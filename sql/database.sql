-- =========================================================
-- TUTORHUB - TUITION MANAGEMENT SYSTEM
-- Complete Database Schema
-- Import this file through phpMyAdmin (or `mysql -u root -p < database.sql`)
-- =========================================================

DROP DATABASE IF EXISTS tutorhub;
CREATE DATABASE tutorhub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tutorhub;

SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- 1. USERS
-- =========================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30),
    role ENUM('student','tutor','both','admin') NOT NULL DEFAULT 'student',
    gender ENUM('male','female','other') DEFAULT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- =========================================================
-- 2. STUDENT_PROFILES
-- =========================================================
CREATE TABLE student_profiles (
    student_profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    class_level VARCHAR(60),
    institution VARCHAR(150),
    guardian_name VARCHAR(120),
    guardian_phone VARCHAR(30),
    address VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sp_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 3. TUTOR_PROFILES
-- =========================================================
CREATE TABLE tutor_profiles (
    tutor_profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    education VARCHAR(255),
    institution VARCHAR(150),
    experience_years DECIMAL(4,1) DEFAULT 0,
    expected_salary DECIMAL(10,2) DEFAULT 0,
    teaching_location VARCHAR(150),
    teaching_mode ENUM('online','offline','both') DEFAULT 'both',
    bio TEXT,
    is_profile_verified TINYINT(1) NOT NULL DEFAULT 0,
    avg_rating DECIMAL(3,2) DEFAULT 0,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tp_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_tp_rating (avg_rating),
    INDEX idx_tp_location (teaching_location)
) ENGINE=InnoDB;

-- =========================================================
-- 4. SUBJECTS
-- =========================================================
CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(80),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tutor <-> Subject (which subjects a tutor teaches)
CREATE TABLE tutor_subjects (
    tutor_subject_id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_profile_id INT NOT NULL,
    subject_id INT NOT NULL,
    CONSTRAINT fk_ts_tutor FOREIGN KEY (tutor_profile_id) REFERENCES tutor_profiles(tutor_profile_id) ON DELETE CASCADE,
    CONSTRAINT fk_ts_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    UNIQUE KEY uq_tutor_subject (tutor_profile_id, subject_id)
) ENGINE=InnoDB;

-- =========================================================
-- 5. TUITION_POSTS (posted by students - a requirement)
-- =========================================================
CREATE TABLE tuition_posts (
    tuition_post_id INT AUTO_INCREMENT PRIMARY KEY,
    student_profile_id INT NOT NULL,
    subject_id INT NOT NULL,
    title VARCHAR(160) NOT NULL,
    class_level VARCHAR(60),
    location VARCHAR(150),
    budget_min DECIMAL(10,2) DEFAULT 0,
    budget_max DECIMAL(10,2) DEFAULT 0,
    preferred_days VARCHAR(100),
    preferred_time VARCHAR(60),
    mode ENUM('online','offline','both') DEFAULT 'offline',
    gender_preference ENUM('any','male','female') DEFAULT 'any',
    description TEXT,
    status ENUM('open','in_progress','completed','closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tup_student FOREIGN KEY (student_profile_id) REFERENCES student_profiles(student_profile_id) ON DELETE CASCADE,
    CONSTRAINT fk_tup_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE RESTRICT,
    INDEX idx_tup_status (status),
    INDEX idx_tup_location (location),
    INDEX idx_tup_budget (budget_min, budget_max),
    INDEX idx_tup_mode (mode)
) ENGINE=InnoDB;

-- =========================================================
-- 6. TUTOR_POSTS (posted by tutors advertising availability)
-- =========================================================
CREATE TABLE tutor_posts (
    tutor_post_id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_profile_id INT NOT NULL,
    subject_id INT NOT NULL,
    title VARCHAR(160) NOT NULL,
    class_level VARCHAR(60),
    location VARCHAR(150),
    rate DECIMAL(10,2) DEFAULT 0,
    mode ENUM('online','offline','both') DEFAULT 'both',
    description TEXT,
    status ENUM('active','closed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_top_tutor FOREIGN KEY (tutor_profile_id) REFERENCES tutor_profiles(tutor_profile_id) ON DELETE CASCADE,
    CONSTRAINT fk_top_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE RESTRICT,
    INDEX idx_top_status (status),
    INDEX idx_top_location (location)
) ENGINE=InnoDB;

-- =========================================================
-- 7. APPLICATIONS (tutor applies to a tuition_post)
-- =========================================================
CREATE TABLE applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    tuition_post_id INT NOT NULL,
    tutor_profile_id INT NOT NULL,
    message TEXT,
    proposed_salary DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending','accepted','rejected','withdrawn') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_app_post FOREIGN KEY (tuition_post_id) REFERENCES tuition_posts(tuition_post_id) ON DELETE CASCADE,
    CONSTRAINT fk_app_tutor FOREIGN KEY (tutor_profile_id) REFERENCES tutor_profiles(tutor_profile_id) ON DELETE CASCADE,
    UNIQUE KEY uq_post_tutor (tuition_post_id, tutor_profile_id),
    INDEX idx_app_status (status)
) ENGINE=InnoDB;

-- =========================================================
-- 8. CONVERSATIONS
-- =========================================================
CREATE TABLE conversations (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT DEFAULT NULL,
    student_user_id INT NOT NULL,
    tutor_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_conv_app FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE SET NULL,
    CONSTRAINT fk_conv_student FOREIGN KEY (student_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_tutor FOREIGN KEY (tutor_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_conversation_pair (application_id, student_user_id, tutor_user_id)
) ENGINE=InnoDB;

-- =========================================================
-- 9. MESSAGES
-- =========================================================
CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_user_id INT NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_sender FOREIGN KEY (sender_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_msg_conv (conversation_id)
) ENGINE=InnoDB;

-- =========================================================
-- 10. FAVORITES
-- =========================================================
CREATE TABLE favorites (
    favorite_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tuition_post_id INT DEFAULT NULL,
    tutor_profile_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_post FOREIGN KEY (tuition_post_id) REFERENCES tuition_posts(tuition_post_id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_tutor FOREIGN KEY (tutor_profile_id) REFERENCES tutor_profiles(tutor_profile_id) ON DELETE CASCADE,
    UNIQUE KEY uq_fav_post (user_id, tuition_post_id),
    UNIQUE KEY uq_fav_tutor (user_id, tutor_profile_id)
) ENGINE=InnoDB;

-- =========================================================
-- 11. NOTIFICATIONS
-- =========================================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(160) NOT NULL,
    message TEXT,
    related_url VARCHAR(255),
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_notif_user_read (user_id, is_read)
) ENGINE=InnoDB;

-- =========================================================
-- 12. REVIEWS
-- =========================================================
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    tuition_schedule_ref INT DEFAULT NULL,
    tuition_post_id INT NOT NULL,
    reviewer_user_id INT NOT NULL,
    reviewee_user_id INT NOT NULL,
    rating TINYINT NOT NULL,
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rev_post FOREIGN KEY (tuition_post_id) REFERENCES tuition_posts(tuition_post_id) ON DELETE CASCADE,
    CONSTRAINT fk_rev_reviewer FOREIGN KEY (reviewer_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_rev_reviewee FOREIGN KEY (reviewee_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5),
    UNIQUE KEY uq_review_once (tuition_post_id, reviewer_user_id, reviewee_user_id)
) ENGINE=InnoDB;

-- =========================================================
-- 13. TUTOR_AVAILABILITY
-- =========================================================
CREATE TABLE tutor_availability (
    availability_id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_profile_id INT NOT NULL,
    day_of_week ENUM('Mon','Tue','Wed','Thu','Fri','Sat','Sun') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_avail_tutor FOREIGN KEY (tutor_profile_id) REFERENCES tutor_profiles(tutor_profile_id) ON DELETE CASCADE,
    INDEX idx_avail_tutor_day (tutor_profile_id, day_of_week)
) ENGINE=InnoDB;

-- =========================================================
-- 14. TUITION_SCHEDULE
-- =========================================================
CREATE TABLE tuition_schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    tuition_post_id INT NOT NULL,
    student_user_id INT NOT NULL,
    tutor_user_id INT NOT NULL,
    class_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    topic VARCHAR(160),
    status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sched_app FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
    CONSTRAINT fk_sched_post FOREIGN KEY (tuition_post_id) REFERENCES tuition_posts(tuition_post_id) ON DELETE CASCADE,
    CONSTRAINT fk_sched_student FOREIGN KEY (student_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_sched_tutor FOREIGN KEY (tutor_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_sched_date (class_date),
    INDEX idx_sched_status (status)
) ENGINE=InnoDB;

-- =========================================================
-- 15. ATTENDANCE
-- =========================================================
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT NOT NULL,
    status ENUM('present','absent','late') NOT NULL,
    notes VARCHAR(255),
    marked_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_att_schedule FOREIGN KEY (schedule_id) REFERENCES tuition_schedule(schedule_id) ON DELETE CASCADE,
    CONSTRAINT fk_att_marker FOREIGN KEY (marked_by) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_attendance_schedule (schedule_id)
) ENGINE=InnoDB;

-- =========================================================
-- 16. PAYMENTS
-- =========================================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    tuition_post_id INT NOT NULL,
    student_user_id INT NOT NULL,
    tutor_user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    for_month VARCHAR(20) NOT NULL,
    payment_date DATE DEFAULT NULL,
    status ENUM('pending','paid','overdue') NOT NULL DEFAULT 'pending',
    method ENUM('cash','bkash','nagad','bank_transfer','other') DEFAULT 'cash',
    transaction_reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pay_post FOREIGN KEY (tuition_post_id) REFERENCES tuition_posts(tuition_post_id) ON DELETE CASCADE,
    CONSTRAINT fk_pay_student FOREIGN KEY (student_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_pay_tutor FOREIGN KEY (tutor_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_pay_status (status),
    INDEX idx_pay_month (for_month)
) ENGINE=InnoDB;

-- =========================================================
-- 17. REPORTS
-- =========================================================
CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_user_id INT NOT NULL,
    reported_user_id INT DEFAULT NULL,
    related_tuition_post_id INT DEFAULT NULL,
    related_tutor_post_id INT DEFAULT NULL,
    reason VARCHAR(160) NOT NULL,
    description TEXT,
    status ENUM('pending','reviewing','resolved','rejected') NOT NULL DEFAULT 'pending',
    admin_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rep_reporter FOREIGN KEY (reporter_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_rep_reported FOREIGN KEY (reported_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_rep_post FOREIGN KEY (related_tuition_post_id) REFERENCES tuition_posts(tuition_post_id) ON DELETE SET NULL,
    CONSTRAINT fk_rep_tpost FOREIGN KEY (related_tutor_post_id) REFERENCES tutor_posts(tutor_post_id) ON DELETE SET NULL,
    INDEX idx_rep_status (status)
) ENGINE=InnoDB;

-- =========================================================
-- 18. ADMIN_ACTIONS
-- =========================================================
CREATE TABLE admin_actions (
    admin_action_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NOT NULL,
    action_type VARCHAR(80) NOT NULL,
    target_type VARCHAR(50),
    target_id INT,
    details VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admact_user FOREIGN KEY (admin_user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- DEMO DATA
-- =========================================================

-- Demo password for ALL demo accounts below is:  Passw0rd!
-- Hash generated with PHP password_hash('Passw0rd!', PASSWORD_BCRYPT)
INSERT INTO users (full_name, email, password_hash, phone, role, gender, status, is_verified) VALUES
('Ayesha Rahman', 'student@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.YeIiHwqNKKNrCw8kt3vTz6ZWjV9WwbGXW', '01711000001', 'student', 'female', 'active', 1),
('Tanvir Hasan', 'tutor@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.YeIiHwqNKKNrCw8kt3vTz6ZWjV9WwbGXW', '01711000002', 'tutor', 'male', 'active', 1),
('System Admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.YeIiHwqNKKNrCw8kt3vTz6ZWjV9WwbGXW', '01711000003', 'admin', 'other', 'active', 1),
('Nadia Islam', 'nadia.both@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.YeIiHwqNKKNrCw8kt3vTz6ZWjV9WwbGXW', '01711000004', 'both', 'female', 'active', 1);

INSERT INTO subjects (subject_name, category) VALUES
('Mathematics', 'Science'),
('Physics', 'Science'),
('Chemistry', 'Science'),
('English', 'Language'),
('Bangla', 'Language'),
('ICT', 'Technology'),
('Biology', 'Science'),
('Higher Math', 'Science');

INSERT INTO student_profiles (user_id, class_level, institution, guardian_name, guardian_phone, address, bio) VALUES
(1, 'Class 9', 'Chittagong Grammar School', 'Kamal Rahman', '01811000001', 'Panchlaish, Chattogram', 'Looking for a dedicated math and physics tutor.'),
(4, 'Class 8', 'Chittagong Collegiate School', 'Rafiq Islam', '01811000004', 'Agrabad, Chattogram', 'Needs help with English and Bangla.');

INSERT INTO tutor_profiles (user_id, education, institution, experience_years, expected_salary, teaching_location, teaching_mode, bio, is_profile_verified, avg_rating, total_reviews) VALUES
(2, 'BSc in EEE', 'CUET', 3.5, 6000.00, 'Chattogram City', 'both', 'Experienced tutor specializing in Math and Physics for O/A Level and SSC/HSC.', 1, 4.50, 2),
(4, 'BA in English', 'University of Chittagong', 1.0, 4000.00, 'Agrabad, Chattogram', 'offline', 'Passionate about teaching English literature and grammar.', 0, 0.00, 0);

INSERT INTO tutor_subjects (tutor_profile_id, subject_id) VALUES
(1, 1), (1, 2), (2, 4), (2, 5);

INSERT INTO tuition_posts (student_profile_id, subject_id, title, class_level, location, budget_min, budget_max, preferred_days, preferred_time, mode, gender_preference, description, status) VALUES
(1, 1, 'Need Math Tutor for Class 9', 'Class 9', 'Panchlaish, Chattogram', 5000, 7000, 'Sun,Tue,Thu', 'Evening', 'offline', 'any', 'Looking for someone patient who can strengthen algebra and geometry basics.', 'open'),
(2, 4, 'English Tutor Needed for Class 8', 'Class 8', 'Agrabad, Chattogram', 3000, 5000, 'Mon,Wed,Fri', 'Afternoon', 'both', 'female', 'Focus on grammar and spoken English practice.', 'open');

INSERT INTO tutor_posts (tutor_profile_id, subject_id, title, class_level, location, rate, mode, description, status) VALUES
(1, 2, 'Physics Tuition Available - SSC/HSC', 'SSC/HSC', 'Chattogram City', 6000, 'both', 'Concept-based physics teaching with problem solving practice.', 'active');

INSERT INTO applications (tuition_post_id, tutor_profile_id, message, proposed_salary, status) VALUES
(1, 1, 'I have 3+ years experience teaching Class 9 math. I can start this week.', 6000, 'accepted');

INSERT INTO conversations (application_id, student_user_id, tutor_user_id) VALUES
(1, 1, 2);

INSERT INTO messages (conversation_id, sender_user_id, body, is_read) VALUES
(1, 1, 'Hi, thanks for applying! Can you start this Sunday?', 1),
(1, 2, 'Yes, Sunday evening works great for me.', 0);

INSERT INTO favorites (user_id, tuition_post_id, tutor_profile_id) VALUES
(2, 1, NULL),
(1, NULL, 1);

INSERT INTO notifications (user_id, type, title, message, related_url, is_read) VALUES
(2, 'application_accepted', 'Application Accepted', 'Your application for "Need Math Tutor for Class 9" was accepted.', '/tutor/applications.php', 0),
(1, 'new_application', 'New Application Received', 'Tanvir Hasan applied to your tuition post.', '/student/applications.php', 1);

INSERT INTO tutor_availability (tutor_profile_id, day_of_week, start_time, end_time, is_available) VALUES
(1, 'Sun', '17:00:00', '19:00:00', 1),
(1, 'Tue', '17:00:00', '19:00:00', 1),
(1, 'Thu', '17:00:00', '19:00:00', 1);

INSERT INTO tuition_schedule (application_id, tuition_post_id, student_user_id, tutor_user_id, class_date, start_time, end_time, topic, status) VALUES
(1, 1, 1, 2, CURDATE() + INTERVAL 2 DAY, '17:00:00', '19:00:00', 'Algebra Basics', 'scheduled'),
(1, 1, 1, 2, CURDATE() - INTERVAL 5 DAY, '17:00:00', '19:00:00', 'Introduction & Diagnostic Test', 'completed');

INSERT INTO attendance (schedule_id, status, notes, marked_by) VALUES
(2, 'present', 'Engaged well, completed diagnostic test.', 2);

INSERT INTO payments (tuition_post_id, student_user_id, tutor_user_id, amount, for_month, payment_date, status, method, transaction_reference) VALUES
(1, 1, 2, 6000.00, 'September 2026', CURDATE() - INTERVAL 3 DAY, 'paid', 'bkash', 'TXN20260924001'),
(1, 1, 2, 6000.00, 'October 2026', NULL, 'pending', 'cash', NULL);

INSERT INTO reviews (tuition_schedule_ref, tuition_post_id, reviewer_user_id, reviewee_user_id, rating, review_text) VALUES
(2, 1, 1, 2, 5, 'Very patient and explains concepts clearly. Highly recommended!');

INSERT INTO reports (reporter_user_id, reported_user_id, related_tuition_post_id, reason, description, status) VALUES
(2, NULL, 2, 'Suspicious post', 'Budget range seems unrealistic for the requested class level.', 'pending');

INSERT INTO admin_actions (admin_user_id, action_type, target_type, target_id, details) VALUES
(3, 'verify_tutor', 'tutor_profile', 1, 'Verified tutor profile after document check.');
