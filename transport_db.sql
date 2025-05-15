-- SkyAgro Transport Coordinator Database Schema

-- Drop database if exists (for clean installations)
DROP DATABASE IF EXISTS transport_db;

-- Create database
CREATE DATABASE transport_db;
USE transport_db;

-- Create Users table (for both drivers and coordinators)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('driver', 'coordinator') NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    location VARCHAR(100),
    joined_date DATE NOT NULL,
    status ENUM('available', 'assigned', 'unavailable') DEFAULT 'available',
    is_new BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Vehicles table
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id VARCHAR(20) NOT NULL UNIQUE,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    vehicle_class VARCHAR(50) NOT NULL,
    license_plate VARCHAR(20) NOT NULL,
    odometer DECIMAL(10, 2) DEFAULT 0,
    fuel_type ENUM('diesel', 'gasoline', 'electric', 'hybrid') DEFAULT 'diesel',
    last_inspection_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Driver-Vehicle assignments
CREATE TABLE driver_vehicle_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    assignment_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- Create Certifications table
CREATE TABLE certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    certification_type VARCHAR(100) NOT NULL,
    certification_number VARCHAR(50) NOT NULL,
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('valid', 'expired', 'pending') DEFAULT 'valid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create Loads table (for shipments/deliveries)
CREATE TABLE loads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    pickup_location VARCHAR(255) NOT NULL,
    delivery_location VARCHAR(255) NOT NULL,
    load_date DATE NOT NULL,
    weight DECIMAL(10, 2) NOT NULL,
    cost DECIMAL(10, 2) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'assigned', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Trips table
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id VARCHAR(20) NOT NULL UNIQUE,
    driver_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    load_id INT,
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    origin VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    distance DECIMAL(10, 2) NOT NULL,
    duration INT, -- in minutes
    driving_time INT, -- in minutes
    idle_time INT, -- in minutes
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (load_id) REFERENCES loads(id) ON DELETE SET NULL
);

-- Create Breaks table
CREATE TABLE breaks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME,
    duration INT, -- in minutes
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
);

