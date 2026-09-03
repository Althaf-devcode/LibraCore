-- =============================================================
--  LibraCore - Library Management System
--  Database schema and demo data
--
--  Import options:
--    phpMyAdmin : select this file on the Import tab
--    CLI        : mysql -u root < libracore.sql
-- =============================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `libracore`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `libracore`;

DROP TABLE IF EXISTS `borrow_records`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `admins`;

-- -------------------------------------------------------------
-- Table: admins
-- -------------------------------------------------------------
CREATE TABLE `admins` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50)  NOT NULL,
  `email`      VARCHAR(120) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`),
  UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table: students
-- -------------------------------------------------------------
CREATE TABLE `students` (
  `id`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `student_id` VARCHAR(30)      NOT NULL,
  `name`       VARCHAR(100)     NOT NULL,
  `email`      VARCHAR(120)     NOT NULL,
  `phone`      VARCHAR(25)      NOT NULL,
  `address`    VARCHAR(255)     NOT NULL,
  `department` VARCHAR(100)     NOT NULL,
  `year`       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_students_student_id` (`student_id`),
  KEY `idx_students_name` (`name`),
  KEY `idx_students_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table: books
-- -------------------------------------------------------------
CREATE TABLE `books` (
  `id`                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `isbn`               VARCHAR(20)      NOT NULL,
  `title`              VARCHAR(200)     NOT NULL,
  `author`             VARCHAR(150)     NOT NULL,
  `category`           VARCHAR(80)      NOT NULL,
  `publisher`          VARCHAR(120)              DEFAULT NULL,
  `publication_year`   SMALLINT UNSIGNED         DEFAULT NULL,
  `quantity`           INT UNSIGNED     NOT NULL DEFAULT 1,
  `available_quantity` INT UNSIGNED     NOT NULL DEFAULT 1,
  `shelf_location`     VARCHAR(30)      NOT NULL,
  `created_at`         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_books_isbn` (`isbn`),
  KEY `idx_books_title` (`title`),
  KEY `idx_books_category` (`category`),
  CONSTRAINT `chk_books_available_range` CHECK (`available_quantity` <= `quantity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- Table: borrow_records
-- -------------------------------------------------------------
CREATE TABLE `borrow_records` (
  `id`          INT UNSIGNED                    NOT NULL AUTO_INCREMENT,
  `student_id`  INT UNSIGNED                    NOT NULL,
  `book_id`     INT UNSIGNED                    NOT NULL,
  `borrow_date` DATE                            NOT NULL,
  `due_date`    DATE                            NOT NULL,
  `return_date` DATE                                     DEFAULT NULL,
  `status`      ENUM('Borrowed','Returned')     NOT NULL DEFAULT 'Borrowed',
  `remarks`     VARCHAR(255)                             DEFAULT NULL,
  `created_at`  TIMESTAMP                       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_br_student` (`student_id`),
  KEY `idx_br_book` (`book_id`),
  KEY `idx_br_status` (`status`),
  KEY `idx_br_due_date` (`due_date`),
  KEY `idx_br_return_date` (`return_date`),
  CONSTRAINT `fk_br_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`)
      ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_br_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`)
      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- Demo data: admin account
-- Default login -> username: admin | password: admin123
-- -------------------------------------------------------------
INSERT INTO `admins` (`username`, `email`, `password`, `created_at`) VALUES
('admin', 'admin@libracore.com', '$2y$10$O0ju/wnddNuKlZ2qMr/qRu3sU54JwVK1HddTqvuvT8nigKoQBX5QC', '2026-05-01 08:00:00');

-- -------------------------------------------------------------
-- Demo data: students
-- -------------------------------------------------------------
INSERT INTO `students`
(`id`, `student_id`, `name`, `email`, `phone`, `address`, `department`, `year`, `created_at`, `updated_at`) VALUES
(1,  'STU-1001', 'Alice Johnson',   'alice.johnson@student.libracore.edu',   '+94 77 152 2552', '12 Maple Street, Springfield',    'Computer Science',         3, '2026-05-04 09:15:00', '2026-05-04 09:15:00'),
(2,  'STU-1002', 'Rahul Sharma',    'rahul.sharma@student.libracore.edu',    '+94 77 252 7552', '88 Lake Avenue, Riverton',        'Information Technology',   1, '2026-05-06 10:30:00', '2026-05-06 10:30:00'),
(3,  'STU-1003', 'Emily Chen',      'emily.chen@student.libracore.edu',      '+94 75 453 8555', '45 Cedar Lane, Fairview',         'Mathematics',              2, '2026-05-09 11:45:00', '2026-05-09 11:45:00'),
(4,  'STU-1004', 'Michael Okafor',  'michael.okafor@student.libracore.edu',  '+94 72 332 2542', '230 Elm Road, Bridgeport',        'Electrical Engineering',   4, '2026-05-12 14:20:00', '2026-05-12 14:20:00'),
(5,  'STU-1005', 'Sofia Ramirez',   'sofia.ramirez@student.libracore.edu',   '+94 75 952 8222', '17 Birch Court, Oakdale',         'Physics',                  1, '2026-05-15 09:50:00', '2026-05-15 09:50:00'),
(6,  'STU-1006', 'David Kim',       'david.kim@student.libracore.edu',       '+94 77 652 6773', '301 Willow Way, Brookside',       'Business Administration',  2, '2026-05-18 13:05:00', '2026-05-18 13:05:00'),
(7,  'STU-1007', 'Fatima Noor',     'fatima.noor@student.libracore.edu',     '+94 72 342 5556', '74 Aspen Drive, Hillcrest',       'Computer Science',         4, '2026-05-22 10:10:00', '2026-05-22 10:10:00'),
(8,  'STU-1008', "Liam O'Brien",    'liam.obrien@student.libracore.edu',     '+94 77 739 9995', '5 Chestnut Plaza, Lakeside',      'Mechanical Engineering',   3, '2026-05-27 15:35:00', '2026-05-27 15:35:00'),
(9,  'STU-1009', 'Priya Patel',     'priya.patel@student.libracore.edu',     '+94 77 656 7894', '129 Sycamore Street, Greenfield', 'Information Technology',   2, '2026-06-02 09:25:00', '2026-06-02 09:25:00'),
(10, 'STU-1010', 'Ethan Walsh',     'ethan.walsh@student.libracore.edu',     '+94 75 555 2355', '62 Poplar Bend, Stonebridge',     'Electronics',              1, '2026-06-08 11:55:00', '2026-06-08 11:55:00');

-- -------------------------------------------------------------
-- Demo data: books
-- available_quantity already reflects the active loans below.
-- -------------------------------------------------------------
INSERT INTO `books`
(`id`, `isbn`, `title`, `author`, `category`, `publisher`, `publication_year`, `quantity`, `available_quantity`, `shelf_location`, `created_at`, `updated_at`) VALUES
(1,  '9780132350884', 'Clean Code',                              'Robert C. Martin',        'Programming',           'Prentice Hall',        2008, 5, 4, 'L1-A1', '2026-05-05 09:00:00', '2026-05-05 09:00:00'),
(2,  '9780262033848', 'Introduction to Algorithms',              'Thomas H. Cormen',        'Algorithms',            'MIT Press',            2009, 4, 3, 'L1-B2', '2026-05-07 10:20:00', '2026-05-07 10:20:00'),
(3,  '9780073523323', 'Database System Concepts',                'Abraham Silberschatz',    'Databases',             'McGraw-Hill',          2019, 4, 3, 'L1-C3', '2026-05-10 11:40:00', '2026-05-10 11:40:00'),
(4,  '9781118063330', 'Operating System Concepts',               'Abraham Silberschatz',    'Operating Systems',     'Wiley',                2012, 3, 2, 'L2-A1', '2026-05-13 13:10:00', '2026-05-13 13:10:00'),
(5,  '9780132126953', 'Computer Networks',                       'Andrew S. Tanenbaum',     'Networking',            'Prentice Hall',        2011, 3, 2, 'L2-B2', '2026-05-16 14:30:00', '2026-05-16 14:30:00'),
(6,  '9780134610993', 'Artificial Intelligence: A Modern Approach', 'Stuart Russell',       'Artificial Intelligence', 'Pearson',            2020, 3, 2, 'L2-C1', '2026-05-19 09:45:00', '2026-05-19 09:45:00'),
(7,  '9780073383095', 'Discrete Mathematics and Its Applications', 'Kenneth H. Rosen',      'Mathematics',           'McGraw-Hill',          2018, 5, 4, 'L3-A2', '2026-05-23 10:55:00', '2026-05-23 10:55:00'),
(8,  '9780596009205', 'Head First Java',                         'Kathy Sierra',            'Programming',           'O''Reilly Media',      2005, 4, 3, 'L3-B3', '2026-05-26 12:15:00', '2026-05-26 12:15:00'),
(9,  '9780135957059', 'The Pragmatic Programmer',                'Andrew Hunt',             'Software Engineering',  'Addison-Wesley',       2019, 3, 2, 'L3-C2', '2026-06-01 13:25:00', '2026-06-01 13:25:00'),
(10, '9780078022128', 'Software Engineering: A Practitioner''s Approach', 'Roger S. Pressman', 'Software Engineering', 'McGraw-Hill',     2014, 4, 3, 'L4-A1', '2026-06-05 14:45:00', '2026-06-05 14:45:00');

-- -------------------------------------------------------------
-- Demo data: borrow records
-- Records 1-10 are still out (4 of them overdue), 11-18 returned.
-- -------------------------------------------------------------
INSERT INTO `borrow_records`
(`student_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `status`, `remarks`, `created_at`) VALUES

