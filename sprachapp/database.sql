-- Benutzer-Tabelle
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    streak_days INT DEFAULT 0,
    last_login_date DATE,
    total_words_learned INT DEFAULT 0,
    total_units_learned INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Einheiten (Units) Tabelle
CREATE TABLE units (
    unit_id INT AUTO_INCREMENT PRIMARY KEY,
    unit_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_by INT,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Vokabeln Tabelle
CREATE TABLE vocabulary (
    vocab_id INT AUTO_INCREMENT PRIMARY KEY,
    german_word VARCHAR(100) NOT NULL,
    english_word VARCHAR(100) NOT NULL,
    unit_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(german_word, english_word, unit_id),
    FOREIGN KEY (unit_id) REFERENCES units(unit_id) ON DELETE CASCADE
);

-- User Favoriten
CREATE TABLE user_favorites (
    user_id INT,
    unit_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(user_id, unit_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(unit_id) ON DELETE CASCADE
);

-- User Progress
CREATE TABLE user_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    vocab_id INT,
    correct_count INT DEFAULT 0,
    incorrect_count INT DEFAULT 0,
    last_answered TIMESTAMP,
    next_review_date TIMESTAMP,
    is_mastered BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, vocab_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (vocab_id) REFERENCES vocabulary(vocab_id) ON DELETE CASCADE
);

-- Falsch beantwortete Vokabeln
CREATE TABLE wrong_answers (
    wrong_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    vocab_id INT,
    last_wrong_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, vocab_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (vocab_id) REFERENCES vocabulary(vocab_id) ON DELETE CASCADE
);

-- Bestenliste
CREATE TABLE leaderboard (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    score INT DEFAULT 0,
    streak_days INT DEFAULT 0,
    words_learned INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Erstelle einen Beispiel-Admin-Benutzer
-- Passwort ist 'admin123' (gehashed)
INSERT INTO users (username, email, password, is_admin, created_at) 
VALUES ('admin', 'admin@example.com', '$2y$10$93SU0QelwgS7fI24YT3k7eBXJdxd1Le1JYDOjjUPZjnFVH5Nvy8Dy', TRUE, NOW());