-- Create Dispatch Assignments table
CREATE TABLE dispatch_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    coordinator_id INT NOT NULL,
    assignment_date DATETIME NOT NULL,
    notes TEXT,
    status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (coordinator_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create Vehicle Maintenance table
CREATE TABLE vehicle_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    maintenance_type VARCHAR(100) NOT NULL,
    service_date DATE NOT NULL,
    odometer_reading DECIMAL(10, 2) NOT NULL,
    next_service_date DATE,
    next_service_odometer DECIMAL(10, 2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- Create Driver Metrics view for easy querying
CREATE VIEW driver_metrics AS
SELECT 
    u.id AS driver_id,
    u.name AS driver_name,
    u.status AS driver_status,
    u.joined_date AS driver_since,
    COUNT(DISTINCT t.id) AS trips_completed,
    SUM(t.distance) AS total_distance,
    SUM(t.driving_time) / 60 AS driving_hours,
    SUM(t.idle_time) / 60 AS idle_hours,
    COUNT(DISTINCT b.id) AS breaks_taken
FROM users u
LEFT JOIN trips t ON u.id = t.driver_id AND t.status = 'completed'
LEFT JOIN breaks b ON t.id = b.trip_id
WHERE u.role = 'driver'
GROUP BY u.id;

-- Insert sample data - Coordinators
INSERT INTO users (username, password, role, name, email, phone, location, joined_date, status, is_new) VALUES
('transport1', '$2y$10$gGFM/O.vC4hDFf5lPrm/TO2USK7gTQaMSKjb3QG8tOoiNhHxULYaO', 'coordinator', 'Transport Coordinator', 'transport@skyagro.com', '555-987-6543', 'Chicago, IL', '2018-05-15', 'available', FALSE);

-- Insert sample data - Drivers
INSERT INTO users (username, password, role, name, email, phone, location, joined_date, status, is_new) VALUES
('driver1', '$2y$10$gGFM/O.vC4hDFf5lPrm/TO2USK7gTQaMSKjb3QG8tOoiNhHxULYaO', 'driver', 'John Smith', 'jsmith@skyagro.com', '555-123-4567', 'Chicago, IL', '2019-01-15', 'available', FALSE),
('driver2', '$2y$10$gGFM/O.vC4hDFf5lPrm/TO2USK7gTQaMSKjb3QG8tOoiNhHxULYaO', 'driver', 'Sarah Johnson', 'sjohnson@skyagro.com', '555-234-5678', 'Milwaukee, WI', '2020-03-10', 'assigned', FALSE),
('driver3', '$2y$10$gGFM/O.vC4hDFf5lPrm/TO2USK7gTQaMSKjb3QG8tOoiNhHxULYaO', 'driver', 'Michael Brown', 'mbrown@skyagro.com', '555-345-6789', 'Detroit, MI', '2023-04-05', 'available', TRUE),
('driver4', '$2y$10$gGFM/O.vC4hDFf5lPrm/TO2USK7gTQaMSKjb3QG8tOoiNhHxULYaO', 'driver', 'Lisa Davis', 'ldavis@skyagro.com', '555-456-7890', 'Indianapolis, IN', '2019-08-20', 'unavailable', FALSE),
('driver5', '$2y$10$gGFM/O.vC4hDFf5lPrm/TO2USK7gTQaMSKjb3QG8tOoiNhHxULYaO', 'driver', 'Robert Wilson', 'rwilson@skyagro.com', '555-567-8901', 'Columbus, OH', '2023-01-10', 'available', TRUE);

-- Insert sample data - Vehicles
INSERT INTO vehicles (vehicle_id, make, model, year, vehicle_class, license_plate, odometer, fuel_type, last_inspection_date) VALUES
('T-123', 'Peterbilt', '579', 2021, 'Heavy Truck (Class 8)', 'AGR-7890', 43567.00, 'diesel', '2023-05-12'),
('T-245', 'Freightliner', 'M2', 2020, 'Medium Truck (Class 6)', 'AGR-3456', 56789.00, 'diesel', '2023-04-15'),
('T-387', 'Isuzu', 'NPR', 2022, 'Light Truck (Class 4)', 'AGR-9012', 13450.00, 'diesel', '2023-05-30'),
('T-492', 'Kenworth', 'T680', 2019, 'Heavy Truck (Class 7)', 'AGR-2345', 87654.00, 'diesel', '2023-03-28'),
('T-573', 'Hino', '268A', 2022, 'Medium Truck (Class 5)', 'AGR-6789', 21340.00, 'diesel', '2023-05-05');

-- Insert sample data - Driver-Vehicle assignments
INSERT INTO driver_vehicle_assignments (driver_id, vehicle_id, assignment_date, is_current) VALUES
(1, 1, '2022-06-10', TRUE),
(2, 2, '2022-04-15', TRUE),
(3, 3, '2023-04-15', TRUE),
(4, 4, '2022-01-20', TRUE),
(5, 5, '2023-01-15', TRUE);

-- Insert sample data - Certifications
INSERT INTO certifications (driver_id, certification_type, certification_number, issue_date, expiry_date, status) VALUES
(1, 'Commercial Driver License', 'DL-456789', '2018-12-15', '2025-12-15', 'valid'),
(1, 'Commercial Driver Certification', 'CDC-78901', '2020-08-30', '2024-08-30', 'valid'),
(1, 'Hazardous Materials Endorsement', 'HME-12345', '2020-05-10', '2023-05-10', 'expired'),
(1, 'Medical Certificate', 'MC-23456', '2022-11-22', '2024-11-22', 'valid'),

(2, 'Commercial Driver License', 'DL-789012', '2019-06-22', '2024-06-22', 'valid'),
(2, 'Commercial Driver Certification', 'CDC-45678', '2020-04-15', '2024-04-15', 'valid'),
(2, 'Tanker Endorsement', 'TE-56789', '2020-07-30', '2024-07-30', 'valid'),
(2, 'Medical Certificate', 'MC-34567', '2021-09-18', '2023-09-18', 'valid'),

(3, 'Commercial Driver License', 'DL-234567', '2023-03-30', '2025-09-30', 'valid'),
(3, 'Commercial Driver Certification', 'CDC-98765', '2023-04-02', '2025-04-02', 'valid'),
(3, 'Medical Certificate', 'MC-87654', '2023-03-15', '2024-03-15', 'valid'),

(4, 'Commercial Driver License', 'DL-345678', '2019-05-05', '2024-11-05', 'valid'),
(4, 'Commercial Driver Certification', 'CDC-56789', '2020-10-18', '2024-10-18', 'valid'),
(4, 'Hazardous Materials Endorsement', 'HME-67890', '2020-07-22', '2024-07-22', 'valid'),
(4, 'Tanker Endorsement', 'TE-78901', '2020-09-14', '2024-09-14', 'valid'),
(4, 'Medical Certificate', 'MC-45678', '2021-05-30', '2023-05-30', 'valid'),

(5, 'Commercial Driver License', 'DL-123456', '2022-03-18', '2026-03-18', 'valid'),
(5, 'Commercial Driver Certification', 'CDC-12345', '2023-01-20', '2025-01-20', 'valid'),
(5, 'Medical Certificate', 'MC-56789', '2023-02-28', '2024-02-28', 'valid');

-- Insert sample data - Loads
INSERT INTO loads (title, pickup_location, delivery_location, load_date, weight, cost, notes, status) VALUES
('Grain Shipment', 'Farm A - 123 Rural Road', 'SkyAgro Processing Plant', '2023-06-15', 2500.00, 1200.00, 'Handle with care, organic grain', 'assigned'),
('Equipment Transport', 'SkyAgro Warehouse', 'Farm B - 456 Country Lane', '2023-06-18', 1800.50, 950.75, 'Agricultural equipment for seasonal harvest', 'pending'),
('Fertilizer Delivery', 'SkyAgro Supply Center', 'Multiple Farms (See route details)', '2023-06-20', 3200.00, 1450.00, 'Route details attached. Delivery to 3 farms in the northern region.', 'pending');

-- Insert sample data - Trips for John Smith
INSERT INTO trips (trip_id, driver_id, vehicle_id, load_id, start_date, end_date, origin, destination, distance, duration, driving_time, idle_time, status) VALUES
('T-1089', 1, 1, 1, '2023-06-15 08:00:00', '2023-06-15 09:45:00', 'SkyAgro Warehouse', 'Farm B - 456 Country Lane', 78.00, 105, 95, 10, 'completed'),
('T-1085', 1, 1, NULL, '2023-06-13 07:30:00', '2023-06-13 11:50:00', 'SkyAgro Supply Center', 'Multiple Farms (Route #32)', 145.00, 260, 220, 40, 'completed'),
('T-1076', 1, 1, NULL, '2023-06-10 10:00:00', '2023-06-10 12:10:00', 'Farm A - 123 Rural Road', 'SkyAgro Processing Plant', 92.00, 130, 110, 20, 'completed'),
('T-1065', 1, 1, NULL, '2023-06-07 08:15:00', '2023-06-07 12:05:00', 'SkyAgro Distribution Center', 'Regional Market #5', 187.00, 230, 200, 30, 'completed'),
('T-1047', 1, 1, NULL, '2023-06-02 09:30:00', '2023-06-02 12:00:00', 'SkyAgro Warehouse', 'Farm C - 789 Rural Highway', 112.00, 150, 135, 15, 'completed');

-- Insert sample data - Trips for Sarah Johnson
INSERT INTO trips (trip_id, driver_id, vehicle_id, load_id, start_date, end_date, origin, destination, distance, duration, driving_time, idle_time, status) VALUES
('T-1083', 2, 2, NULL, '2023-06-14 09:00:00', '2023-06-14 11:40:00', 'SkyAgro Distribution Center', 'Regional Market #2', 135.00, 160, 140, 20, 'completed'),
('T-1079', 2, 2, NULL, '2023-06-11 08:45:00', '2023-06-11 11:00:00', 'Farm C - 789 Rural Highway', 'SkyAgro Processing Plant', 110.00, 135, 120, 15, 'completed'),
('T-1070', 2, 2, NULL, '2023-06-08 07:30:00', '2023-06-08 11:20:00', 'SkyAgro Warehouse', 'Multiple Farms (Route #18)', 168.00, 230, 200, 30, 'completed');

-- Insert sample data - Trips for Michael Brown
INSERT INTO trips (trip_id, driver_id, vehicle_id, load_id, start_date, end_date, origin, destination, distance, duration, driving_time, idle_time, status) VALUES
('T-1081', 3, 3, NULL, '2023-06-12 10:30:00', '2023-06-12 11:40:00', 'SkyAgro Supply Center', 'Farm D - 234 Valley Road', 58.00, 70, 60, 10, 'completed'),
('T-1072', 3, 3, NULL, '2023-06-09 11:00:00', '2023-06-09 12:35:00', 'Farm B - 456 Country Lane', 'SkyAgro Warehouse', 75.00, 95, 85, 10, 'completed'),
('T-1063', 3, 3, NULL, '2023-06-05 14:00:00', '2023-06-05 14:50:00', 'SkyAgro Processing Plant', 'Local Distribution Center', 42.00, 50, 45, 5, 'completed');

-- Insert sample data - Trips for Lisa Davis
INSERT INTO trips (trip_id, driver_id, vehicle_id, load_id, start_date, end_date, origin, destination, distance, duration, driving_time, idle_time, status) VALUES
('T-1024', 4, 4, NULL, '2023-05-28 08:00:00', '2023-05-28 11:15:00', 'Farm E - 567 Mountain Pass', 'SkyAgro Processing Plant', 165.00, 195, 175, 20, 'completed'),
('T-1018', 4, 4, NULL, '2023-05-25 07:15:00', '2023-05-25 11:20:00', 'SkyAgro Supply Center', 'Regional Distribution Hub', 210.00, 245, 220, 25, 'completed'),
('T-1010', 4, 4, NULL, '2023-05-21 08:30:00', '2023-05-21 12:15:00', 'Chemical Supply Factory', 'Multiple Farms (Route #42)', 185.00, 225, 200, 25, 'completed');

-- Insert sample data - Trips for Robert Wilson
INSERT INTO trips (trip_id, driver_id, vehicle_id, load_id, start_date, end_date, origin, destination, distance, duration, driving_time, idle_time, status) VALUES
('T-1075', 5, 5, NULL, '2023-06-10 09:15:00', '2023-06-10 11:05:00', 'SkyAgro Warehouse', 'Farm F - 890 Riverside Drive', 82.00, 110, 95, 15, 'completed'),
('T-1068', 5, 5, NULL, '2023-06-07 10:30:00', '2023-06-07 12:35:00', 'Agricultural Supply Store', 'SkyAgro Distribution Center', 95.00, 125, 110, 15, 'completed'),
('T-1054', 5, 5, NULL, '2023-06-03 08:45:00', '2023-06-03 10:10:00', 'SkyAgro Supply Center', 'Farm A - 123 Rural Road', 68.00, 85, 75, 10, 'completed');

-- Insert sample data - Breaks
-- John Smith breaks
INSERT INTO breaks (trip_id, start_time, end_time, duration, location) VALUES
(1, '2023-06-15 08:45:00', '2023-06-15 09:00:00', 15, 'Highway Rest Stop 12'),
(2, '2023-06-13 09:15:00', '2023-06-13 09:45:00', 30, 'Gas Station - Route 66'),
(3, '2023-06-10 11:00:00', '2023-06-10 11:15:00', 15, 'Farm Entrance');

-- Sarah Johnson breaks
INSERT INTO breaks (trip_id, start_time, end_time, duration, location) VALUES
(6, '2023-06-14 10:15:00', '2023-06-14 10:45:00', 30, 'Truck Stop 45'),
(7, '2023-06-11 09:50:00', '2023-06-11 10:05:00', 15, 'Rural Roadside Stop');

-- Michael Brown breaks
INSERT INTO breaks (trip_id, start_time, end_time, duration, location) VALUES
(9, '2023-06-12 11:00:00', '2023-06-12 11:10:00', 10, 'Farm Gate Entrance');

-- Lisa Davis breaks
INSERT INTO breaks (trip_id, start_time, end_time, duration, location) VALUES
(12, '2023-05-28 09:30:00', '2023-05-28 10:00:00', 30, 'Mountain View Rest Area'),
(13, '2023-05-25 09:00:00', '2023-05-25 09:30:00', 30, 'Highway Rest Stop 36');

-- Robert Wilson breaks
INSERT INTO breaks (trip_id, start_time, end_time, duration, location) VALUES
(15, '2023-06-10 10:00:00', '2023-06-10 10:15:00', 15, 'Riverside Gas Station');

-- Insert sample data - Vehicle Maintenance
INSERT INTO vehicle_maintenance (vehicle_id, maintenance_type, service_date, odometer_reading, next_service_date, next_service_odometer, notes) VALUES
(1, 'Oil Change', '2023-05-01', 41067.00, '2023-07-01', 46067.00, 'Regular maintenance'),
(1, 'Transmission Service', '2022-10-15', 29000.00, '2023-10-15', 49000.00, 'Annual service'),
(1, 'Brake Inspection', '2023-04-05', 39000.00, '2023-07-05', 45000.00, 'Brake pads at 65% remaining'),
(1, 'Air Filter', '2023-04-20', 40000.00, '2023-08-20', 50000.00, 'Replaced air filter'),

(2, 'Oil Change', '2023-04-10', 54289.00, '2023-06-10', 59289.00, 'Regular maintenance'),
(2, 'Tire Rotation', '2023-03-25', 53500.00, '2023-06-25', 58500.00, 'Tires in good condition'),

(3, 'Full Service', '2023-05-15', 12950.00, '2023-08-15', 17950.00, 'Initial service for new vehicle'),

(4, 'Oil Change', '2023-03-10', 85154.00, '2023-06-10', 90154.00, 'Regular maintenance'),
(4, 'Major Service', '2023-02-15', 83000.00, '2023-08-15', 93000.00, 'Semi-annual complete service'),

(5, 'Oil Change', '2023-04-30', 20340.00, '2023-06-30', 25340.00, 'Regular maintenance');

-- Insert sample data - Dispatch Assignments
INSERT INTO dispatch_assignments (driver_id, coordinator_id, assignment_date, notes, status) VALUES
(2, 1, '2023-06-14 08:00:00', 'Assigned for equipment delivery to Farm B', 'accepted'),
(1, 1, '2023-06-12 09:00:00', 'Available for grain transport assignments', 'completed'); 