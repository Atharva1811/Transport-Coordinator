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
    if (!empty($data["username"]) && !empty($data["password"])) {
        $username = $data["username"];
        $password = $data["password"];
        
        // Prepare a select statement
        $sql = "SELECT id, username, role, password FROM users WHERE username = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $role, $hashed_password);
                    
                    if (mysqli_stmt_fetch($stmt)) {
                        // Verify password (in a real application, use password_verify)
                        if ($password === $hashed_password) { // This is just for demonstration; use password_verify in production
                            // Password is correct, create a response
                            $response = [
                                "success" => true,
                                "message" => "Login successful",
                                "user" => [
                                    "id" => $id,
                                    "username" => $username,
                                    "role" => $role
                                ]
                            ];
                            
                            echo json_encode($response);
                        } else {
                            // Display an error message if password is not valid
                            echo json_encode([
                                "success" => false,
                                "message" => "Invalid password"
                            ]);
                        }
                    }
                } else {
                    // Display an error message if username doesn't exist
                    echo json_encode([
                        "success" => false,
                        "message" => "No account found with that username"
                    ]);
                }
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Oops! Something went wrong. Please try again later."
                ]);
            }
            
            // Close statement
            mysqli_stmt_close($stmt);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Please enter both username and password"
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