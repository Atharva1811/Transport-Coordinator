<?php
// Include database connection
include_once '../database/config.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure this is a DELETE request
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get driver ID from query parameter
$driver_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($driver_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid driver ID']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // First, check if driver exists and is a driver (not a coordinator)
    $checkQuery = "SELECT id FROM users WHERE id = ? AND role = 'driver'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('i', $driver_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Driver not found']);
        exit;
    }
    
    // Delete any assignments
    $deleteAssignmentsQuery = "DELETE FROM dispatch_assignments WHERE driver_id = ?";
    $deleteAssignmentsStmt = $conn->prepare($deleteAssignmentsQuery);
    $deleteAssignmentsStmt->bind_param('i', $driver_id);
    $deleteAssignmentsStmt->execute();
    
    // Delete vehicle assignments
    $deleteVehicleAssignmentsQuery = "DELETE FROM driver_vehicle_assignments WHERE driver_id = ?";
    $deleteVehicleAssignmentsStmt = $conn->prepare($deleteVehicleAssignmentsQuery);
    $deleteVehicleAssignmentsStmt->bind_param('i', $driver_id);
    $deleteVehicleAssignmentsStmt->execute();
    
    // Delete any certifications
    $deleteCertificationsQuery = "DELETE FROM certifications WHERE driver_id = ?";
    $deleteCertificationsStmt = $conn->prepare($deleteCertificationsQuery);
    $deleteCertificationsStmt->bind_param('i', $driver_id);
    $deleteCertificationsStmt->execute();
    
    // Handle trips (this is more complex as we need to handle breaks as well)
    // First get trip IDs
    $tripsQuery = "SELECT id FROM trips WHERE driver_id = ?";
    $tripsStmt = $conn->prepare($tripsQuery);
    $tripsStmt->bind_param('i', $driver_id);
    $tripsStmt->execute();
    $tripsResult = $tripsStmt->get_result();
    
    // Delete breaks for each trip
    while ($trip = $tripsResult->fetch_assoc()) {
        $tripId = $trip['id'];
        $deleteBreaksQuery = "DELETE FROM breaks WHERE trip_id = ?";
        $deleteBreaksStmt = $conn->prepare($deleteBreaksQuery);
        $deleteBreaksStmt->bind_param('i', $tripId);
        $deleteBreaksStmt->execute();
    }
    
    // Now delete the trips
    $deleteTripsQuery = "DELETE FROM trips WHERE driver_id = ?";
    $deleteTripsStmt = $conn->prepare($deleteTripsQuery);
    $deleteTripsStmt->bind_param('i', $driver_id);
    $deleteTripsStmt->execute();
    
    // Finally, delete the driver
    $deleteDriverQuery = "DELETE FROM users WHERE id = ? AND role = 'driver'";
    $deleteDriverStmt = $conn->prepare($deleteDriverQuery);
    $deleteDriverStmt->bind_param('i', $driver_id);
    $deleteDriverStmt->execute();
    
    // Check if any rows were affected
    if ($deleteDriverStmt->affected_rows === 0) {
        throw new Exception("No driver was deleted");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Driver has been deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete driver: ' . $e->getMessage()
    ]);
}

// Close database connection
$conn->close();
?> 