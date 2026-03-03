-- Add OTP columns for login verification
ALTER TABLE users ADD COLUMN login_otp VARCHAR(6) NULL;
ALTER TABLE users ADD COLUMN login_otp_expires DATETIME NULL;
