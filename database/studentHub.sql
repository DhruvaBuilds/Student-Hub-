-- ============================================================
-- StudentHub database dump  (Practical 8 / 9 / 10 / 11 / 12 / 14)
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < studentHub.sql
-- Demo logins:  admin@studenthub.edu / Admin@123
--               rahul.sharma@email.com / Student@123
-- ============================================================
CREATE DATABASE IF NOT EXISTS studenthub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studenthub;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS remember_tokens;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','student') NOT NULL DEFAULT 'student',
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login    DATETIME NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE students (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id      INT UNSIGNED NULL,
  student_code VARCHAR(20) NOT NULL,
  name         VARCHAR(100) NOT NULL,
  email        VARCHAR(150) NOT NULL,
  phone        VARCHAR(15)  NOT NULL,
  course       VARCHAR(100) NOT NULL,
  year         TINYINT UNSIGNED NOT NULL,
  gender       ENUM('male','female','other') NOT NULL,
  status       ENUM('active','inactive','deleted') NOT NULL DEFAULT 'active',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_students_email (email),
  UNIQUE KEY uq_students_code (student_code),
  UNIQUE KEY uq_students_user (user_id),
  KEY idx_students_course (course),
  KEY idx_students_status (status),
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE events (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title       VARCHAR(150) NOT NULL,
  description TEXT NULL,
  event_date  DATE NOT NULL,
  location    VARCHAR(150) NOT NULL,
  category    VARCHAR(30)  NOT NULL,
  status      ENUM('open','closed') NOT NULL DEFAULT 'open',
  poster      VARCHAR(255) NULL,
  created_by  INT UNSIGNED NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_events_date (event_date),
  KEY idx_events_category (category),
  CONSTRAINT fk_events_user FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE registrations (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id    INT UNSIGNED NOT NULL,
  event_id      INT UNSIGNED NOT NULL,
  registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_registration (student_id, event_id),
  KEY idx_registered_at (registered_at),
  CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reg_event   FOREIGN KEY (event_id)   REFERENCES events (id)   ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NULL,
  action     VARCHAR(50)  NOT NULL,
  details    VARCHAR(255) NULL,
  ip_address VARCHAR(45)  NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_action (action),
  KEY idx_audit_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Remember-me: only a SHA-256 hash of the random token is stored (never the token itself)
CREATE TABLE remember_tokens (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  selector   CHAR(24) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_selector (selector),
  KEY idx_token_user (user_id),
  CONSTRAINT fk_token_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- seed: users ----------
INSERT INTO users (id, name, email, password_hash, role, status, last_login) VALUES
(1, 'Admin User', 'admin@studenthub.edu', '$2y$12$zULmaOQbF/3Ys19XcUE..edZMweSF6o1xEoVqO9AJNhurTXnTcx7K', 'admin', 'active', NULL),
(2, 'Rahul Sharma', 'rahul.sharma@email.com', '$2y$12$ed8uZGawayFV72Dh.HIhiu7gumauVko/QBHWGOMMmcnL0ha8cfmuu', 'student', 'active', NULL),
(3, 'Priya Patel', 'priya.patel@email.com', '$2y$12$CiN8NPqdOkLYSC7h2eQzDe.ILWjyqxScOW0fZQvPA4MI8cR6xgeNa', 'student', 'active', NULL),
(4, 'Amit Shah', 'amit.shah@email.com', '$2y$12$msZ15IPwWnSUbZjGqu4Rh.b9fKG0HHptpF7L/GT1VlVmvNurzjecW', 'student', 'active', NULL),
(5, 'Inactive Student', 'inactive.student@email.com', '$2y$12$p1dYwU770ZXhI0Jmt0VJYOaWYTJZjQRA1jLHFDUhnSe7NxLgYsAu2', 'student', 'inactive', NULL);

-- ---------- seed: students (22 rows + 1 soft-deleted) ----------
INSERT INTO students (id, user_id, student_code, name, email, phone, course, year, gender, status, created_at) VALUES
(1, 2, 'STU2026001', 'Rahul Sharma', 'rahul.sharma@email.com', '9876543210', 'B.Tech Computer Engineering', 2, 'male', 'active', '2026-06-06 10:00:00'),
(2, 3, 'STU2026002', 'Priya Patel', 'priya.patel@email.com', '9825012345', 'B.Tech Information Technology', 2, 'female', 'active', '2026-06-11 10:00:00'),
(3, 4, 'STU2026003', 'Amit Shah', 'amit.shah@email.com', '9898098980', 'B.Tech Computer Science', 3, 'male', 'active', '2026-06-16 10:00:00'),
(4, NULL, 'STU2026004', 'Neha Verma', 'neha.verma@email.com', '9810011223', 'B.Tech Computer Engineering', 1, 'female', 'active', '2026-06-21 10:00:00'),
(5, NULL, 'STU2026005', 'Karan Mehta', 'karan.mehta@email.com', '9712345678', 'B.Tech Information Technology', 3, 'male', 'active', '2026-06-26 10:00:00'),
(6, NULL, 'STU2026006', 'Sneha Desai', 'sneha.desai@email.com', '9909988776', 'B.Tech Electronics & Communication', 2, 'female', 'active', '2026-07-01 10:00:00'),
(7, NULL, 'STU2026007', 'Vikram Singh', 'vikram.singh@email.com', '9654321870', 'B.Tech Computer Science', 4, 'male', 'active', '2026-07-06 10:00:00'),
(8, NULL, 'STU2026008', 'Anjali Gupta', 'anjali.gupta@email.com', '9876501234', 'B.Tech Computer Engineering', 2, 'female', 'active', '2026-07-11 10:00:00'),
(9, NULL, 'STU2026009', 'Rohan Joshi', 'rohan.joshi@email.com', '9988776655', 'B.Tech Information Technology', 1, 'male', 'active', '2026-07-16 10:00:00'),
(10, NULL, 'STU2026010', 'Pooja Nair', 'pooja.nair@email.com', '9745612380', 'B.Tech Computer Science', 3, 'female', 'active', '2026-07-21 10:00:00'),
(11, NULL, 'STU2026011', 'Arjun Reddy', 'arjun.reddy@email.com', '9848012345', 'B.Tech Electronics & Communication', 4, 'male', 'active', '2026-07-26 10:00:00'),
(12, NULL, 'STU2026012', 'Divya Iyer', 'divya.iyer@email.com', '9444123987', 'B.Tech Computer Engineering', 3, 'female', 'active', '2026-07-31 10:00:00'),
(13, NULL, 'STU2026013', 'Manish Kumar', 'manish.kumar@email.com', '9801234567', 'B.Tech Information Technology', 2, 'male', 'active', '2026-08-05 10:00:00'),
(14, NULL, 'STU2026014', 'Kavya Menon', 'kavya.menon@email.com', '9633012345', 'B.Tech Computer Science', 1, 'female', 'active', '2026-08-10 10:00:00'),
(15, NULL, 'STU2026015', 'Harsh Trivedi', 'harsh.trivedi@email.com', '9724455667', 'B.Tech Computer Engineering', 4, 'male', 'active', '2026-08-15 10:00:00'),
(16, NULL, 'STU2026016', 'Ishita Bansal', 'ishita.bansal@email.com', '9815566778', 'B.Tech Electronics & Communication', 1, 'female', 'inactive', '2026-08-20 10:00:00'),
(17, NULL, 'STU2026017', 'Nikhil Chauhan', 'nikhil.chauhan@email.com', '9990011223', 'B.Tech Information Technology', 3, 'male', 'active', '2026-08-25 10:00:00'),
(18, NULL, 'STU2026018', 'Meera Kapoor', 'meera.kapoor@email.com', '9871234500', 'B.Tech Computer Science', 2, 'female', 'active', '2026-08-30 10:00:00'),
(19, NULL, 'STU2026019', 'Sahil Ansari', 'sahil.ansari@email.com', '9936677889', 'B.Tech Computer Engineering', 2, 'male', 'active', '2026-09-04 10:00:00'),
(20, NULL, 'STU2026020', 'Tanvi Kulkarni', 'tanvi.kulkarni@email.com', '9822334455', 'B.Tech Information Technology', 4, 'female', 'active', '2026-09-09 10:00:00'),
(21, NULL, 'STU2026021', 'Yash Parmar', 'yash.parmar@email.com', '9687766554', 'B.Tech Computer Science', 1, 'male', 'active', '2026-09-14 10:00:00'),
(22, NULL, 'STU2026022', 'Riya Saxena', 'riya.saxena@email.com', '9455667788', 'B.Tech Electronics & Communication', 3, 'female', 'active', '2026-09-19 10:00:00'),
(23, NULL, 'STU2026023', 'Deepak Yadav', 'deepak.yadav@email.com', '9532211009', 'B.Tech Computer Engineering', 1, 'male', 'deleted', '2026-09-24 10:00:00');

-- ---------- seed: events (same 15 records as data/events.json) ----------
INSERT INTO events (id, title, description, event_date, location, category, status, poster, created_by) VALUES
(1, 'Mid-Semester Examination', 'Official examination. Carry your ID card and arrive 30 minutes early.', '2026-10-15', 'Block A', 'Exam', 'closed', NULL, 1),
(2, 'Web Development Workshop', 'Hands-on workshop. Bring your laptop; installation help is provided on site.', '2026-10-18', 'Computer Lab 1', 'Workshop', 'open', NULL, 1),
(3, 'Sports Day', 'Inter-department sports activities open to all students.', '2026-10-20', 'College Ground', 'Sports', 'open', NULL, 1),
(4, 'Java Programming Seminar', 'Expert-led seminar followed by a Q&A session.', '2026-10-22', 'Seminar Hall', 'Seminar', 'open', NULL, 1),
(5, 'Project Submission', 'Mandatory academic activity. Check the department notice board for details.', '2026-10-25', 'Department Office', 'Academic', 'open', NULL, 1),
(6, 'Coding Competition', 'Team competition with prizes for the top three teams.', '2026-10-28', 'Computer Lab 2', 'Competition', 'open', NULL, 1),
(7, 'Career Guidance Session', 'Expert-led seminar followed by a Q&A session.', '2026-11-01', 'Seminar Hall', 'Seminar', 'open', NULL, 1),
(8, 'Cultural Festival', 'Annual cultural programme with music, dance and drama performances.', '2026-11-05', 'College Auditorium', 'Cultural', 'open', NULL, 1),
(9, 'Database Workshop', 'Hands-on workshop. Bring your laptop; installation help is provided on site.', '2026-11-08', 'Computer Lab 1', 'Workshop', 'open', NULL, 1),
(10, 'Internal Assessment', 'Official examination. Carry your ID card and arrive 30 minutes early.', '2026-11-10', 'Block B', 'Exam', 'open', NULL, 1),
(11, 'Hackathon', 'Team competition with prizes for the top three teams.', '2026-11-15', 'Innovation Lab', 'Competition', 'open', NULL, 1),
(12, 'Computer Networks Seminar', 'Expert-led seminar followed by a Q&A session.', '2026-11-18', 'Seminar Hall', 'Seminar', 'open', NULL, 1),
(13, 'Annual Sports Meet', 'Inter-department sports activities open to all students.', '2026-11-22', 'College Ground', 'Sports', 'open', NULL, 1),
(14, 'Final Project Presentation', 'Mandatory academic activity. Check the department notice board for details.', '2026-11-25', 'Block A', 'Academic', 'open', NULL, 1),
(15, 'Semester End Examination', 'Official examination. Carry your ID card and arrive 30 minutes early.', '2026-12-01', 'Main Building', 'Exam', 'open', NULL, 1);

-- ---------- seed: registrations ----------
INSERT INTO registrations (student_id, event_id, registered_at) VALUES
(1, 15, '2026-07-15 14:36:00'),
(3, 8, '2026-09-09 16:33:00'),
(3, 13, '2026-05-16 17:14:00'),
(4, 5, '2026-06-12 08:45:00'),
(4, 6, '2026-09-20 18:06:00'),
(4, 7, '2026-10-16 14:30:00'),
(4, 8, '2026-06-15 15:56:00'),
(4, 9, '2026-07-07 09:40:00'),
(8, 2, '2026-09-03 18:30:00'),
(10, 6, '2026-09-26 12:35:00'),
(11, 3, '2026-08-20 18:19:00'),
(11, 5, '2026-09-27 20:41:00'),
(12, 7, '2026-09-15 19:30:00'),
(13, 6, '2026-08-20 14:12:00'),
(13, 10, '2026-07-24 12:00:00'),
(13, 13, '2026-05-18 10:41:00'),
(15, 4, '2026-10-09 15:51:00'),
(15, 8, '2026-09-20 15:23:00'),
(16, 12, '2026-06-30 12:44:00'),
(16, 14, '2026-09-11 15:32:00'),
(16, 15, '2026-09-14 20:27:00'),
(17, 10, '2026-09-29 18:58:00'),
(17, 12, '2026-05-18 12:20:00'),
(17, 14, '2026-09-04 20:06:00'),
(18, 8, '2026-08-30 19:45:00'),
(18, 10, '2026-09-18 12:28:00'),
(19, 6, '2026-06-25 14:53:00'),
(19, 9, '2026-09-09 09:41:00'),
(19, 10, '2026-07-17 15:23:00'),
(20, 3, '2026-06-03 11:47:00'),
(20, 9, '2026-08-23 10:28:00'),
(20, 11, '2026-05-13 09:00:00'),
(21, 13, '2026-09-02 17:34:00'),
(21, 15, '2026-05-31 17:50:00');

-- ---------- seed: audit log ----------
INSERT INTO audit_logs (user_id, action, details, ip_address, created_at) VALUES
(1, 'login', 'Admin logged in', '127.0.0.1', '2026-10-01 09:00:00'),
(1, 'event_create', 'Created event #15 Semester End Examination', '127.0.0.1', '2026-10-01 09:10:00'),
(2, 'register', 'New student account created', '127.0.0.1', '2026-10-02 11:30:00'),
(1, 'student_delete', 'Soft-deleted student #23 Deepak Yadav', '127.0.0.1', '2026-10-03 14:05:00');

-- ---------- Advanced (Practical 8): stored procedure ----------
-- Used by test_connection.php to show dashboard counts from the DB itself.
DROP PROCEDURE IF EXISTS sp_dashboard_stats;
DELIMITER $$
CREATE PROCEDURE sp_dashboard_stats()
BEGIN
  SELECT
    (SELECT COUNT(*) FROM users)                              AS total_users,
    (SELECT COUNT(*) FROM students WHERE status <> 'deleted') AS total_students,
    (SELECT COUNT(*) FROM events)                             AS total_events,
    (SELECT COUNT(*) FROM registrations)                      AS total_registrations;
END$$
DELIMITER ;
