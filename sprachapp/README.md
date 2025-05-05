# SprachApp - Vokabeltrainer

SprachApp ist ein Vokabeltrainer für Deutsch und Englisch mit verschiedenen Lernmethoden wie Karteikarten, Mini-Tests und Multiple-Choice-Quiz.

## Funktionen

- Benutzerverwaltung (Registrierung, Login, Profil)
- Karteikartensystem mit Spaced Repetition
- Deutsch-Englisch und Englisch-Deutsch Lernrichtungen
- Audioausgabe der Vokabeln
- Mini-Test mit Freitexteingabe
- Multiple-Choice-Quiz (Mini-Kahoot)
- Speicherung falsch beantworteter Vokabeln
- Streaks und Belohnungssystem
- Bestenliste
- Admin-Bereich für die Verwaltung von Inhalten

## Installation

1. Kopiere alle Dateien in das Webverzeichnis deines PHP-Servers
2. Importiere die `database.sql` in deine Datenbank
3. Passe die Datenbankverbindungsdetails in `config.php` an
4. Registriere einen Benutzer und setze diesen in der Datenbank als Admin
   ```sql
   UPDATE users SET is_admin = TRUE WHERE username = 'deinbenutzername';