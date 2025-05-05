<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

// Karteikarten für einen Benutzer abrufen
function getFlashcardsForUser($userId, $unitId) {
    // Heute
    $today = date('Y-m-d H:i:s');
    
    // Datenbankverbindung herstellen
    $conn = connectDB();
    
    // Vokabeln aus der angegebenen Einheit abrufen
    $stmt = $conn->prepare("SELECT * FROM vocabulary WHERE unit_id = ?");
    $stmt->bind_param("i", $unitId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return [];
    }
    
    $flashcards = [];
    
    while ($vocab = $result->fetch_assoc()) {
        // Fortschritt für diese Vokabel abrufen
        $progressStmt = $conn->prepare("SELECT * FROM user_progress WHERE user_id = ? AND vocab_id = ?");
        $progressStmt->bind_param("ii", $userId, $vocab['vocab_id']);
        $progressStmt->execute();
        $progressResult = $progressStmt->get_result();
        
        // Wenn kein Fortschritt oder nächste Überprüfung ist fällig
        if ($progressResult->num_rows === 0 || 
            ($progress = $progressResult->fetch_assoc()) && 
            (empty($progress['next_review_date']) || $progress['next_review_date'] <= $today)) {
            $flashcards[] = $vocab;
        }
        
        $progressStmt->close();
    }
    
    $stmt->close();
    $conn->close();
    
    return $flashcards;
}

