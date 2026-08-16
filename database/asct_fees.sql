-- database/asct_fees.sql

CREATE DATABASE IF NOT EXISTS asct_fees CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE asct_fees;

-- Admins Table
CREATE TABLE admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Departments Table
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (department_code),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Courses Table
CREATE TABLE courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NOT NULL,
    course_name VARCHAR(150) NOT NULL,
    course_code VARCHAR(30) UNIQUE NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in years',
    total_semesters INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    INDEX idx_department (department_id),
    INDEX idx_code (course_code),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Semesters Table
CREATE TABLE semesters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    semester_number INT NOT NULL,
    semester_name VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_course_semester (course_id, semester_number),
    INDEX idx_course (course_id)
) ENGINE=InnoDB;

-- Students Table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_no VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    mother_name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    mobile VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    state VARCHAR(50) NOT NULL,
    pincode VARCHAR(10) NOT NULL,
    department_id INT NOT NULL,
    course_id INT NOT NULL,
    admission_year YEAR NOT NULL,
    admission_date DATE NOT NULL,
    current_semester INT NOT NULL DEFAULT 1,
    academic_session VARCHAR(20) NOT NULL,
    status ENUM('active', 'inactive', 'passed', 'left') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
    INDEX idx_enrollment (enrollment_no),
    INDEX idx_department (department_id),
    INDEX idx_course (course_id),
    INDEX idx_status (status),
    INDEX idx_mobile (mobile)
) ENGINE=InnoDB;

-- Fee Structures Table
CREATE TABLE fee_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NOT NULL,
    course_id INT NOT NULL,
    semester_id INT NOT NULL,
    academic_session VARCHAR(20) NOT NULL,
    tuition_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    examination_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    development_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    library_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    laboratory_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    other_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    late_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_fee DECIMAL(10,2) GENERATED ALWAYS AS (tuition_fee + examination_fee + development_fee + library_fee + laboratory_fee + other_fee) STORED,
    due_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_fee_structure (course_id, semester_id, academic_session),
    INDEX idx_course (course_id),
    INDEX idx_semester (semester_id),
    INDEX idx_session (academic_session)
) ENGINE=InnoDB;

-- Student Fees Table
CREATE TABLE student_fees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_structure_id INT NOT NULL,
    semester_id INT NOT NULL,
    academic_session VARCHAR(20) NOT NULL,
    total_fee DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    pending_amount DECIMAL(10,2) GENERATED ALWAYS AS (total_fee - paid_amount) STORED,
    due_date DATE,
    status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id) ON DELETE RESTRICT,
    FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_student_semester_fee (student_id, semester_id, academic_session),
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_session (academic_session)
) ENGINE=InnoDB;

-- Payments Table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    student_fee_id INT NOT NULL,
    enrollment_no VARCHAR(50) NOT NULL,
    receipt_no VARCHAR(50) UNIQUE NOT NULL,
    razorpay_order_id VARCHAR(100) UNIQUE,
    razorpay_payment_id VARCHAR(100) UNIQUE,
    razorpay_signature VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'INR',
    payment_method ENUM('razorpay', 'cash', 'bank_transfer', 'other') NOT NULL,
    payment_status ENUM('created', 'pending', 'success', 'failed', 'refunded') DEFAULT 'pending',
    payment_date DATETIME,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (student_fee_id) REFERENCES student_fees(id) ON DELETE RESTRICT,
    INDEX idx_student (student_id),
    INDEX idx_enrollment (enrollment_no),
    INDEX idx_receipt (receipt_no),
    INDEX idx_razorpay_order (razorpay_order_id),
    INDEX idx_razorpay_payment (razorpay_payment_id),
    INDEX idx_status (payment_status),
    INDEX idx_date (payment_date)
) ENGINE=InnoDB;

-- Admin Payment Logs Table (Manual/Offline Payments)
CREATE TABLE admin_payment_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    student_fee_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_mode ENUM('cash', 'bank_transfer', 'cheque', 'other') NOT NULL,
    reference_no VARCHAR(100),
    payment_date DATE NOT NULL,
    remarks TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (student_fee_id) REFERENCES student_fees(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES admins(id),
    INDEX idx_student (student_id),
    INDEX idx_reference (reference_no)
) ENGINE=InnoDB;

-- Settings Table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert Default Admin (Username: admin, Password: Admin@123)
INSERT INTO admins (name, username, email, password) VALUES
('System Administrator', 'admin', 'admin@asct.edu.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert Default Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('college_name', 'All Saints College Of Technology'),
('college_short_name', 'ASCT'),
('college_address', 'Bhopal, Madhya Pradesh, India'),
('college_phone', '+91-755-XXXXXXX'),
('college_email', 'info@asct.edu.in'),
('college_website', 'www.asct.edu.in'),
('razorpay_key_id', ''),
('razorpay_key_secret', ''),
('currency', 'INR'),
('receipt_prefix', 'ASCT'),
('current_academic_session', '2025-26');

-- Insert Default Departments
INSERT INTO departments (department_name, department_code, description) VALUES
('Diploma', 'DIP', 'Diploma Programs'),
('Bachelor of Technology', 'BTECH', 'B.Tech Programs'),
('Master of Technology', 'MTECH', 'M.Tech Programs');

-- Insert Sample Courses
INSERT INTO courses (department_id, course_name, course_code, duration, total_semesters) VALUES
(1, 'Diploma in Computer Science', 'DCS', 3, 6),
(1, 'Diploma in Mechanical Engineering', 'DME', 3, 6),
(1, 'Diploma in Civil Engineering', 'DCE', 3, 6),
(1, 'Diploma in Electrical Engineering', 'DEE', 3, 6),
(2, 'B.Tech Computer Science', 'BTCS', 4, 8),
(2, 'B.Tech Mechanical Engineering', 'BTME', 4, 8),
(2, 'B.Tech Civil Engineering', 'BTCE', 4, 8),
(2, 'B.Tech Electrical Engineering', 'BTEE', 4, 8),
(3, 'M.Tech Computer Science', 'MTCS', 2, 4),
(3, 'M.Tech Structural Engineering', 'MTSE', 2, 4);

-- Create semesters for all courses (dynamic based on total_semesters)
DELIMITER $$
CREATE PROCEDURE create_semesters()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE cid INT;
    DECLARE tsem INT;
    DECLARE i INT;
    DECLARE cur CURSOR FOR SELECT id, total_semesters FROM courses;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO cid, tsem;
        IF done THEN
            LEAVE read_loop;
        END IF;

        SET i = 1;
        WHILE i <= tsem DO
            INSERT IGNORE INTO semesters (course_id, semester_number, semester_name)
            VALUES (cid, i, CONCAT('Semester ', i));
            SET i = i + 1;
        END WHILE;
    END LOOP;
    CLOSE cur;
END$$
DELIMITER ;

CALL create_semesters();
DROP PROCEDURE create_semesters;