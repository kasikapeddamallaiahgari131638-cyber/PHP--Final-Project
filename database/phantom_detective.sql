-- =====================================================================
-- PHANTOM DETECTIVE - A Crime Investigation Game
-- Database Schema + Sample Data
-- Import this file in phpMyAdmin (or `mysql -u root -p < phantom_detective.sql`)
-- =====================================================================

DROP DATABASE IF EXISTS phantom_detective;
CREATE DATABASE phantom_detective CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE phantom_detective;

-- ---------------------------------------------------------------------
-- USERS
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    total_score INT NOT NULL DEFAULT 0,
    cases_completed INT NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ADMIN
-- ---------------------------------------------------------------------
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(120) DEFAULT 'Administrator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CASES
-- ---------------------------------------------------------------------
CREATE TABLE cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    crime_type VARCHAR(60) NOT NULL,
    difficulty ENUM('Easy','Medium','Hard') NOT NULL DEFAULT 'Easy',
    victim_name VARCHAR(120) NOT NULL,
    crime_scene_desc TEXT NOT NULL,
    crime_scene_image VARCHAR(255) DEFAULT 'assets/images/scene_default.svg',
    solution_explanation TEXT NOT NULL,
    estimated_time INT NOT NULL DEFAULT 20,
    status ENUM('published','draft','archived') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EVIDENCE
-- ---------------------------------------------------------------------
CREATE TABLE evidence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    type VARCHAR(60) NOT NULL,
    description TEXT NOT NULL,
    location_found VARCHAR(150) NOT NULL,
    relevance ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    hotspot_x INT DEFAULT 50,
    hotspot_y INT DEFAULT 50,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CLUES (tied to evidence)
