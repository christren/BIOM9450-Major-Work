-- One-time local demonstration password reset.
-- Run this ONCE against the same cancer_portal database used by the website.
-- This does not touch Patient, TumorSample, Gene or mutation tables.
-- All three teaching accounts will use the password: password1234
-- Only use on a local, non-public demonstration installation.
USE cancer_portal;

UPDATE AppUser
SET Password = '$2y$10$PWH6KnnQPmLkchR7xcuPmercvnxQ/NowObU65KHdkJNHfRmyY7vWS'
WHERE Email IN (
    'oncologist@example.test',
    'researcher@example.test',
    'patient@example.test'
);

-- These three emails should show the same bcrypt hash.
SELECT Email, Role, LEFT(Password, 7) AS PasswordHashPrefix
FROM AppUser
WHERE Email IN (
    'oncologist@example.test',
    'researcher@example.test',
    'patient@example.test'
);
