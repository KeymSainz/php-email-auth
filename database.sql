-- Run this migration when ready. Do NOT run before updating the code.
-- Schema: users, email_verifications, otp_codes

-- Add fullname and created_at to users (if not exists)
ALTER TABLE users ADD COLUMN fullname VARCHAR(255) NULL AFTER id;
ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Create email_verifications table
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create otp_codes table
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Remove old columns from users (run AFTER confirming new tables work)
-- Comment out if these columns don't exist in your users table
ALTER TABLE users DROP COLUMN verification_code;
ALTER TABLE users DROP COLUMN login_otp;
ALTER TABLE users DROP COLUMN login_otp_expires;
