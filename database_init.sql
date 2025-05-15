-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS skyagro_transport;

-- Use the database
USE skyagro_transport;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'transport_coordinator', 'dispatch_coordinator') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create transporters (drivers) table
CREATE TABLE IF NOT EXISTS transporters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    status ENUM('available', 'assigned', 'unavailable') DEFAULT 'available',
    vehicle_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create dispatch_assignments table
CREATE TABLE IF NOT EXISTS dispatch_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    driver_id INT NOT NULL,
    notes TEXT,
    assignment_date DATETIME NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES transporters(id) ON DELETE CASCADE
);

-- Create load_assignments table for storing load information
CREATE TABLE IF NOT EXISTS load_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    delivery_location VARCHAR(255) NOT NULL,
    load_date DATE NOT NULL,
    weight DECIMAL(10,2) NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create driver_load_assignments table to track which drivers received load information
CREATE TABLE IF NOT EXISTS driver_load_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    driver_id INT NOT NULL,
    load_id INT NOT NULL,
    sent_at TIMESTAMP NOT NULL,
    accepted BOOLEAN DEFAULT NULL,
    accepted_at TIMESTAMP NULL,
    FOREIGN KEY (driver_id) REFERENCES transporters(id) ON DELETE CASCADE,
    FOREIGN KEY (load_id) REFERENCES load_assignments(id) ON DELETE CASCADE
);

-- Insert sample user data
INSERT INTO users (username, password, role) VALUES
    ('admin', 'admin123', 'admin'), -- In production, use hashed passwords
    ('transport', 'transport123', 'transport_coordinator'),
    ('dispatch', 'dispatch123', 'dispatch_coordinator');

-- Insert sample transporters data
INSERT INTO transporters (name, phone, email, status, vehicle_type) VALUES
    ('John Smith', '555-123-4567', 'jsmith@skyagro.com', 'available', 'Truck'),
    ('Sarah Johnson', '555-234-5678', 'sjohnson@skyagro.com', 'assigned', 'Van'),
    ('Michael Brown', '555-345-6789', 'mbrown@skyagro.com', 'available', 'Truck'),
    ('Lisa Davis', '555-456-7890', 'ldavis@skyagro.com', 'unavailable', 'Van'),
    ('Robert Wilson', '555-567-8901', 'rwilson@skyagro.com', 'available', 'Truck');

-- Insert sample load assignments data
INSERT INTO load_assignments (title, pickup_location, delivery_location, load_date, weight, cost, notes) VALUES
    ('Grain Shipment', 'Farm A - 123 Rural Road', 'SkyAgro Processing Plant', '2023-06-15', 2500.00, 1200.00, 'Handle with care, organic grain'),
    ('Equipment Transport', 'SkyAgro Warehouse', 'Farm B - 456 Country Lane', '2023-06-18', 1800.50, 950.75, 'Agricultural equipment for seasonal harvest'),
    ('Fertilizer Delivery', 'SkyAgro Supply Center', 'Multiple Farms (See route details)', '2023-06-20', 3200.00, 1450.00, 'Route details attached. Delivery to 3 farms in the northern region.'); 