-- ---------------------------------------------------------------------
CREATE TABLE clues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evidence_id INT NOT NULL,
    clue_text TEXT NOT NULL,
    FOREIGN KEY (evidence_id) REFERENCES evidence(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- WITNESSES
-- ---------------------------------------------------------------------
CREATE TABLE witnesses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    relationship VARCHAR(120) NOT NULL,
    dialogue TEXT NOT NULL COMMENT 'JSON array of {q,a}',
    important_clue TEXT NOT NULL,
    contradiction TEXT DEFAULT NULL,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SUSPECTS
-- ---------------------------------------------------------------------
CREATE TABLE suspects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    photo VARCHAR(255) DEFAULT 'assets/images/suspect_default.svg',
    age INT DEFAULT NULL,
    occupation VARCHAR(120) DEFAULT NULL,
    relationship_to_victim VARCHAR(150) DEFAULT NULL,
    motive TEXT,
    alibi TEXT,
    evidence_against TEXT,
    evidence_supporting TEXT,
    suspicion_level ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
    is_culprit TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- INVESTIGATION (one row per user attempt on a case)
-- ---------------------------------------------------------------------
CREATE TABLE investigation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    case_id INT NOT NULL,
    status ENUM('in_progress','completed') NOT NULL DEFAULT 'in_progress',
    evidence_collected TEXT DEFAULT NULL COMMENT 'JSON array of evidence ids',
    witnesses_interviewed TEXT DEFAULT NULL COMMENT 'JSON array of witness ids',
    deduction_notes TEXT DEFAULT NULL COMMENT 'JSON deduction chain built by player',
    verdict_suspect_id INT DEFAULT NULL,
    verdict_evidence_id INT DEFAULT NULL,
    verdict_motive VARCHAR(255) DEFAULT NULL,
    verdict_explanation TEXT DEFAULT NULL,
    is_correct TINYINT(1) DEFAULT NULL,
    score INT DEFAULT 0,
    accuracy DECIMAL(5,2) DEFAULT 0,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_case (user_id, case_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- PLAYER PROGRESS
-- ---------------------------------------------------------------------
CREATE TABLE player_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    case_id INT NOT NULL,
    status ENUM('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
    progress_percentage INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_progress (user_id, case_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- LEADERBOARD (denormalized for fast ranking, refreshed on verdict)
-- ---------------------------------------------------------------------
CREATE TABLE leaderboard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    total_score INT NOT NULL DEFAULT 0,
    cases_solved INT NOT NULL DEFAULT 0,
    accuracy DECIMAL(5,2) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ACTIVITY LOGS
-- ---------------------------------------------------------------------
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(150) NOT NULL,
    details VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- REPORTS (simple materialized snapshot, admin can regenerate)
-- ---------------------------------------------------------------------
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(60) NOT NULL,
    report_data TEXT NOT NULL COMMENT 'JSON payload',
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- SAMPLE DATA
-- =====================================================================

-- Admin account -> username: admin  password: Admin@123
INSERT INTO admin (username, password, full_name) VALUES
('admin', '$2y$10$AY1tDsOYR9ufiOODRih8QeDLpx1.lguvo.3rRaQHSjzqOv/sGYytW', 'Chief Administrator');

-- Test user -> username: detective  password: Detective@123
INSERT INTO users (username, email, password, full_name, total_score, cases_completed) VALUES
('detective', 'detective@phantom.local', '$2y$10$vyhrLSfy6SQ2fWUCgIXzC.MGw2OR/6gEL5LelcOgv/.beFsYBZrZS', 'Alex Morgan', 0, 0);

-- =====================================================================
-- CASE 1 : The Vanishing Violinist (Easy / Murder)
-- =====================================================================
INSERT INTO cases (title, description, crime_type, difficulty, victim_name, crime_scene_desc, solution_explanation, estimated_time) VALUES
('The Vanishing Violinist', 'A world-famous violinist is found dead backstage minutes before his farewell concert. The theatre was locked from within.', 'Murder', 'Easy', 'Julian Reeve',
 'Backstage dressing room of the Grand Opera House. The room is in mild disarray - a shattered wine glass, an overturned chair, and the victim\'s violin case lies open and empty on the floor.',
 'The stage manager, Victor Hale, poisoned Julian\'s wine out of jealousy after being passed over for a solo performance for years. He staged the room to look like a struggle and stole the violin to fake a robbery motive.',
 15);

SET @case1 = LAST_INSERT_ID();

INSERT INTO evidence (case_id, name, type, description, location_found, relevance, hotspot_x, hotspot_y) VALUES
(@case1, 'Shattered Wine Glass', 'Physical', 'A wine glass smashed near the mirror. Faint white residue lines the largest shard.', 'Dressing table', 'Critical', 20, 35),
(@case1, 'Backstage Pass', 'Document', 'A backstage pass with a partial thumbprint smudge, dropped near the door.', 'Near the doorway', 'High', 70, 60),
(@case1, 'Threatening Note', 'Document', 'A crumpled note reading a veiled warning about "taking what is owed."', 'Wastebasket', 'High', 40, 80),
(@case1, 'Empty Violin Case', 'Physical', 'The victim\'s violin case, unlocked and empty, lid open.', 'Floor beside the sofa', 'Medium', 80, 30),
(@case1, 'Security Logbook Page', 'Document', 'A torn logbook page showing who badged in backstage that evening.', 'Security desk (adjacent room)', 'Medium', 55, 15);

SET @c1e1 = (SELECT id FROM evidence WHERE case_id=@case1 AND name='Shattered Wine Glass');
SET @c1e2 = (SELECT id FROM evidence WHERE case_id=@case1 AND name='Backstage Pass');
SET @c1e3 = (SELECT id FROM evidence WHERE case_id=@case1 AND name='Threatening Note');
SET @c1e5 = (SELECT id FROM evidence WHERE case_id=@case1 AND name='Security Logbook Page');

INSERT INTO clues (evidence_id, clue_text) VALUES
(@c1e1, 'Lab notes suggest the residue matches a slow-acting sedative-poison mix, not typical stage makeup.'),
(@c1e2, 'The pass belongs to backstage crew, not performers - meaning a crew member was in the room.'),
(@c1e3, 'The handwriting style matches notices pinned on the crew announcement board.'),
(@c1e5, 'Victor Hale badged in twice that evening - once before the show, and again seven minutes before the body was found.');

INSERT INTO witnesses (case_id, name, relationship, dialogue, important_clue, contradiction) VALUES
(@case1, 'Maria Chen', 'Co-performer (cellist)', '[{"q":"Did you see anyone near Julian\'s room?","a":"Victor was hovering by the door around showtime, which was unusual for him."},{"q":"How did Julian seem before the show?","a":"Nervous, but excited. He mentioned finally getting his solo tonight."}]', 'Victor Hale was seen loitering outside the dressing room shortly before the murder.', NULL),
(@case1, 'Tom Reyes', 'Security guard', '[{"q":"Who badged into the backstage area?","a":"Just the usual crew, plus Victor came through twice - once was odd, he doesnt normally return after setup."},{"q":"Did you hear anything unusual?","a":"A crash, like glass breaking, around 8:15 PM."}]', 'Victor returned to the backstage area a second time, right around when the crash was heard.', NULL),
(@case1, 'Victor Hale', 'Stage manager', '[{"q":"Where were you at 8:15 PM?","a":"In the control booth the entire time, checking the lighting cues."},{"q":"Did you go near Julian\'s room?","a":"No, I had no reason to. We barely spoke."}]', 'Victor claims he never went near the dressing room.', 'This directly contradicts the security logbook and Tom Reyes\' account of Victor entering backstage twice.');

INSERT INTO suspects (case_id, name, age, occupation, relationship_to_victim, motive, alibi, evidence_against, evidence_supporting, suspicion_level, is_culprit) VALUES
(@case1, 'Victor Hale', 47, 'Stage Manager', 'Colleague, long-time crew member', 'Passed over for a featured solo for six consecutive seasons in favor of Julian; deep resentment.', 'Claims he was in the control booth the entire evening.', 'Security log shows he re-entered backstage 7 minutes before the body was found; witness saw him near the dressing room; his badge matches the pass found at the scene.', 'No physical evidence places him inside the room itself.', 'High', 1),
(@case1, 'Maria Chen', 34, 'Cellist', 'Fellow performer', 'Minor professional rivalry, but no history of conflict.', 'Was warming up in the shared rehearsal hall with two other musicians.', 'None beyond proximity.', 'Corroborated alibi from two other musicians.', 'Low', 0),
(@case1, 'Lena Ford', 29, 'Julian\'s personal assistant', 'Employee', 'Recently reprimanded by Julian for a scheduling mistake; feared losing her job.', 'Was arranging flowers in the lobby, seen by ushers.', 'The threatening note\'s tone was initially suspected to be hers.', 'Ushers confirm she never left the lobby.', 'Medium', 0);

-- =====================================================================
-- CASE 2 : Silence at Silver Manor (Medium / Murder)
-- =====================================================================
INSERT INTO cases (title, description, crime_type, difficulty, victim_name, crime_scene_desc, solution_explanation, estimated_time) VALUES
('Silence at Silver Manor', 'A wealthy widow is found dead in her study during a family reunion dinner. Every relative had a reason to want her fortune.', 'Murder', 'Medium', 'Eleanor Ashcroft',
 'The private study of Silver Manor. A fireplace still smolders, a decanter of brandy sits half-empty on the desk, and a will lies open, one clause circled in red ink.',
 'Nephew Richard Ashcroft altered the will earlier that week and murdered Eleanor with a poisoned brandy pour after she discovered the forgery and confronted him privately.',
 25);

SET @case2 = LAST_INSERT_ID();

INSERT INTO evidence (case_id, name, type, description, location_found, relevance, hotspot_x, hotspot_y) VALUES
(@case2, 'Altered Will', 'Document', 'The will, with one inheritance clause visibly rewritten in slightly different ink.', 'Desk', 'Critical', 45, 40),
(@case2, 'Brandy Decanter', 'Physical', 'A half-empty decanter with an unusual bitter smell beneath the alcohol.', 'Side table', 'Critical', 15, 55),
(@case2, 'Muddy Footprints', 'Physical', 'Footprints leading from the garden terrace door to the desk.', 'Terrace door', 'High', 80, 70),
(@case2, 'Torn Letter', 'Document', 'A partially burned letter fragment mentioning "the forged signature."', 'Fireplace', 'High', 30, 20),
(@case2, 'Pocket Watch', 'Physical', 'An engraved pocket watch, not belonging to the victim, found under the desk.', 'Under the desk', 'Medium', 55, 60);

SET @c2e1 = (SELECT id FROM evidence WHERE case_id=@case2 AND name='Altered Will');
SET @c2e2 = (SELECT id FROM evidence WHERE case_id=@case2 AND name='Brandy Decanter');
SET @c2e3 = (SELECT id FROM evidence WHERE case_id=@case2 AND name='Muddy Footprints');
SET @c2e5 = (SELECT id FROM evidence WHERE case_id=@case2 AND name='Pocket Watch');

INSERT INTO clues (evidence_id, clue_text) VALUES
(@c2e1, 'A handwriting expert would note the circled clause ink does not match the rest of the document, dated within the last week.'),
(@c2e2, 'The bitter smell is consistent with crushed almond seeds - a folk poison mixed into the brandy.'),
(@c2e3, 'The footprint size and stride match a tall adult male, and the mud matches the manor\'s garden, not the driveway gravel.'),
(@c2e5, 'The watch is engraved "R.A." - initials matching Richard Ashcroft.');

INSERT INTO witnesses (case_id, name, relationship, dialogue, important_clue, contradiction) VALUES
(@case2, 'Beatrice Ashcroft', 'Eleanor\'s daughter', '[{"q":"Where were you during dinner?","a":"At the table the whole time, I never left, ask anyone."},{"q":"Did Mother seem upset tonight?","a":"Yes, actually. She left dinner early saying she needed to check something in the study."}]', 'Eleanor left the dinner table specifically to check something in the study, shortly before her death.', NULL),
(@case2, 'Mr. Higgins', 'Butler', '[{"q":"Did anyone leave through the terrace?","a":"I noticed the terrace door was ajar after dinner, which was unusual - it is always kept locked."},{"q":"Did you see Richard that evening?","a":"He excused himself briefly, said he needed air on the terrace."}]', 'Richard left the dinner table around the same time and claimed to be on the terrace - right by the entrance used by the killer.', NULL),
(@case2, 'Richard Ashcroft', 'Eleanor\'s nephew', '[{"q":"Where were you when Eleanor died?","a":"I stepped out onto the terrace for a smoke, alone, for maybe five minutes."},{"q":"Did you speak with Eleanor privately tonight?","a":"No, not really, just the usual pleasantries at dinner."}]', 'Richard admits being on the terrace alone during the window of the murder.', 'Beatrice later reveals Eleanor said she wanted to speak to Richard specifically about "the paperwork" before she died - contradicting his claim of no private conversation.');

INSERT INTO suspects (case_id, name, age, occupation, relationship_to_victim, motive, alibi, evidence_against, evidence_supporting, suspicion_level, is_culprit) VALUES
(@case2, 'Richard Ashcroft', 41, 'Businessman (in debt)', 'Nephew', 'Deep in gambling debt; forged a will clause to inherit a larger share of the estate.', 'Says he stepped onto the terrace alone for five minutes.', 'Pocket watch with his initials found under the desk; muddy footprints from terrace match his build; letter fragment references a forged signature; butler confirms his terrace exit lines up with the time of death.', 'No one directly saw him enter the study.', 'High', 1),
(@case2, 'Beatrice Ashcroft', 38, 'Eleanor\'s daughter', 'Daughter', 'Would inherit the majority regardless; strained relationship with mother but no financial motive.', 'Remained at the dinner table the whole evening, confirmed by three guests.', 'None significant.', 'Multiple guests confirm she never left the table.', 'Low', 0),
(@case2, 'Mr. Higgins', 58, 'Butler', 'Longtime household staff', 'Recently informed he would be let go after Eleanor downsized the household; resentment.', 'Was serving the dinner table continuously, confirmed by kitchen staff.', 'Knew the terrace door was unlocked.', 'Kitchen staff confirm his continuous presence.', 'Medium', 0);

-- =====================================================================
-- CASE 3 : The Midnight Gallery Heist (Hard / Theft)
-- =====================================================================
INSERT INTO cases (title, description, crime_type, difficulty, victim_name, crime_scene_desc, solution_explanation, estimated_time) VALUES
('The Midnight Gallery Heist', 'A priceless Renaissance painting vanishes from a private gallery during a black-tie exhibition, despite tight security.', 'Theft', 'Hard', 'Vermont Art Gallery (Owner: Isabelle Duvall)',
 'The gallery\'s main hall after the exhibition closed. An empty frame hangs crookedly on the wall, a security camera has been subtly repositioned, and a champagne flute with lipstick stands abandoned near the exit.',
 'The gallery\'s own art restorer, Nadia Petrov, used her after-hours access and technical knowledge to disable the sensor grid and swap the painting with a near-perfect forgery she had been secretly preparing for months.',
 30);

SET @case3 = LAST_INSERT_ID();

INSERT INTO evidence (case_id, name, type, description, location_found, relevance, hotspot_x, hotspot_y) VALUES
(@case3, 'Repositioned Camera', 'Physical', 'The hallway camera is tilted three degrees from its usual angle, just enough to miss the frame.', 'Hallway ceiling', 'Critical', 60, 15),
(@case3, 'Forged Canvas Fragment', 'Physical', 'A tiny fragment of canvas and paint, chemically inconsistent with 16th-century materials.', 'Behind the frame', 'Critical', 45, 45),
(@case3, 'Access Keycard Log', 'Document', 'Digital log showing the restoration studio keycard used at 11:47 PM, after the gallery closed.', 'Security office terminal', 'High', 20, 25),
(@case3, 'Lipstick-Stained Flute', 'Physical', 'A champagne flute with a distinct shade of red lipstick, abandoned near the fire exit.', 'Near fire exit', 'Medium', 85, 65),
(@case3, 'Restoration Studio Receipt', 'Document', 'A supply receipt for rare period pigments, ordered under the restoration department budget three months ago.', 'Restoration studio bin', 'High', 30, 75);

SET @c3e1 = (SELECT id FROM evidence WHERE case_id=@case3 AND name='Repositioned Camera');
SET @c3e2 = (SELECT id FROM evidence WHERE case_id=@case3 AND name='Forged Canvas Fragment');
SET @c3e3 = (SELECT id FROM evidence WHERE case_id=@case3 AND name='Access Keycard Log');
SET @c3e5 = (SELECT id FROM evidence WHERE case_id=@case3 AND name='Restoration Studio Receipt');

INSERT INTO clues (evidence_id, clue_text) VALUES
(@c3e1, 'Only someone trained on the gallery\'s specific mounting system would know exactly how many degrees to tilt the camera without triggering an alert.'),
(@c3e2, 'The fragment\'s pigment binder is a modern synthetic - a forger would need this exact analysis to pass a casual glance.'),
(@c3e3, 'The restoration studio keycard is only issued to two people: the head restorer and the assistant restorer.'),
(@c3e5, 'The pigments ordered are precisely the rare period-accurate colors needed to forge this specific painting.');

INSERT INTO witnesses (case_id, name, relationship, dialogue, important_clue, contradiction) VALUES
(@case3, 'Isabelle Duvall', 'Gallery owner', '[{"q":"Who had after-hours access?","a":"Only senior staff - myself, the head of security, and the restoration team."},{"q":"Did anything seem off before the theft?","a":"Nadia asked for extra time alone with the painting last week, for a routine condition check, which was approved."}]', 'Nadia requested unsupervised time alone with the painting just days before it vanished.', NULL),
(@case3, 'Marcus Lee', 'Head of security', '[{"q":"Did the alarms trigger?","a":"No, which is strange, because the sensor grid should have caught any movement near the frame."},{"q":"Whose keycard opened the studio that night?","a":"The log shows the assistant restorer\'s card, used well after closing."}]', 'The keycard log directly points to the assistant restorer\'s access being used after hours.', NULL),
(@case3, 'Nadia Petrov', 'Assistant restorer', '[{"q":"Where were you at 11:47 PM?","a":"Home, asleep. I left right after the exhibition closed at 10."},{"q":"Do you know how the camera got tilted?","a":"No idea, maybe a maintenance issue."}]', 'Nadia claims she left before 10 PM and was home by the time of the theft.', 'The keycard log places her card being used inside the restoration studio at 11:47 PM, directly contradicting her claim of being home asleep.');

INSERT INTO suspects (case_id, name, age, occupation, relationship_to_victim, motive, alibi, evidence_against, evidence_supporting, suspicion_level, is_culprit) VALUES
(@case3, 'Nadia Petrov', 33, 'Assistant Art Restorer', 'Gallery employee', 'Years of being overlooked for promotion; secretly building a black-market forgery career; financial desperation.', 'Claims she left at 10 PM and was home asleep by 11:47 PM.', 'Keycard log places her in the studio at 11:47 PM; she requested unsupervised access to the painting days earlier; restoration receipt shows she ordered the exact pigments used in the forgery fragment; only she and the head restorer could disable the camera correctly.', 'No direct eyewitness saw her touch the frame itself.', 'High', 1),
(@case3, 'Marcus Lee', 45, 'Head of Security', 'Gallery employee', 'Access to systems and cameras, but no clear financial motive uncovered.', 'Was monitoring the security office continuously per shift logs.', 'Knew the camera blind spots.', 'Shift logs and a second guard confirm his continuous presence in the office.', 'Low', 0),
(@case3, 'Isabelle Duvall', 52, 'Gallery Owner', 'Owner/victim of the theft', 'Insurance payout would be substantial, but she has no history of financial trouble and reported the theft immediately.', 'Was greeting guests in the main hall until the exhibition closed, confirmed by dozens of attendees.', 'Owns the gallery and would benefit from insurance.', 'Confirmed by numerous exhibition guests and staff.', 'Medium', 0);

-- Player progress rows for the demo user (not started yet)
INSERT INTO player_progress (user_id, case_id, status, progress_percentage)
SELECT u.id, c.id, 'not_started', 0 FROM users u, cases c WHERE u.username='detective';

-- Leaderboard seed row for demo user
INSERT INTO leaderboard (user_id, total_score, cases_solved, accuracy)
SELECT id, 0, 0, 0 FROM users WHERE username='detective';
