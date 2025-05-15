<?php
// Include database connection
include_once '../database/config.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Initialize response array
$response = [];

// Get all drivers with basic information
$driversQuery = "SELECT 
                    u.id,
                    u.name,
                    u.email,
                    u.phone,
                    u.status,
                    u.is_new AS isNew,
                    v.vehicle_class AS vehicleType
                 FROM users u
                 LEFT JOIN driver_vehicle_assignments dva ON u.id = dva.driver_id AND dva.is_current = 1
                 LEFT JOIN vehicles v ON dva.vehicle_id = v.id
                 WHERE u.role = 'driver'
                 ORDER BY u.name";

$result = $conn->query($driversQuery);

if ($result) {
    $drivers = [];
    while ($driver = $result->fetch_assoc()) {
        $drivers[] = [
            'id' => (int)$driver['id'],
            'name' => $driver['name'],
            'email' => $driver['email'],
            'phone' => $driver['phone'],
            'status' => $driver['status'],
            'isNew' => (bool)$driver['isNew'],
            'vehicleType' => $driver['vehicleType'] ?: 'Unassigned'
        ];
    }
    
    $response['success'] = true;
    $response['drivers'] = $drivers;
} else {
    $response['success'] = false;
    $response['error'] = "Failed to fetch drivers: " . $conn->error;
}

// Get statistics for dashboard
$statsQuery = "SELECT 
                COUNT(*) AS totalTransporters,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS availableToday,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) AS assignedOrders,
                SUM(CASE WHEN is_new = 1 THEN 1 ELSE 0 END) AS newTransporters
               FROM users
               WHERE role = 'driver'";
               
$statsResult = $conn->query($statsQuery);

if ($statsResult && $statsResult->num_rows > 0) {
    $response['stats'] = $statsResult->fetch_assoc();
}

// Return the data as JSON
echo json_encode($response);

// Close the database connection
$conn->close();
?> 