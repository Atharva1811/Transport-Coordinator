<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';

// Connect to MySQL without selecting a database
$conn = new mysqli($db_host, $db_user, $db_password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS transport_db";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("transport_db");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully or already exists<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Check if users table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    // Insert default users
    $sql = "INSERT INTO users (username, password, role) VALUES 
            ('transport', 'transport123', 'transport_coordinator'),
            ('driver', 'driver123', 'driver')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Default users inserted successfully<br>";
    } else {
        echo "Error inserting default users: " . $conn->error . "<br>";
    }
} else {
    echo "Users already exist in the database<br>";
}

echo "Database setup complete!";

// Close connection
$conn->close();
?> 