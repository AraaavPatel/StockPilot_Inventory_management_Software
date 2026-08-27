-- =====================================================================
-- Login throttling
-- Non-destructive: safe to run against an existing production database.
-- Tracks failed login attempts so AuthController can lock out an
-- email+IP combination after repeated failures (see App\Core\LoginThrottle).
-- =====================================================================

CREATE TABLE IF NOT EXISTS login_attempts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(150)    NOT NULL,
    ip_address      VARCHAR(45)     NOT NULL,
    succeeded       TINYINT(1)      NOT NULL DEFAULT 0,
    attempted_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_attempts_lookup (email, ip_address, attempted_at)
) ENGINE=InnoDB;
