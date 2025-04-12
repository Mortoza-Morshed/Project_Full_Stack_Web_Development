-- Create the database
CREATE DATABASE IF NOT EXISTS energy_tracker;
USE energy_tracker;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('homeowner', 'provider', 'admin') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Homeowners Table
CREATE TABLE IF NOT EXISTS homeowners (
    homeowner_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    address VARCHAR(255) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Providers Table
CREATE TABLE IF NOT EXISTS providers (
    provider_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    services TEXT NOT NULL,
    location VARCHAR(100) NOT NULL,
    sustainability_practices TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Energy Usage Table
CREATE TABLE IF NOT EXISTS energy_usage (
    usage_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    energy_type VARCHAR(50) NOT NULL,
    consumption_value DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Recommendations Table
CREATE TABLE IF NOT EXISTS recommendations (
    recommendation_id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    FOREIGN KEY (provider_id) REFERENCES providers(provider_id) ON DELETE SET NULL
);

-- Insert admin user
INSERT INTO users (name, email, password, role, status) 
VALUES ('Admin', 'admin@energy.com', '$2y$10$afKpmSK466D6JvSMoguLr.WyrXLp0f8Bd1vh0e.uFJFWgruwnkY3i', 'admin', 'approved');
--'admin123' 