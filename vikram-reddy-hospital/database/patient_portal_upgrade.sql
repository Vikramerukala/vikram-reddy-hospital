USE hospital_management;

-- Run this upgrade ONCE after importing hospital_management.sql.
-- It adds patient accounts without changing existing staff/admin accounts.

INSERT INTO roles (name, display_name)
SELECT 'patient', 'Patient'
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'patient');

ALTER TABLE users
  ADD COLUMN patient_id INT NULL AFTER role_id,
  ADD UNIQUE KEY uq_users_patient_id (patient_id),
  ADD CONSTRAINT fk_users_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS patient_password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_patient_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_patient_reset_user_expiry (user_id, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
