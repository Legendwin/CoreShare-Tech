-- CoreShare Tech Database Schema

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'educator', 'admin') DEFAULT 'student',
    plan ENUM('free', 'pro', 'semester', 'exam_pass') DEFAULT 'free',
    plan_expires DATETIME DEFAULT NULL,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Resources Table
CREATE TABLE IF NOT EXISTS resources (
    id INT(11) NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    course_name VARCHAR(100) DEFAULT NULL,
    type VARCHAR(50) DEFAULT NULL,
    grade_level VARCHAR(50) DEFAULT NULL,
    programme VARCHAR(100) DEFAULT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT(11) NOT NULL,
    status ENUM('pending', 'published', 'rejected') DEFAULT 'pending',
    downloads INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Reviews Table
CREATE TABLE IF NOT EXISTS reviews (
    id INT(11) NOT NULL AUTO_INCREMENT,
    resource_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    rating INT(11) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. User Counters (Limits Tracking)
CREATE TABLE IF NOT EXISTS user_counters (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    downloads_today INT(11) DEFAULT 0,
    downloads_date DATE DEFAULT NULL,
    uploads_count INT(11) DEFAULT 0,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    data TEXT NOT NULL,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;