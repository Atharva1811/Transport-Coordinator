<?php
/**
 * SkyAgro Transport Coordinator - Pending Transporters API
 * 
 * This file handles operations related to pending transporters:
 * - Submit new transporter request
 * - List pending requests
 * - Approve request
 * - Reject request
 */

require_once '../db_config.php';

// Set appropriate headers
header('Content-Type: application/json');

// Add the pending_transporters table if it doesn't exist
$checkTableSql = "SHOW TABLES LIKE 'pending_transporters'";
$tableExists = $conn->query($checkTableSql)->num_rows > 0;

if (!$tableExists) {
    $createTableSql = "CREATE TABLE pending_transporters (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100) NOT NULL,
        address VARCHAR(255),
        vehicle_type VARCHAR(50) NOT NULL,
        vehicle_model VARCHAR(100),
        license_plate VARCHAR(20),
        license_number VARCHAR(50) NOT NULL,
        license_expiry DATE NOT NULL,
        notes TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        submitted_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_on TIMESTAMP NULL,
        processed_by INT NULL,
        rejection_reason TEXT
    )";
    
    if (!$conn->query($createTableSql)) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create pending_transporters table: ' . $conn->error
        ]);
        exit;
    }
}

// Check the request method
$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Listing pending transporters
        getPendingTransporters();
        break;
    case 'POST':
        // Check if it's an action or a new submission
        $data = json_decode(file_get_contents('php://input'), true);
        if (isset($data['action'])) {
            // Process action (approve/reject)
            processTransporterAction($data);
        } else {
            // Submit new transporter request
            submitTransporterRequest($data);
        }
        break;
    default:
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
        break;
}

/**
 * Get list of pending transporter requests
 */
function getPendingTransporters() {
    global $conn;
    
    // Check if filtering by status is required
    $status = isset($_GET['status']) ? $_GET['status'] : 'pending';
    
    if ($status === 'all') {
        $sql = "SELECT * FROM pending_transporters ORDER BY submitted_on DESC";
    } else {
        $sql = "SELECT * FROM pending_transporters WHERE status = ? ORDER BY submitted_on DESC";
    }
    
    $stmt = $conn->prepare($sql);
    if ($status !== 'all') {
        $stmt->bind_param("s", $status);
    }
    
    if (!$stmt->execute()) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch pending transporters: ' . $stmt->error
        ]);
        return;
    }
    
    $result = $stmt->get_result();
    $pendingTransporters = [];
    
    while ($row = $result->fetch_assoc()) {
        $pendingTransporters[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $pendingTransporters
    ]);
}

/**
 * Submit a new transporter request
 */
function submitTransporterRequest($data) {
    global $conn;
    
    // Validate required fields
    $requiredFields = ['name', 'phone', 'email', 'vehicle_type', 'license_number', 'license_expiry'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields',
            'missing_fields' => $missingFields
        ]);
        return;
    }
    
    // Check if a request with the same email or phone already exists
    $checkSql = "SELECT id FROM pending_transporters WHERE email = ? OR phone = ? AND status = 'pending'";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $data['email'], $data['phone']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'A request with this email or phone number is already pending'
        ]);
        return;
    }
    
    // Check if a transporter with the same email or phone already exists
    $checkExistingSql = "SELECT id FROM transporters WHERE email = ? OR phone = ?";
    $checkExistingStmt = $conn->prepare($checkExistingSql);
    $checkExistingStmt->bind_param("ss", $data['email'], $data['phone']);
    $checkExistingStmt->execute();
    $checkExistingResult = $checkExistingStmt->get_result();
    
    if ($checkExistingResult->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'A transporter with this email or phone number already exists'
        ]);
        return;
    }
    
    // Insert the new request
    $sql = "INSERT INTO pending_transporters (
                name, phone, email, address, vehicle_type, vehicle_model, 
                license_plate, license_number, license_expiry, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssss", 
        $data['name'], 
        $data['phone'], 
        $data['email'], 
        $data['address'] ?? '', 
        $data['vehicle_type'], 
        $data['vehicle_model'] ?? '', 
        $data['license_plate'] ?? '', 
        $data['license_number'], 
        $data['license_expiry'],
        $data['notes'] ?? ''
    );
    
    if (!$stmt->execute()) {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to submit transporter request: ' . $stmt->error
        ]);
        return;
    }
    
    // Notify admin about the new request (in a real implementation, this would send an email)
    notifyAdmin($conn->insert_id, $data['name']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Transporter request submitted successfully',
        'request_id' => $conn->insert_id
    ]);
}

