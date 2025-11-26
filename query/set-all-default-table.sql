-- 1. Table Users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    
    avatar_url VARCHAR(255) DEFAULT 'assets/default_avatar.png',
    bio TEXT,
    theme VARCHAR(20) DEFAULT 'dark',
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Table Projects
RCHAR(20) DEFAULT 'private',
    
    owner_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Clé étrangère
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Table Members (Liaison)
CREATE TABLE project_members (
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role VARCHAR(50) DEFAULT 'viewer',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Clé composite (Un user ne peut pas rejoindre 2 fois le même projet)
    PRIMARY KEY (project_id, user_id),
    
    -- Clés étrangères
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insertion Admin Test
INSERT INTO users (username, email, password, role) 
VALUES ('Admin', 'admin@arcops.net', '$2y$10$ExempleHash...', 'admin');