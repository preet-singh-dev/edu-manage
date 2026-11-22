-- ============================================================================
-- EduManage - Student Management System
-- Database Schema & Sample Data (MySQL)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `student_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `student_db`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `fees`;
DROP TABLE IF EXISTS `marks`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `courses`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. Users Table (Admin & Teacher roles)
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'teacher') NOT NULL DEFAULT 'teacher',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Courses Table
-- ----------------------------------------------------------------------------
CREATE TABLE `courses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_name` VARCHAR(120) NOT NULL,
  `course_code` VARCHAR(30) NOT NULL,
  `duration` VARCHAR(50) NOT NULL,
  `fees` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_courses_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Students Table
-- ----------------------------------------------------------------------------
CREATE TABLE `students` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `roll_no` VARCHAR(30) NOT NULL,
  `full_name` VARCHAR(120) NOT NULL,
  `father_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
  `dob` DATE NOT NULL,
  `address` TEXT NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `admission_date` DATE NOT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_students_roll_no` (`roll_no`),
  UNIQUE KEY `uq_students_email` (`email`),
  KEY `idx_students_course` (`course_id`),
  CONSTRAINT `fk_students_course`
    FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Attendance Table
-- ----------------------------------------------------------------------------
CREATE TABLE `attendance` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` ENUM('Present', 'Absent') NOT NULL,
  `marked_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendance_student_date` (`student_id`, `attendance_date`),
  KEY `idx_attendance_course_date` (`course_id`, `attendance_date`),
  CONSTRAINT `fk_attendance_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_course`
    FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_attendance_user`
    FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Marks Table
-- ----------------------------------------------------------------------------
CREATE TABLE `marks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `subject` VARCHAR(100) NOT NULL,
  `exam_name` VARCHAR(100) NOT NULL,
  `max_marks` INT UNSIGNED NOT NULL DEFAULT 100,
  `marks_obtained` DECIMAL(5, 2) NOT NULL,
  `percentage` DECIMAL(5, 2) NOT NULL,
  `grade` VARCHAR(5) NOT NULL,
  `result_status` ENUM('Pass', 'Fail') NOT NULL,
  `added_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_marks_student` (`student_id`),
  CONSTRAINT `fk_marks_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_marks_user`
    FOREIGN KEY (`added_by`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Fees Table
-- ----------------------------------------------------------------------------
CREATE TABLE `fees` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `receipt_no` VARCHAR(40) NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_date` DATE NOT NULL,
  `payment_mode` ENUM('Cash', 'Card', 'Bank Transfer', 'UPI', 'Cheque') NOT NULL DEFAULT 'Cash',
  `remarks` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fees_receipt_no` (`receipt_no`),
  KEY `idx_fees_student` (`student_id`),
  CONSTRAINT `fk_fees_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SAMPLE DATA
-- ============================================================================

-- Default Users:
-- Admin:   username = "admin",    password = "admin123"
-- Teacher: username = "teacher1", password = "teacher123"
-- Teacher: username = "teacher2", password = "teacher123"
INSERT INTO `users` (`id`, `username`, `full_name`, `email`, `password`, `role`) VALUES
(1, 'admin',    'Principal Administrator', 'admin@edumanage.edu',    '$2y$10$tt.eoVfLLEOIGblJgzpDE.2C9w2t6v4bwb02a0Co8QtqusrAZdMly', 'admin'),
(2, 'teacher1', 'Prof. Eleanor Vance',     'eleanor@edumanage.edu',  '$2y$10$vnhJn6/oHyh/T5U2zKb2ieYOE2colyxYFMjwItaHFHa7lTygRwq9S', 'teacher'),
(3, 'teacher2', 'Prof. Marcus Sterling',   'marcus@edumanage.edu',   '$2y$10$vnhJn6/oHyh/T5U2zKb2ieYOE2colyxYFMjwItaHFHa7lTygRwq9S', 'teacher');

-- Sample Courses (3)
INSERT INTO `courses` (`id`, `course_name`, `course_code`, `duration`, `fees`) VALUES
(1, 'B.Sc Computer Science', 'CS101',  '3 Years', 45000.00),
(2, 'B.Com Honors',          'COM201', '3 Years', 32000.00),
(3, 'MBA Finance',           'MBA301', '2 Years', 75000.00);

-- Sample Students (10)
INSERT INTO `students` (`id`, `roll_no`, `full_name`, `father_name`, `email`, `phone`, `gender`, `dob`, `address`, `course_id`, `admission_date`, `photo`, `status`) VALUES
(1,  'EDU-2026-001', 'Aarav Sharma',      'Rajesh Sharma',     'aarav.sharma@student.edu',    '9876543210', 'Male',   '2005-03-14', '42 Royal Oak Avenue, New Delhi',       1, '2026-07-01', NULL, 'Active'),
(2,  'EDU-2026-002', 'Priya Nair',        'Suresh Nair',       'priya.nair@student.edu',      '9876543211', 'Female', '2005-08-22', '18 Sapphire Lane, Kochi',              1, '2026-07-02', NULL, 'Active'),
(3,  'EDU-2026-003', 'Rohan Verma',       'Anil Verma',        'rohan.verma@student.edu',     '9876543212', 'Male',   '2004-11-05', '105 Heritage Park, Jaipur',            1, '2026-07-03', NULL, 'Active'),
(4,  'EDU-2026-004', 'Ananya Patel',      'Kiran Patel',       'ananya.patel@student.edu',    '9876543213', 'Female', '2005-01-19', '77 Crescent Road, Ahmedabad',          1, '2026-07-04', NULL, 'Active'),
(5,  'EDU-2026-005', 'Kabir Mehta',       'Vikram Mehta',      'kabir.mehta@student.edu',     '9876543214', 'Male',   '2005-06-30', '29 Crown Colony, Mumbai',              2, '2026-07-05', NULL, 'Active'),
(6,  'EDU-2026-006', 'Sneha Iyer',        'Ramesh Iyer',       'sneha.iyer@student.edu',      '9876543215', 'Female', '2005-09-12', '64 Palm Grove, Chennai',               2, '2026-07-06', NULL, 'Active'),
(7,  'EDU-2026-007', 'Devansh Kulkarni',  'Prakash Kulkarni',  'devansh.k@student.edu',       '9876543216', 'Male',   '2004-12-27', '12 Ivory Towers, Pune',                2, '2026-07-07', NULL, 'Inactive'),
(8,  'EDU-2026-008', 'Meera Krishnan',    'Venkat Krishnan',   'meera.k@student.edu',         '9876543217', 'Female', '2002-04-16', '88 University Enclave, Bengaluru',     3, '2026-07-08', NULL, 'Active'),
(9,  'EDU-2026-009', 'Aditya Malhotra',   'Sanjay Malhotra',   'aditya.m@student.edu',        '9876543218', 'Male',   '2002-10-08', '53 Regal Estate, Chandigarh',          3, '2026-07-09', NULL, 'Active'),
(10, 'EDU-2026-010', 'Zoya Khan',         'Imran Khan',        'zoya.khan@student.edu',       '9876543219', 'Female', '2003-02-24', '19 Marina Heights, Hyderabad',         3, '2026-07-10', NULL, 'Active');

-- Sample Attendance (Today + Previous Day)
INSERT INTO `attendance` (`student_id`, `course_id`, `attendance_date`, `status`, `marked_by`) VALUES
(1, 1, CURDATE(), 'Present', 2),
(2, 1, CURDATE(), 'Present', 2),
(3, 1, CURDATE(), 'Absent',  2),
(4, 1, CURDATE(), 'Present', 2),
(5, 2, CURDATE(), 'Present', 3),
(6, 2, CURDATE(), 'Present', 3),
(8, 3, CURDATE(), 'Present', 2),
(9, 3, CURDATE(), 'Absent',  2),
(10,3, CURDATE(), 'Present', 2),
(1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present', 2),
(2, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present', 2),
(3, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Present', 2),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Absent',  2);

-- Sample Marks
INSERT INTO `marks` (`student_id`, `subject`, `exam_name`, `max_marks`, `marks_obtained`, `percentage`, `grade`, `result_status`, `added_by`) VALUES
(1, 'Data Structures',      'Mid-Term 2026', 100, 88.00, 88.00, 'A',  'Pass', 2),
(1, 'Database Systems',     'Mid-Term 2026', 100, 92.00, 92.00, 'A+', 'Pass', 2),
(1, 'Web Technologies',     'Mid-Term 2026', 100, 85.00, 85.00, 'A',  'Pass', 2),
(2, 'Data Structures',      'Mid-Term 2026', 100, 76.00, 76.00, 'B+', 'Pass', 2),
(2, 'Database Systems',     'Mid-Term 2026', 100, 81.00, 81.00, 'A',  'Pass', 2),
(5, 'Financial Accounting', 'Mid-Term 2026', 100, 68.00, 68.00, 'B',  'Pass', 3),
(8, 'Corporate Finance',    'Mid-Term 2026', 100, 94.00, 94.00, 'A+', 'Pass', 2),
(3, 'Data Structures',      'Mid-Term 2026', 100, 34.00, 34.00, 'F',  'Fail', 2);

-- Sample Fees
INSERT INTO `fees` (`receipt_no`, `student_id`, `amount`, `payment_date`, `payment_mode`, `remarks`) VALUES
('REC-20260701-1001', 1, 25000.00, '2026-07-01', 'Bank Transfer', 'First semester tuition installment'),
('REC-20260702-1002', 2, 45000.00, '2026-07-02', 'UPI',           'Full annual course fee'),
('REC-20260705-1003', 5, 20000.00, '2026-07-05', 'Card',          'Admission & tuition partial fee'),
('REC-20260708-1004', 8, 50000.00, '2026-07-08', 'Bank Transfer', 'MBA Semester 1 & 2 fee'),
('REC-20260710-1005', 9, 35000.00, '2026-07-10', 'Cash',          'Initial admission installment');
