- Datenbank erstellen
CREATE DATABASE IF NOT EXISTS vocabulary_app;
USE vocabulary_app;

-- Admin-Tabelle
CREATE TABLE admin (
    adminid INT PRIMARY KEY AUTO_INCREMENT,
    adminname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Teacher-Tabelle
CREATE TABLE teacher (
    teacherid INT PRIMARY KEY AUTO_INCREMENT,
    teachername VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Group-Tabelle
CREATE TABLE `group` (
    groupid INT PRIMARY KEY AUTO_INCREMENT,
    groupname VARCHAR(255) NOT NULL,
    teacherid INT NOT NULL,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacherid) REFERENCES teacher(teacherid)
);

-- Student-Tabelle (mit direkter Gruppenzugehörigkeit)
CREATE TABLE student (
    studentid INT PRIMARY KEY AUTO_INCREMENT,
    studentname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    groupid INT,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (groupid) REFERENCES `group`(groupid)
);

-- Unit-Tabelle
CREATE TABLE unit (
    unitid INT PRIMARY KEY AUTO_INCREMENT,
    groupid INT NOT NULL,
    unitname VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (groupid) REFERENCES `group`(groupid)
);

-- Vocab-German-Tabelle
CREATE TABLE vocabgerman (
    gvocabid INT PRIMARY KEY AUTO_INCREMENT,
    unitid INT NOT NULL,
    german_word TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unitid) REFERENCES unit(unitid)
);

-- Vocab-English-Tabelle
CREATE TABLE vocabenglish (
    evocabid INT PRIMARY KEY AUTO_INCREMENT,
    unitid INT NOT NULL,
    english_word TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unitid) REFERENCES unit(unitid)
);

-- Vocab-Mapping für N:M Beziehung (Synonyme)
CREATE TABLE vocabmapping (
    gvocabid INT,
    evocabid INT,
    PRIMARY KEY (gvocabid, evocabid),
    FOREIGN KEY (gvocabid) REFERENCES vocabgerman(gvocabid),
    FOREIGN KEY (evocabid) REFERENCES vocabenglish(evocabid)
);

-- Vocab-Right-Tabelle
CREATE TABLE vocabright (
    studentid INT,
    gvocabid INT,
    evocabid INT,
    correct_answers INT DEFAULT 1,
    last_answered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (studentid, gvocabid, evocabid),
    FOREIGN KEY (studentid) REFERENCES student(studentid),
    FOREIGN KEY (gvocabid) REFERENCES vocabgerman(gvocabid),
    FOREIGN KEY (evocabid) REFERENCES vocabenglish(evocabid)
);

-- Vocab-Wrong-Tabelle
CREATE TABLE vocabwrong (
    studentid INT,
    gvocabid INT,
    evocabid INT,
    wrong_answers INT DEFAULT 1,
    last_answered TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (studentid, gvocabid, evocabid),
    FOREIGN KEY (studentid) REFERENCES student(studentid),
    FOREIGN KEY (gvocabid) REFERENCES vocabgerman(gvocabid),
    FOREIGN KEY (evocabid) REFERENCES vocabenglish(evocabid)
);

-- Favourite-Tabelle
CREATE TABLE favourite (
    studentid INT,
    unitid INT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (studentid, unitid),
    FOREIGN KEY (studentid) REFERENCES student(studentid),
    FOREIGN KEY (unitid) REFERENCES unit(unitid)
);

-- View für komplette Gruppennamen
CREATE VIEW groupnames AS
SELECT 
    g.groupid,
    g.groupname,
    g.teacherid,
    CONCAT(t.teachername, '_', g.groupname) as full_groupname,
    g.password,
    g.created_at
FROM `group` g
JOIN teacher t ON g.teacherid = t.teacherid;