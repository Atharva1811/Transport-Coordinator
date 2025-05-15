<?php
// Include database connection
include_once '../database/config.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Initialize response array
$response = [];

// Get all loads
$loadsQuery = "SELECT 
                id,
                title,
                pickup_location,
                delivery_location,
                load_date,
                weight,
                cost,
                notes,
                status
               FROM loads
               ORDER BY load_date DESC";

$result = $conn->query($loadsQuery);

if ($result) {
    $loads = [];
    while ($load = $result->fetch_assoc()) {
        $loads[] = [
            'id' => (int)$load['id'],
            'title' => $load['title'],
            'pickup_location' => $load['pickup_location'],
            'delivery_location' => $load['delivery_location'],
            'load_date' => $load['load_date'],
            'weight' => (float)$load['weight'],
            'cost' => (float)$load['cost'],
            'notes' => $load['notes'],
            'status' => $load['status']
        ];
    }
    
    $response['success'] = true;
    $response['loads'] = $loads;
} else {
    $response['success'] = false;
    $response['error'] = "Failed to fetch loads: " . $conn->error;
}

// Return the data as JSON
echo json_encode($response);

// Close the database connection
$conn->close();
?> 