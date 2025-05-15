<?php
// Enable CORS for development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Include database connection
require_once "db_config.php";

// Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the raw POST data
    $json_data = file_get_contents("php://input");
    $data = json_decode($json_data, true);
    
    // Validate input
    if (!empty($data["driver_id"])) {
        $driver_id = (int)$data["driver_id"];
        $notes = !empty($data["notes"]) ? $data["notes"] : "";
        $date_time = !empty($data["date_time"]) ? $data["date_time"] : date("Y-m-d H:i:s");
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert into dispatch_assignments
            $insert_sql = "INSERT INTO dispatch_assignments (driver_id, notes, assignment_date) 
                           VALUES (?, ?, ?)";
            
            $stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($stmt, "iss", $driver_id, $notes, $date_time);
            $insert_result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Update driver status
            $update_sql = "UPDATE transporters SET status = 'assigned' WHERE id = ?";
            
            $stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt, "i", $driver_id);
            $update_result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if ($insert_result && $update_result) {
                mysqli_commit($conn);
                
                echo json_encode([
                    "success" => true,
                    "message" => "Driver successfully forwarded to dispatch coordinator"
                ]);
            } else {
                // Something went wrong, rollback
                mysqli_rollback($conn);
                
                echo json_encode([
                    "success" => false,
                    "message" => "Failed to forward driver"
                ]);
            }
        } catch (Exception $e) {
            // An exception occurred, rollback
            mysqli_rollback($conn);
            
            echo json_encode([
                "success" => false,
                "message" => "Error: " . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Driver ID is required"
        ]);
    }
} else {
    // For preflight OPTIONS requests
    if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
        http_response_code(200);
        exit();
    }
    
    // Not a POST request
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
}

// Close connection
mysqli_close($conn);
?> 