

-- Staff login accounts table
CREATE TABLE IF NOT EXISTS StaffUsers (
    StaffUserID  INT AUTO_INCREMENT PRIMARY KEY,
    StaffID      INT NOT NULL UNIQUE,          -- links to Staff.StaffID
    Username     VARCHAR(100) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    IsActive     TINYINT(1) DEFAULT 1,
    CreatedAt    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (StaffID) REFERENCES Staff(StaffID) ON DELETE CASCADE
);

-- ============================================================
-- SAMPLE STAFF ACCOUNTS
-- Password for ALL sample accounts below is: Staff2024
-- Admin can change passwords via Admin Panel → Staff Accounts
-- ============================================================

INSERT INTO StaffUsers (StaffID, Username, PasswordHash) VALUES
(1,  'dr.johnson',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2,  'dr.lee',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(3,  'dr.white',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(4,  'dr.green',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(5,  'dr.scott',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(11, 'dr.miller',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(12, 'dr.carter',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(13, 'dr.thompson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(14, 'dr.robinson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(15, 'dr.davis',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- NOTE: The hash above is Laravel's placeholder. Because PHP's password_hash()
-- output varies, we handle this the same way as admin login:
-- A one-time fix script will regenerate real hashes. See staff/fix_passwords.php

-- ============================================================
-- INDEX for performance
-- ============================================================
CREATE INDEX idx_staffusers_staffid ON StaffUsers(StaffID);
