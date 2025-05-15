<?php
// Include database connection
include_once '../database/config.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Check for required fields
if (!isset($input['driver_id']) || !isset($input['notes'])) {
    echo json_encode(['success' => false, 'error' => 'Driver ID and notes are required']);
    exit;
}

$driver_id = intval($input['driver_id']);
$notes = $input['notes'];
$date_time = isset($input['date_time']) ? $input['date_time'] : date('Y-m-d H:i:s');

// For demo purposes, we'll use coordinator ID 1 (the transport coordinator)
$coordinator_id = 1;

// Start transaction
$conn->begin_transaction();

try {
    // Insert into dispatch_assignments
    $query = "INSERT INTO dispatch_assignments (driver_id, coordinator_id, assignment_date, notes, status) 
              VALUES (?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('iiss', $driver_id, $coordinator_id, $date_time, $notes);
    $stmt->execute();
    
    // Update driver status to 'assigned'
    $updateQuery = "UPDATE users SET status = 'assigned' WHERE id = ? AND role = 'driver'";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param('i', $driver_id);
    $updateStmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Driver has been forwarded to dispatch coordinator',
        'assignment_id' => $conn->insert_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'error' => 'Failed to forward driver: ' . $e->getMessage()
    ]);
}

// Close database connection
$conn->close();
?> 