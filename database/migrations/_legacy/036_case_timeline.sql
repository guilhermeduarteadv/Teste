
CREATE TABLE IF NOT EXISTS case_timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT,
    event_date DATETIME,
    title VARCHAR(255),
    description TEXT,
    is_important TINYINT(1) DEFAULT 0,
    visible_client TINYINT(1) DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
