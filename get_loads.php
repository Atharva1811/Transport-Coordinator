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
    // Prepare a select statement to get loads
    $sql = "SELECT id, title, pickup_location, delivery_location, load_date, weight, cost, notes, created_at 
            FROM load_assignments 
            ORDER BY load_date DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $loads = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $loads[] = [
                "id" => (int)$row["id"],
                "title" => $row["title"],
                "pickup_location" => $row["pickup_location"],
                "delivery_location" => $row["delivery_location"],
                "load_date" => $row["load_date"],
                "weight" => (float)$row["weight"],
                "cost" => (float)$row["cost"],
                "notes" => $row["notes"],
                "created_at" => $row["created_at"]
            ];
        }
        
        echo json_encode([
            "success" => true,
            "data" => $loads
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Failed to retrieve load data"
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