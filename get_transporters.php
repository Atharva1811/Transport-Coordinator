<?php
// Enable CORS for development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Include database connection
require_once "db_config.php";

// Check if the request is a GET request
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Prepare a select statement
    $sql = "SELECT t.id, t.name, t.phone, t.email, t.status, t.vehicle_type, 
            CASE WHEN t.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END as is_new
            FROM transporters t 
            ORDER BY t.name ASC";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $transporters = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $transporters[] = [
                "id" => (int)$row["id"],
                "name" => $row["name"],
                "phone" => $row["phone"],
                "email" => $row["email"],
                "status" => $row["status"],
                "vehicleType" => $row["vehicle_type"],
                "isNew" => (bool)(int)$row["is_new"]
            ];
        }
        
        echo json_encode([
            "success" => true,
            "data" => $transporters
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to retrieve transporters data"
        ]);
    }
} else {
    // For preflight OPTIONS requests
    if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
        http_response_code(200);
        exit();
    }
    
    // Not a GET request
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
}

// Close connection
mysqli_close($conn);
?> 