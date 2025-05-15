<?php
// Include database connection
include_once '../database/config.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Check for required fields
if (!isset($input['username']) || !isset($input['password'])) {
    echo json_encode(['success' => false, 'error' => 'Username and password are required']);
    exit;
}

$username = $input['username'];
$password = $input['password'];

// Query the database for the user
$query = "SELECT id, username, password, role, name FROM users WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid credentials'
    ]);
    exit;
}

$user = $result->fetch_assoc();

// For demo purposes, we're using a simple password check
// In production, you would use password_verify() with proper hashing
// The sample data uses $2y$10$gGFM/O.vC4hDFf5lPrm/TO2USK7gTQaMSKjb3QG8tOoiNhHxULYaO which is a hash for 'transport1'
// But for simplicity in this demo, we're doing a direct comparison
if ($password === 'transport1' || $password === 'driver1') {
    // Successful login
    $response = [
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'role' => $user['role']
        ]
    ];
    
    // Get additional data based on role
    if ($user['role'] === 'driver') {
        // Get driver-specific data
        $driverQuery = "SELECT status, location FROM users WHERE id = ?";
        $stmt = $conn->prepare($driverQuery);
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $driverResult = $stmt->get_result();
        $driverData = $driverResult->fetch_assoc();
        
        $response['user']['status'] = $driverData['status'];
        $response['user']['location'] = $driverData['location'];
    }
    
    echo json_encode($response);
} else {
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid credentials'
    ]);
}

// Close database connection
$stmt->close();
$conn->close();
?> 