(1,  1,  '2026-08-01', '2026-08-15', NULL,         'Borrowed', 'Preparing for midterm project.',        '2026-08-01 09:12:00'),
(2,  3,  '2026-08-10', '2026-08-24', NULL,         'Borrowed', NULL,                                    '2026-08-10 10:05:00'),
(3,  2,  '2026-07-28', '2026-08-11', NULL,         'Borrowed', 'Requested by faculty.',                 '2026-07-28 11:40:00'),
(4,  8,  '2026-08-18', '2026-09-01', NULL,         'Borrowed', NULL,                                    '2026-08-18 14:22:00'),
(5,  6,  '2026-08-05', '2026-08-19', NULL,         'Borrowed', 'AI assignment reference.',              '2026-08-05 09:58:00'),
(6,  7,  '2026-08-20', '2026-09-03', NULL,         'Borrowed', NULL,                                    '2026-08-20 13:31:00'),
(7,  10, '2026-08-02', '2026-08-16', NULL,         'Borrowed', 'Capstone project research.',            '2026-08-02 15:47:00'),
(8,  5,  '2026-08-15', '2026-08-29', NULL,         'Borrowed', NULL,                                    '2026-08-15 10:18:00'),
(9,  9,  '2026-08-21', '2026-09-04', NULL,         'Borrowed', 'Recommended by senior.',                '2026-08-21 09:36:00'),
(10, 4,  '2026-08-22', '2026-09-05', NULL,         'Borrowed', NULL,                                    '2026-08-22 12:52:00'),

(2,  1,  '2026-06-01', '2026-06-15', '2026-06-13', 'Returned', 'Returned in good condition.',           '2026-06-01 10:00:00'),
(3,  1,  '2026-06-20', '2026-07-04', '2026-07-10', 'Returned', 'Returned 6 days late.',                 '2026-06-20 11:30:00'),
(4,  3,  '2026-07-01', '2026-07-15', '2026-07-15', 'Returned', NULL,                                    '2026-07-01 09:20:00'),
(5,  2,  '2026-07-03', '2026-07-17', '2026-07-16', 'Returned', NULL,                                    '2026-07-03 13:45:00'),
(6,  8,  '2026-07-05', '2026-07-19', '2026-07-25', 'Returned', 'Returned late after exams.',            '2026-07-05 14:10:00'),
(7,  5,  '2026-07-08', '2026-07-22', '2026-07-21', 'Returned', NULL,                                    '2026-07-08 09:55:00'),
(8,  7,  '2026-07-10', '2026-07-24', '2026-07-24', 'Returned', 'Renewed once during the loan period.',  '2026-07-10 10:40:00'),
(9,  6,  '2026-07-12', '2026-07-26', '2026-08-02', 'Returned', 'Returned 7 days late.',                 '2026-07-12 11:15:00');
