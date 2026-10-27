CREATE TABLE IF NOT EXISTS request_attempts (
    id INT NOT NULL AUTO_INCREMENT,
    scope VARCHAR(32) NOT NULL,
    subject_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY request_attempts_lookup (scope, subject_hash, created_at)
);
