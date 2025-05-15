<?php
// Enable CORS for development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Include database connection
require_once "db_config.php";

// Check if the request is a DELETE request
if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    // Get the driver ID from the URL parameter
    if (isset($_GET["id"]) && !empty($_GET["id"])) {
        $driver_id = (int)$_GET["id"];
        
        // Prepare a delete statement
        $sql = "DELETE FROM transporters WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "i", $driver_id);
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Check if any row was affected
                if (mysqli_affected_rows($conn) > 0) {
                    echo json_encode([
                        "success" => true,
                        "message" => "Driver deleted successfully"
                    ]);
                } else {
                    echo json_encode([
                        "success" => false,
                        "message" => "No driver found with ID $driver_id"
                    ]);
                }
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Error: Could not execute query - " . mysqli_error($conn)
                ]);
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Error: Could not prepare statement - " . mysqli_error($conn)
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
    
    // Not a DELETE request
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
}

// Close connection
mysqli_close($conn);
?> 