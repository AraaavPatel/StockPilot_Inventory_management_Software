-- =====================================================================
-- Audit logging + notification settings
-- Non-destructive: CREATE TABLE IF NOT EXISTS only, safe to run against
-- an existing production database.
-- =====================================================================

CREATE TABLE IF NOT EXISTS audit_logs (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    DEFAULT NULL COMMENT 'NULL for pre-login events e.g. failed login',
    actor_name      VARCHAR(150)    DEFAULT NULL COMMENT 'snapshot of the user name at the time, survives user deletion',
    action          VARCHAR(60)     NOT NULL COMMENT 'e.g. LOGIN_SUCCESS, PRODUCT_UPDATED, STOCK_ADJUSTED',
    module          VARCHAR(60)     NOT NULL,
    entity_type     VARCHAR(60)     DEFAULT NULL,
    entity_id       INT UNSIGNED    DEFAULT NULL,
    old_values      TEXT            DEFAULT NULL COMMENT 'JSON snapshot before the change',
    new_values      TEXT            DEFAULT NULL COMMENT 'JSON snapshot after the change',
    ip_address      VARCHAR(45)     DEFAULT NULL,
    user_agent      VARCHAR(255)    DEFAULT NULL,
    request_id      VARCHAR(40)     DEFAULT NULL,
    prev_hash       CHAR(64)        DEFAULT NULL COMMENT 'record_hash of the previous row, forms a hash chain',
    record_hash     CHAR(64)        NOT NULL COMMENT 'sha256 of this row + prev_hash — detects direct DB tampering',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_user (user_id),
    KEY idx_audit_created (created_at),
    KEY idx_audit_action (action)
) ENGINE=InnoDB;

-- No UPDATE/DELETE routes exist anywhere in the app for this table
-- (see App\Audit\AuditLogger — it only ever INSERTs) and the account
-- the application connects as should itself not have DROP/ALTER rights
-- on this table in a hardened production setup (documented in
-- DEPLOYMENT_INFINITYFREE.md; InfinityFree's shared-DB-user model can't
-- fully enforce this at the grant level, which is why the hash chain
-- exists as a second layer of tamper *detection* even though it can't
-- prevent a determined DB-level attacker).

CREATE TABLE IF NOT EXISTS notification_log (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event           VARCHAR(60)     NOT NULL,
    recipient       VARCHAR(150)    NOT NULL,
    success         TINYINT(1)      NOT NULL DEFAULT 0,
    error_message   VARCHAR(255)    DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notif_created (created_at)
) ENGINE=InnoDB;
