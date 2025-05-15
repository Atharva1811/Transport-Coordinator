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
if (!isset($input['title']) || !isset($input['pickup_location']) || 
    !isset($input['delivery_location']) || !isset($input['date']) ||
    !isset($input['weight']) || !isset($input['cost']) || 
    !isset($input['driver_ids']) || empty($input['driver_ids'])) {
    
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

// Extract data
$title = $input['title'];
$pickup_location = $input['pickup_location'];
$delivery_location = $input['delivery_location'];
$load_date = $input['date'];
$weight = floatval($input['weight']);
$cost = floatval($input['cost']);
$notes = isset($input['notes']) ? $input['notes'] : '';
$driver_ids = $input['driver_ids'];
$load_id = isset($input['load_id']) && !empty($input['load_id']) ? intval($input['load_id']) : null;

// Start transaction
$conn->begin_transaction();

try {
    // Check if this is a new load or existing one
    if ($load_id === null) {
        // Insert new load
        $loadQuery = "INSERT INTO loads (title, pickup_location, delivery_location, load_date, weight, cost, notes, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'assigned')";
        
        $stmt = $conn->prepare($loadQuery);
        $stmt->bind_param('ssssdds', $title, $pickup_location, $delivery_location, $load_date, $weight, $cost, $notes);
        $stmt->execute();
        
        $load_id = $conn->insert_id;
    } else {
        // Update existing load
        $loadQuery = "UPDATE loads 
                      SET title = ?, pickup_location = ?, delivery_location = ?, 
                          load_date = ?, weight = ?, cost = ?, notes = ?, status = 'assigned' 
                      WHERE id = ?";
        
        $stmt = $conn->prepare($loadQuery);
        $stmt->bind_param('ssssddsI', $title, $pickup_location, $delivery_location, $load_date, $weight, $cost, $notes, $load_id);
        $stmt->execute();
    }
    
    // Create future-dated trips for each driver
    foreach ($driver_ids as $driver_id) {
        // Get the vehicle for this driver
        $vehicleQuery = "SELECT v.id 
                        FROM vehicles v
                        JOIN driver_vehicle_assignments dva ON v.id = dva.vehicle_id
                        WHERE dva.driver_id = ? AND dva.is_current = 1";
        $vehicleStmt = $conn->prepare($vehicleQuery);
        $vehicleStmt->bind_param('i', $driver_id);
        $vehicleStmt->execute();
        $vehicleResult = $vehicleStmt->get_result();
        
        if ($vehicleResult->num_rows > 0) {
            $vehicle = $vehicleResult->fetch_assoc();
            $vehicle_id = $vehicle['id'];
            
            // Generate a unique trip ID
            $tripId = 'T-' . rand(1000, 9999);
            
            // Create a scheduled trip
            $tripQuery = "INSERT INTO trips (trip_id, driver_id, vehicle_id, load_id, start_date, origin, destination, distance, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'scheduled')";
            
            $tripStmt = $conn->prepare($tripQuery);
            $tripStart = date('Y-m-d H:i:s', strtotime($load_date));
            $tripStmt->bind_param('siiissss', $tripId, $driver_id, $vehicle_id, $load_id, $tripStart, $pickup_location, $delivery_location);
            $tripStmt->execute();
            
            // Update driver status to assigned
            $updateDriverQuery = "UPDATE users SET status = 'assigned' WHERE id = ?";
            $updateDriverStmt = $conn->prepare($updateDriverQuery);
            $updateDriverStmt->bind_param('i', $driver_id);
            $updateDriverStmt->execute();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Load information has been sent to drivers',
        'load_id' => $load_id,
        'driver_count' => count($driver_ids)
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'error' => 'Failed to send load information: ' . $e->getMessage()
    ]);
}

// Close database connection
$conn->close();
?> 