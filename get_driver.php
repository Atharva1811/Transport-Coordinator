<?php
// Include database connection
include_once '../database/config.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Get driver ID from query parameter
$driver_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($driver_id <= 0) {
    echo json_encode(['error' => 'Invalid driver ID']);
    exit;
}

// Initialize response array
$response = [];

// Get driver basic information
$driverQuery = "SELECT 
                    u.id,
                    u.username,
                    u.name,
                    u.email,
                    u.phone,
                    u.location,
                    u.joined_date AS driverSince,
                    u.status,
                    u.is_new AS isNew
                FROM users u
                WHERE u.id = ? AND u.role = 'driver'";

$stmt = $conn->prepare($driverQuery);
$stmt->bind_param('i', $driver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Driver not found']);
    exit;
}

$response = $result->fetch_assoc();
$stmt->close();

// Get vehicle information
$vehicleQuery = "SELECT 
                    v.vehicle_id,
                    v.make,
                    v.model,
                    v.year,
                    v.vehicle_class AS vehicleType,
                    v.license_plate,
                    v.odometer,
                    v.fuel_type,
                    v.last_inspection_date
                FROM vehicles v
                JOIN driver_vehicle_assignments dva ON v.id = dva.vehicle_id
                WHERE dva.driver_id = ? AND dva.is_current = 1";

$stmt = $conn->prepare($vehicleQuery);
$stmt->bind_param('i', $driver_id);
$stmt->execute();
$vehicleResult = $stmt->get_result();

if ($vehicleResult->num_rows > 0) {
    $vehicle = $vehicleResult->fetch_assoc();
    $response['vehicleInfo'] = $vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['vehicle_id'] . ')';
    $response['vehicleType'] = $vehicle['vehicleType'];
    $response['vehicle'] = $vehicle;
}
$stmt->close();

// Get certifications
$certQuery = "SELECT 
                 certification_type, 
                 certification_number, 
                 issue_date, 
                 expiry_date, 
                 status
              FROM certifications
              WHERE driver_id = ?";

$stmt = $conn->prepare($certQuery);
$stmt->bind_param('i', $driver_id);
$stmt->execute();
$certResult = $stmt->get_result();

$response['certifications'] = [];
while ($cert = $certResult->fetch_assoc()) {
    $response['certifications'][] = $cert;
}
$stmt->close();

// Get driver metrics
$metricsQuery = "SELECT
                    COUNT(DISTINCT t.id) AS tripsCompleted,
                    COALESCE(SUM(t.distance), 0) AS totalDistance,
                    COALESCE(SUM(t.driving_time) / 60, 0) AS drivingTime,
                    COALESCE(SUM(t.idle_time) / 60, 0) AS idleTime,
                    COUNT(DISTINCT b.id) AS breaksTaken
                FROM users u
                LEFT JOIN trips t ON u.id = t.driver_id AND t.status = 'completed'
                LEFT JOIN breaks b ON t.id = b.trip_id
                WHERE u.id = ?";

$stmt = $conn->prepare($metricsQuery);
$stmt->bind_param('i', $driver_id);
$stmt->execute();
$metricsResult = $stmt->get_result();

if ($metricsResult->num_rows > 0) {
    $metrics = $metricsResult->fetch_assoc();
    $response['metrics'] = [
        'tripsCompleted' => (int)$metrics['tripsCompleted'],
        'totalDistance' => number_format($metrics['totalDistance'], 0, '.', ','),
        'drivingTime' => number_format($metrics['drivingTime'], 0, '.', ','),
        'idleTime' => number_format($metrics['idleTime'], 0, '.', ','),
        'breaksTaken' => number_format($metrics['breaksTaken'], 0, '.', ',')
    ];
}
$stmt->close();

// Get recent trips
$tripsQuery = "SELECT 
                t.trip_id,
                DATE_FORMAT(t.start_date, '%d %b %Y') AS date,
                t.origin,
                t.destination,
                CONCAT(t.distance, ' km') AS distance,
                CONCAT(FLOOR(t.duration / 60), 'h ', (t.duration % 60), 'm') AS duration,
                t.status
              FROM trips t
              WHERE t.driver_id = ?
              ORDER BY t.start_date DESC
              LIMIT 5";

$stmt = $conn->prepare($tripsQuery);
$stmt->bind_param('i', $driver_id);
$stmt->execute();
$tripsResult = $stmt->get_result();

$response['recentTrips'] = [];
while ($trip = $tripsResult->fetch_assoc()) {
    $response['recentTrips'][] = $trip;
}
$stmt->close();

// Get maintenance history for current vehicle
if (isset($vehicle['id'])) {
    $maintenanceQuery = "SELECT 
                           maintenance_type,
                           service_date,
                           odometer_reading,
                           next_service_date,
                           next_service_odometer,
                           notes
                         FROM vehicle_maintenance
                         WHERE vehicle_id = ?
                         ORDER BY service_date DESC
                         LIMIT 5";
                         
    $stmt = $conn->prepare($maintenanceQuery);
    $stmt->bind_param('i', $vehicle['id']);
    $stmt->execute();
    $maintenanceResult = $stmt->get_result();
    
    $response['maintenanceHistory'] = [];
    while ($maintenance = $maintenanceResult->fetch_assoc()) {
        $response['maintenanceHistory'][] = $maintenance;
    }
    $stmt->close();
}

// Return the driver data as JSON
echo json_encode($response);

// Close the database connection
$conn->close();
?> 