// Karteikarte überprüfen
function checkFlashcard($userId, $vocabId, $userAnswer, $fromGerman = true) {
    // Vokabel abrufen
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM vocabulary WHERE vocab_id = ?");
    $stmt->bind_param("i", $vocabId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Vokabel nicht gefunden'];
    }
    
    $vocab = $result->fetch_assoc();
    
    // Je nach Richtung (Deutsch->Englisch oder Englisch->Deutsch) überprüfen
    $correct = false;
    $correctAnswer = '';
    
    if ($fromGerman) {
        $correct = strtolower(trim($userAnswer)) === strtolower(trim($vocab['english_word']));
        $correctAnswer = $vocab['english_word'];
    } else {
        $correct = strtolower(trim($userAnswer)) === strtolower(trim($vocab['german_word']));
        $correctAnswer = $vocab['german_word'];
    }
    
    // Fortschritt abrufen oder erstellen
    $progressStmt = $conn->prepare("SELECT * FROM user_progress WHERE user_id = ? AND vocab_id = ?");
    $progressStmt->bind_param("ii", $userId, $vocabId);
    $progressStmt->execute();
    $progressResult = $progressStmt->get_result();
    
    if ($progressResult->num_rows === 0) {
        // Neuen Fortschritt erstellen
        $nextReviewDate = calculateNextReviewDate($correct, 0);
        
        $newProgress = $conn->prepare("INSERT INTO user_progress (user_id, vocab_id, correct_count, incorrect_count, last_answered, next_review_date, is_mastered) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $correctCount = $correct ? 1 : 0;
        $incorrectCount = $correct ? 0 : 1;
        $lastAnswered = date('Y-m-d H:i:s');
        $isMastered = false;
        $newProgress->bind_param("iiisssi", $userId, $vocabId, $correctCount, $incorrectCount, $lastAnswered, $nextReviewDate, $isMastered);
        $newProgress->execute();
        $newProgress->close();
    } else {
        // Fortschritt aktualisieren
        $progress = $progressResult->fetch_assoc();
        $correctCount = $progress['correct_count'] + ($correct ? 1 : 0);
        $incorrectCount = $progress['incorrect_count'] + ($correct ? 0 : 1);
        $nextReviewDate = calculateNextReviewDate($correct, $correctCount);
        $isMastered = ($correctCount >= 5 && ($correctCount / ($correctCount + $incorrectCount) >= 0.8)) ? 1 : 0;
        $lastAnswered = date('Y-m-d H:i:s');
        
        $updateProgress = $conn->prepare("UPDATE user_progress SET correct_count = ?, incorrect_count = ?, last_answered = ?, next_review_date = ?, is_mastered = ? WHERE user_id = ? AND vocab_id = ?");
        $updateProgress->bind_param("iissiii", $correctCount, $incorrectCount, $lastAnswered, $nextReviewDate, $isMastered, $userId, $vocabId);
        $updateProgress->execute();
        $updateProgress->close();
    }
    
    $progressStmt->close();
    
    // Bei falscher Antwort in wrong_answers speichern
    if (!$correct) {
        // Prüfen, ob bereits ein Eintrag existiert
        $wrongCheck = $conn->prepare("SELECT * FROM wrong_answers WHERE user_id = ? AND vocab_id = ?");
        $wrongCheck->bind_param("ii", $userId, $vocabId);
        $wrongCheck->execute();
        $wrongResult = $wrongCheck->get_result();
        $lastWrongDate = date('Y-m-d H:i:s');
        
        if ($wrongResult->num_rows === 0) {
            $insertWrong = $conn->prepare("INSERT INTO wrong_answers (user_id, vocab_id, last_wrong_date) VALUES (?, ?, ?)");
            $insertWrong->bind_param("iis", $userId, $vocabId, $lastWrongDate);
            $insertWrong->execute();
            $insertWrong->close();
        } else {
            $updateWrong = $conn->prepare("UPDATE wrong_answers SET last_wrong_date = ? WHERE user_id = ? AND vocab_id = ?");
            $updateWrong->bind_param("sii", $lastWrongDate, $userId, $vocabId);
            $updateWrong->execute();
            $updateWrong->close();
        }
        
        $wrongCheck->close();
    } else {
        // Bei richtiger Antwort aus wrong_answers entfernen
        $deleteWrong = $conn->prepare("DELETE FROM wrong_answers WHERE user_id = ? AND vocab_id = ?");
        $deleteWrong->bind_param("ii", $userId, $vocabId);
        $deleteWrong->execute();
        $deleteWrong->close();
        
        // Statistik aktualisieren
        $userStmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        
        if ($userResult->num_rows > 0) {
            $userData = $userResult->fetch_assoc();
            $wordsLearned = $userData['total_words_learned'] + 1;
            
            $updateUser = $conn->prepare("UPDATE users SET total_words_learned = ? WHERE user_id = ?");
            $updateUser->bind_param("ii", $wordsLearned, $userId);
            $updateUser->execute();
            $updateUser->close();
            
            // Bestenliste aktualisieren
            $updateLeaderboard = $conn->prepare("UPDATE leaderboard SET words_learned = ?, score = ? WHERE user_id = ?");
            $score = $wordsLearned * 10 + $userData['streak_days'] * 5;
            $updateLeaderboard->bind_param("iii", $wordsLearned, $score, $userId);
            $updateLeaderboard->execute();
            $updateLeaderboard->close();
        }
        
        $userStmt->close();
    }
    
    $conn->close();
    
    return [
        'success' => true,
        'correct' => $correct,
        'correct_answer' => $correctAnswer,
        'message' => $correct ? 'Richtig!' : 'Falsch! Die richtige Antwort wäre: ' . $correctAnswer
    ];
}

// Berechnung des nächsten Überprüfungsdatums basierend auf dem Spaced Repetition System
function calculateNextReviewDate($correct, $correctCount) {
    if (!$correct) {
        // Bei falscher Antwort sofort wieder anzeigen
        return date('Y-m-d H:i:s');
    }
    
    // Basierend auf der Anzahl der richtigen Antworten den Zeitabstand erhöhen
    $days = 1;
    
    if ($correctCount > 0) {
        if ($correctCount == 1) {
            $days = 1;
        } else if ($correctCount == 2) {
            $days = 3;
        } else if ($correctCount == 3) {
            $days = 7;
        } else if ($correctCount == 4) {
            $days = 14;
        } else {
            $days = 30;
        }
    }
    
    return date('Y-m-d H:i:s', strtotime("+$days days"));
}

// Mini-Test starten für eine bestimmte Einheit
function startMiniTest($userId, $unitId, $numQuestions = 10) {
    // Vokabeln aus der Einheit abrufen
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM vocabulary WHERE unit_id = ?");
    $stmt->bind_param("i", $unitId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Keine Vokabeln in dieser Einheit gefunden'];
    }
    
    // Alle Vokabeln in ein Array sammeln
    $vocabularies = [];
    while ($row = $result->fetch_assoc()) {
        $vocabularies[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    // Zufällige Auswahl von Vokabeln (maximal $numQuestions)
    shuffle($vocabularies);
    $testVocabs = array_slice($vocabularies, 0, min($numQuestions, count($vocabularies)));
    
    // Test-Session speichern
    $_SESSION['mini_test'] = [
        'unit_id' => $unitId,
        'questions' => $testVocabs,
        'current_question' => 0,
        'correct_answers' => 0,
        'total_questions' => count($testVocabs),
        'start_time' => time()
    ];
    
    return [
        'success' => true,
        'message' => 'Mini-Test gestartet',
        'total_questions' => count($testVocabs),
        'first_question' => $testVocabs[0]
    ];
}

// Antwort im Mini-Test überprüfen
function checkMiniTestAnswer($userAnswer, $fromGerman = true) {
    // Prüfen, ob ein Test aktiv ist
    if (!isset($_SESSION['mini_test']) || !isset($_SESSION['mini_test']['questions'])) {
        return ['success' => false, 'message' => 'Kein aktiver Test'];
    }
    
    $test = &$_SESSION['mini_test'];
    $currentQ = $test['current_question'];
    
    if ($currentQ >= count($test['questions'])) {
        return ['success' => false, 'message' => 'Test ist bereits abgeschlossen'];
    }
    
    $vocab = $test['questions'][$currentQ];
    
    // Antwort überprüfen
    $correct = false;
    $correctAnswer = '';
    
    if ($fromGerman) {
        $correct = strtolower(trim($userAnswer)) === strtolower(trim($vocab['english_word']));
        $correctAnswer = $vocab['english_word'];
    } else {
        $correct = strtolower(trim($userAnswer)) === strtolower(trim($vocab['german_word']));
        $correctAnswer = $vocab['german_word'];
    }
    
    // Test aktualisieren
    if ($correct) {
        $test['correct_answers']++;
    }
    
    $test['current_question']++;
    $isComplete = $test['current_question'] >= $test['total_questions'];
    $nextQuestion = $isComplete ? null : $test['questions'][$test['current_question']];
    
    // Bei Testabschluss die Statistik aktualisieren
    if ($isComplete) {
        $score = ($test['correct_answers'] / $test['total_questions']) * 100;
        $userId = $_SESSION['user_id'];
        
        // Units_learned aktualisieren, wenn Score über 70%
        if ($score >= 70) {
            $conn = connectDB();
            $stmt = $conn->prepare("SELECT total_units_learned FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $userData = $result->fetch_assoc();
                $unitsLearned = $userData['total_units_learned'] + 1;
                
                $updateStmt = $conn->prepare("UPDATE users SET total_units_learned = ? WHERE user_id = ?");
                $updateStmt->bind_param("ii", $unitsLearned, $userId);
                $updateStmt->execute();
                $updateStmt->close();
            }
            
            $stmt->close();
            $conn->close();
        }
    }
    
    return [
        'success' => true,
        'correct' => $correct,
        'correct_answer' => $correctAnswer,
        'message' => $correct ? 'Richtig!' : 'Falsch! Die richtige Antwort wäre: ' . $correctAnswer,
        'is_complete' => $isComplete,
        'next_question' => $nextQuestion,
        'progress' => [
            'current' => $test['current_question'],
            'total' => $test['total_questions'],
            'correct' => $test['correct_answers']
        ]
    ];
}

// Mini-Kahoot (Multiple Choice) starten
function startMiniKahoot($userId, $unitId, $numQuestions = 10) {
    // Vokabeln aus der Einheit abrufen
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM vocabulary WHERE unit_id = ?");
    $stmt->bind_param("i", $unitId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Keine Vokabeln in dieser Einheit gefunden'];
    }
    
    // Alle Vokabeln in ein Array sammeln
    $vocabularies = [];
    while ($row = $result->fetch_assoc()) {
        $vocabularies[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    // Zufällige Auswahl von Vokabeln (maximal $numQuestions)
    shuffle($vocabularies);
    $testVocabs = array_slice($vocabularies, 0, min($numQuestions, count($vocabularies)));
    
    // Für jede Frage Multiple-Choice-Optionen vorbereiten
    $questionsWithOptions = [];
    
    foreach ($testVocabs as $vocab) {
        // Zufällig entscheiden, ob von Deutsch nach Englisch oder umgekehrt
        $fromGerman = (rand(0, 1) == 0);
        
        // Richtige Antwort und Frage bestimmen
        $question = $fromGerman ? $vocab['german_word'] : $vocab['english_word'];
        $answer = $fromGerman ? $vocab['english_word'] : $vocab['german_word'];
        
        // 3 zufällige falsche Antworten sammeln
        $wrongAnswers = [];
        $otherVocabs = array_filter($vocabularies, function($v) use ($vocab) {
            return $v['vocab_id'] != $vocab['vocab_id'];
        });
        
        shuffle($otherVocabs);
        
        for ($i = 0; $i < min(3, count($otherVocabs)); $i++) {
            $wrongAnswers[] = $fromGerman ? $otherVocabs[$i]['english_word'] : $otherVocabs[$i]['german_word'];
        }
        
        // Antworten mischen
        $options = array_merge([$answer], $wrongAnswers);
        shuffle($options);
        
        $questionsWithOptions[] = [
            'vocab_id' => $vocab['vocab_id'],
            'question' => $question,
            'correct_answer' => $answer,
            'options' => $options,
            'from_german' => $fromGerman
        ];
    }
    
    // Kahoot-Session speichern
    $_SESSION['mini_kahoot'] = [
        'unit_id' => $unitId,
        'questions' => $questionsWithOptions,
        'current_question' => 0,
        'correct_answers' => 0,
        'total_questions' => count($questionsWithOptions),
        'start_time' => time()
    ];
    
    return [
        'success' => true,
        'message' => 'Mini-Kahoot gestartet',
        'total_questions' => count($questionsWithOptions),
        'first_question' => $questionsWithOptions[0]
    ];
}

// Antwort im Mini-Kahoot überprüfen
function checkMiniKahootAnswer($selectedOption) {
    // Prüfen, ob ein Kahoot aktiv ist
    if (!isset($_SESSION['mini_kahoot']) || !isset($_SESSION['mini_kahoot']['questions'])) {
        return ['success' => false, 'message' => 'Kein aktiver Kahoot'];
    }
    
    $kahoot = &$_SESSION['mini_kahoot'];
    $currentQ = $kahoot['current_question'];
    
    if ($currentQ >= count($kahoot['questions'])) {
        return ['success' => false, 'message' => 'Kahoot ist bereits abgeschlossen'];
    }
    
    $question = $kahoot['questions'][$currentQ];
    
    // Antwort überprüfen
    $correct = $selectedOption === $question['correct_answer'];
    
    // Kahoot aktualisieren
    if ($correct) {
        $kahoot['correct_answers']++;
    }
    
    $kahoot['current_question']++;
    $isComplete = $kahoot['current_question'] >= $kahoot['total_questions'];
    $nextQuestion = $isComplete ? null : $kahoot['questions'][$kahoot['current_question']];
    
    // Bei Abschluss die Statistik aktualisieren (wie bei Mini-Test)
    if ($isComplete) {
        $score = ($kahoot['correct_answers'] / $kahoot['total_questions']) * 100;
        $userId = $_SESSION['user_id'];
        
        // Units_learned aktualisieren, wenn Score über 70%
        if ($score >= 70) {
            $conn = connectDB();
            $stmt = $conn->prepare("SELECT total_units_learned FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $userData = $result->fetch_assoc();
                $unitsLearned = $userData['total_units_learned'] + 1;
                
                $updateStmt = $conn->prepare("UPDATE users SET total_units_learned = ? WHERE user_id = ?");
                $updateStmt->bind_param("ii", $unitsLearned, $userId);
                $updateStmt->execute();
                $updateStmt->close();
            }
            
            $stmt->close();
            $conn->close();
        }
    }
    
    return [
        'success' => true,
        'correct' => $correct,
        'correct_answer' => $question['correct_answer'],
        'message' => $correct ? 'Richtig!' : 'Falsch! Die richtige Antwort wäre: ' . $question['correct_answer'],
        'is_complete' => $isComplete,
        'next_question' => $nextQuestion,
        'progress' => [
            'current' => $kahoot['current_question'],
            'total' => $kahoot['total_questions'],
            'correct' => $kahoot['correct_answers']
        ]
    ];
}