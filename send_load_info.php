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
    if (empty($data["driver_ids"]) || !is_array($data["driver_ids"])) {
        echo json_encode([
            "success" => false,
            "message" => "No drivers selected"
        ]);
        exit;
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get driver details for the selected drivers
        $driver_ids = array_map('intval', $data["driver_ids"]);
        $driver_ids_str = implode(',', $driver_ids);
        
        $sql = "SELECT id, name, email FROM transporters WHERE id IN ($driver_ids_str) AND status = 'available'";
        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            throw new Exception("Failed to retrieve driver information");
        }
        
        $drivers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $drivers[] = $row;
        }
        
        if (empty($drivers)) {
            throw new Exception("No available drivers found with the selected IDs");
        }
        
        // Determine if we're using an existing load or creating a new one
        $load_id = null;
        
        if (!empty($data["load_id"])) {
            // Using an existing load
            $load_id = (int)$data["load_id"];
            
            // Verify the load exists
            $check_sql = "SELECT id FROM load_assignments WHERE id = ?";
            $stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($stmt, "i", $load_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) == 0) {
                mysqli_stmt_close($stmt);
                throw new Exception("Selected load not found");
            }
            
            mysqli_stmt_close($stmt);
            
            // Get load data for the email
            $load_sql = "SELECT title, pickup_location, delivery_location, load_date, weight, cost, notes FROM load_assignments WHERE id = ?";
            $stmt = mysqli_prepare($conn, $load_sql);
            mysqli_stmt_bind_param($stmt, "i", $load_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $load_data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if (!$load_data) {
                throw new Exception("Failed to retrieve load data");
            }
            
            // Map data to the same format as the input data
            $data["title"] = $load_data["title"];
            $data["pickup_location"] = $load_data["pickup_location"];
            $data["delivery_location"] = $load_data["delivery_location"];
            $data["date"] = $load_data["load_date"];
            $data["weight"] = $load_data["weight"];
            $data["cost"] = $load_data["cost"];
            $data["notes"] = $load_data["notes"];
        } else {
            // Insert new load information into the database
            $load_sql = "INSERT INTO load_assignments (
                title, 
                pickup_location, 
                delivery_location, 
                load_date, 
                weight, 
                cost, 
                notes, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = mysqli_prepare($conn, $load_sql);
            mysqli_stmt_bind_param($stmt, "ssssdss", 
                $data["title"], 
                $data["pickup_location"], 
                $data["delivery_location"], 
                $data["date"], 
                $data["weight"], 
                $data["cost"], 
                $data["notes"]
            );
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to save load information");
            }
            
            $load_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
        }
        
        // Associate load with drivers and send emails
        $successful_emails = 0;
        
        foreach ($drivers as $driver) {
            // Check if this driver already has this load assigned
            $check_sql = "SELECT id FROM driver_load_assignments WHERE driver_id = ? AND load_id = ?";
            $stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($stmt, "ii", $driver["id"], $load_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                // Driver already has this load assigned, skip
                mysqli_stmt_close($stmt);
                continue;
            }
            
            mysqli_stmt_close($stmt);
            
            // Insert driver-load association
            $associate_sql = "INSERT INTO driver_load_assignments (driver_id, load_id, sent_at) VALUES (?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $associate_sql);
            mysqli_stmt_bind_param($stmt, "ii", $driver["id"], $load_id);
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Failed to associate load with driver ID " . $driver["id"]);
            }
            
            mysqli_stmt_close($stmt);
            
            // Send email to driver
            $sent = sendLoadEmail($driver, $data);
            
            if ($sent) {
                $successful_emails++;
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        // Return success response
        echo json_encode([
            "success" => true,
            "message" => "Load information sent to $successful_emails out of " . count($drivers) . " drivers",
            "load_id" => $load_id
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction in case of error
        mysqli_rollback($conn);
        
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
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

// Helper function to send load information email
function sendLoadEmail($driver, $load_data) {
    // In a production environment, use a proper email sending library
    // This is a simplified version for demonstration purposes
    
    $to = $driver["email"];
    $subject = "New Load Available: " . $load_data["title"];
    
    // Format the cost with 2 decimal places
    $formatted_cost = number_format($load_data["cost"], 2);
    
    // Create HTML email
    $html_message = "
    <html>
    <head>
        <title>Load Information from SkyAgro</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            h1 { color: #3498db; }
            .load-info { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .cost { font-size: 24px; color: #2c3e50; font-weight: bold; }
            .footer { margin-top: 30px; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>New Load Available</h1>
            <p>Hello {$driver["name"]},</p>
            <p>A new load is available that matches your vehicle type. Details are provided below:</p>
            
            <div class='load-info'>
                <h2>{$load_data["title"]}</h2>
                <p><strong>Pickup Location:</strong> {$load_data["pickup_location"]}</p>
                <p><strong>Delivery Location:</strong> {$load_data["delivery_location"]}</p>
                <p><strong>Date:</strong> {$load_data["date"]}</p>
                <p><strong>Weight:</strong> {$load_data["weight"]} kg</p>
                <p><strong>Costing:</strong> <span class='cost'>$${formatted_cost}</span></p>
                
                " . (!empty($load_data["notes"]) ? "<p><strong>Additional Notes:</strong><br>{$load_data["notes"]}</p>" : "") . "
            </div>
            
            <p>Please respond to this email or contact the Transport Coordinator if you are interested in this load.</p>
            
            <p>Thank you,</p>
            <p><strong>SkyAgro Transport Coordination Team</strong></p>
            
            <div class='footer'>
                <p>This is an automated message from SkyAgro. Please do not reply directly to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Plain text alternative
    $text_message = "New Load Available: {$load_data["title"]}\n\n" .
        "Hello {$driver["name"]},\n\n" .
        "A new load is available that matches your vehicle type. Details are provided below:\n\n" .
        "Pickup Location: {$load_data["pickup_location"]}\n" .
        "Delivery Location: {$load_data["delivery_location"]}\n" .
        "Date: {$load_data["date"]}\n" .
        "Weight: {$load_data["weight"]} kg\n" .
        "Costing: $${formatted_cost}\n" .
        (!empty($load_data["notes"]) ? "Additional Notes: {$load_data["notes"]}\n\n" : "\n") .
        "Please respond to this email or contact the Transport Coordinator if you are interested in this load.\n\n" .
        "Thank you,\n" .
        "SkyAgro Transport Coordination Team\n\n" .
        "This is an automated message from SkyAgro. Please do not reply directly to this email.";
    
    // Set email headers
    $headers = [
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "From: SkyAgro Transport <transport@skyagro.com>",
        "Reply-To: transport@skyagro.com"
    ];
    
    // In a real environment, we would send the email here
    // For this demonstration, we'll log it and return success
    // mail($to, $subject, $html_message, implode("\r\n", $headers));
    
    // Log the email instead of sending (for demonstration)
    error_log("Email to: $to, Subject: $subject");
    
    // Simulate successful sending
    return true;
}

// Close database connection
mysqli_close($conn);
?> 