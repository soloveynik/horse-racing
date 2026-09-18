CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE owners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE jockeys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    experience_years INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE horses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    breed VARCHAR(100),
    birth_year INT,
    owner_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_horse_owner
        FOREIGN KEY (owner_id)
        REFERENCES owners(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE races (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    race_date DATE NOT NULL,
    location VARCHAR(150) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    race_id INT NOT NULL,
    horse_id INT NOT NULL,
    jockey_id INT NOT NULL,
    place INT NOT NULL,
    finish_time DECIMAL(8,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_result_race
        FOREIGN KEY (race_id)
        REFERENCES races(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_result_horse
        FOREIGN KEY (horse_id)
        REFERENCES horses(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_result_jockey
        FOREIGN KEY (jockey_id)
        REFERENCES jockeys(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT unique_horse_race
        UNIQUE (race_id, horse_id),

    CONSTRAINT unique_place_race
        UNIQUE (race_id, place)
);