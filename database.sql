-- ============================================================
-- SIMAK - Sistem Informasi Pembayaran Mahasiswa
-- Universitas Al-Asyariah Mandar
-- ============================================================

CREATE DATABASE IF NOT EXISTS simak_unasman CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE simak_unasman;

-- Tabel users
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nim` VARCHAR(20) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','mahasiswa') NOT NULL DEFAULT 'mahasiswa',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel faculties
CREATE TABLE `faculties` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `code` VARCHAR(10) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel students
CREATE TABLE `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `nim` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `faculty_id` INT NOT NULL,
  `semester` TINYINT NOT NULL DEFAULT 1,
  `angkatan` YEAR NOT NULL,
  `phone` VARCHAR(20),
  `email` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`faculty_id`) REFERENCES `faculties`(`id`)
) ENGINE=InnoDB;

-- Tabel bills (tagihan)
CREATE TABLE `bills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `bill_code` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(200) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `semester` TINYINT NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `due_date` DATE,
  `status` ENUM('unpaid','pending','paid','failed') NOT NULL DEFAULT 'unpaid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel payments
CREATE TABLE `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bill_id` INT NOT NULL,
  `order_id` VARCHAR(100) NOT NULL UNIQUE,
  `snap_token` VARCHAR(255),
  `payment_date` TIMESTAMP NULL,
  `payment_method` VARCHAR(50),
  `payment_status` ENUM('pending','success','failed','expired') NOT NULL DEFAULT 'pending',
  `amount_paid` DECIMAL(12,2),
  `midtrans_response` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`bill_id`) REFERENCES `bills`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DATA AWAL (Seed Data)
-- ============================================================

-- Admin default (password: admin123)
INSERT INTO `users` (`nim`, `password`, `role`) VALUES
('ADMIN001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Fakultas
INSERT INTO `faculties` (`name`, `code`) VALUES
('Fakultas Teknik', 'FT'),
('Fakultas Ekonomi', 'FE'),
('Fakultas Hukum', 'FH'),
('Fakultas Keguruan dan Ilmu Pendidikan', 'FKIP'),
('Fakultas Pertanian', 'FAPERTA');

-- Mahasiswa demo (password: mhs123)
INSERT INTO `users` (`nim`, `password`, `role`) VALUES
('2021010001', '$2y$10$TKh8H1.PmbraeX7yXRSiW.9fVJJJIJWPkWpxbhMXiPl1lHWgpMByi', 'mahasiswa'),
('2021020001', '$2y$10$TKh8H1.PmbraeX7yXRSiW.9fVJJJIJWPkWpxbhMXiPl1lHWgpMByi', 'mahasiswa');

INSERT INTO `students` (`user_id`, `nim`, `name`, `faculty_id`, `semester`, `angkatan`, `phone`, `email`) VALUES
(2, '2021010001', 'Ahmad Fauzi', 1, 7, 2021, '081234567890', 'ahmad@email.com'),
(3, '2021020001', 'Siti Rahmawati', 2, 5, 2021, '082345678901', 'siti@email.com');

-- Tagihan demo
INSERT INTO `bills` (`student_id`, `bill_code`, `description`, `amount`, `semester`, `academic_year`, `due_date`, `status`) VALUES
(1, 'BILL-2024-0001', 'UKT Semester 7 TA 2024/2025', 2500000, 7, '2024/2025', '2024-09-30', 'unpaid'),
(2, 'BILL-2024-0002', 'UKT Semester 5 TA 2024/2025', 2000000, 5, '2024/2025', '2024-09-30', 'paid');
