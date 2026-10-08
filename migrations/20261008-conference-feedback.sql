CREATE TABLE IF NOT EXISTS conference_feedback (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id VARCHAR(100) NOT NULL,
    submission_key CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    overall_rating TINYINT UNSIGNED NOT NULL,
    program_rating TINYINT UNSIGNED NOT NULL,
    organization_rating TINYINT UNSIGNED NOT NULL,
    participation_format ENUM('offline', 'online', 'unspecified') NOT NULL DEFAULT 'unspecified',
    liked_text TEXT NOT NULL,
    improvements_text TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY feedback_submission (event_id, submission_key),
    KEY feedback_event_date (event_id, created_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