/**
 * Process transporter action (approve/reject)
 */
function processTransporterAction($data) {
    global $conn;
    
    // Validate required fields
    if (!isset($data['id']) || !isset($data['action']) || 
        ($data['action'] !== 'approve' && $data['action'] !== 'reject') ||
        (isset($data['action']) && $data['action'] === 'reject' && !isset($data['reason']))) {
        
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action parameters'
        ]);
        return;
    }
    
    // Start a transaction
    $conn->begin_transaction();
    
    try {
        // Get the pending request
        $getSql = "SELECT * FROM pending_transporters WHERE id = ?";
        $getStmt = $conn->prepare($getSql);
        $getStmt->bind_param("i", $data['id']);
        $getStmt->execute();
        $result = $getStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Transporter request not found');
        }
        
        $request = $result->fetch_assoc();
        
        // Update the request status
        $updateSql = "UPDATE pending_transporters SET 
                        status = ?, 
                        processed_on = NOW(), 
                        processed_by = ?,
                        rejection_reason = ?
                      WHERE id = ?";
        
        $status = $data['action'] === 'approve' ? 'approved' : 'rejected';
        $userId = $data['user_id'] ?? null; // ID of the user processing the request
        $reason = $data['action'] === 'reject' ? $data['reason'] : null;
        
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("sisi", $status, $userId, $reason, $data['id']);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update request status: ' . $updateStmt->error);
        }
        
        // If approved, add to transporters table
        if ($data['action'] === 'approve') {
            $addSql = "INSERT INTO transporters (name, phone, email, status, vehicle_type) 
                       VALUES (?, ?, ?, 'available', ?)";
            
            $addStmt = $conn->prepare($addSql);
            $addStmt->bind_param("ssss", 
                $request['name'], 
                $request['phone'], 
                $request['email'], 
                $request['vehicle_type']
            );
            
            if (!$addStmt->execute()) {
                throw new Exception('Failed to add transporter: ' . $addStmt->error);
            }
            
            // Notify the transporter about approval
            notifyTransporter($request['email'], 'approved', $request['name']);
        } else {
            // Notify the transporter about rejection
            notifyTransporter($request['email'], 'rejected', $request['name'], $reason);
        }
        
        // Commit the transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $data['action'] === 'approve' 
                ? 'Transporter approved successfully' 
                : 'Transporter request rejected',
            'transporter_id' => $data['action'] === 'approve' ? $conn->insert_id : null
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Notify admin about new transporter request
 * 
 * In a real implementation, this would send an email to admin
 */
function notifyAdmin($requestId, $driverName) {
    // This is a placeholder - in a real implementation, this would send an email
    error_log("New transporter request from $driverName (ID: $requestId)");
    
    // Additional functionality to send actual email would be implemented here
}

/**
 * Notify transporter about request approval/rejection
 * 
 * In a real implementation, this would send an email to the transporter
 */
function notifyTransporter($email, $status, $name, $reason = null) {
    // This is a placeholder - in a real implementation, this would send an email
    if ($status === 'approved') {
        error_log("Notifying $name ($email) about request approval");
    } else {
        error_log("Notifying $name ($email) about request rejection. Reason: $reason");
    }
    
    // Additional functionality to send actual email would be implemented here
}
?> 