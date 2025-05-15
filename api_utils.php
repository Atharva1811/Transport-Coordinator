<?php
/**
 * API Utilities for SkyAgro Transport Coordinator
 * 
 * This file contains common functions used by the SkyAgro API endpoints
 * for integration with external systems.
 */

require_once __DIR__ . '/../db_config.php';

/**
 * Verifies an API key against the system_integrations table
 * 
 * @param mysqli $conn Database connection
 * @param string $apiKey The API key to verify
 * @return int|false The system ID if valid, false otherwise
 */
function verifyApiKey($conn, $apiKey) {
    // Check if API key exists
    if (empty($apiKey)) {
        return false;
    }
    
    $sql = "SELECT id FROM system_integrations WHERE api_key = ? AND active = 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("SQL preparation error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    return false;
}

/**
 * Logs an integration event for tracking and troubleshooting
 * 
 * @param mysqli $conn Database connection
 * @param int $systemId External system ID
 * @param string $eventType Event type (push, pull, error, sync)
 * @param string $description Event description
 * @param string $status Status (success, failure, pending)
 * @param string|null $entityType Entity type (transporters, load_assignments, etc.)
 * @param int|null $entityId Entity ID
 * @return bool Success or failure
 */
function logIntegrationEvent($conn, $systemId, $eventType, $description, $status, $entityType = null, $entityId = null) {
    $sql = "INSERT INTO integration_events 
           (system_id, event_type, description, status, entity_type, entity_id) 
           VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("SQL preparation error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issssi", $systemId, $eventType, $description, $status, $entityType, $entityId);
    return $stmt->execute();
}

/**
 * Maps an external ID to a local entity
 * 
 * @param mysqli $conn Database connection
 * @param int $systemId External system ID
 * @param string $externalId External system's ID
 * @param string $localTable Local table name
 * @return int|false The local ID if found, false otherwise
 */
function getLocalIdFromExternalId($conn, $systemId, $externalId, $localTable) {
    $sql = "SELECT local_id FROM external_references 
            WHERE system_id = ? AND external_id = ? AND local_table = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("SQL preparation error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("iss", $systemId, $externalId, $localTable);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['local_id'];
    }
    
    return false;
}

/**
 * Maps a local ID to an external system's ID
 * 
 * @param mysqli $conn Database connection
 * @param int $systemId External system ID
 * @param int $localId Local entity ID
 * @param string $localTable Local table name
 * @return string|false The external ID if found, false otherwise
 */
function getExternalIdFromLocalId($conn, $systemId, $localId, $localTable) {
    $sql = "SELECT external_id FROM external_references 
            WHERE system_id = ? AND local_id = ? AND local_table = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("SQL preparation error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("iis", $systemId, $localId, $localTable);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['external_id'];
    }
    
    return false;
}

/**
 * Creates or updates a mapping between local and external IDs
 * 
 * @param mysqli $conn Database connection
 * @param int $systemId External system ID
 * @param string $localTable Local table name
 * @param int $localId Local entity ID
 * @param string $externalId External system's ID
 * @return bool Success or failure
 */
function mapExternalToLocalId($conn, $systemId, $localTable, $localId, $externalId) {
    // Check if mapping already exists
    $sql = "SELECT id FROM external_references 
            WHERE system_id = ? AND local_table = ? AND local_id = ? AND external_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("SQL preparation error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("isis", $systemId, $localTable, $localId, $externalId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Mapping already exists
        return true;
    }
    
    // Create new mapping
    $sql = "INSERT INTO external_references 
            (system_id, local_table, local_id, external_id) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        error_log("SQL preparation error: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("isis", $systemId, $localTable, $localId, $externalId);
    return $stmt->execute();
}

/**
 * Gets the appropriate HTTP response format based on the Accept header
 * 
 * @return string 'json' or 'xml'
 */
function getResponseFormat() {
    $acceptHeader = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    
    if (strpos($acceptHeader, 'application/xml') !== false || 
        strpos($acceptHeader, 'text/xml') !== false) {
        return 'xml';
    }
    
    return 'json'; // Default to JSON
}

/**
 * Formats and sends an API response
 * 
 * @param array $data Response data
 * @param int $statusCode HTTP status code
 * @param string $format Response format (json or xml)
 */
function sendApiResponse($data, $statusCode = 200, $format = null) {
    http_response_code($statusCode);
    
    if ($format === null) {
        $format = getResponseFormat();
    }
    
    if ($format === 'xml') {
        header('Content-Type: application/xml; charset=UTF-8');
        
        // Convert array to XML
        $xml = new SimpleXMLElement('<response/>');
        arrayToXml($data, $xml);
        echo $xml->asXML();
    } else {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
    }
    exit;
}

/**
 * Helper function to convert an array to XML
 * 
 * @param array $data Array to convert
 * @param SimpleXMLElement $xml XML object
 */
function arrayToXml($data, &$xml) {
    foreach ($data as $key => $value) {
        if (is_numeric($key)) {
            $key = 'item' . $key;
        }
        
        if (is_array($value)) {
            $subnode = $xml->addChild($key);
            arrayToXml($value, $subnode);
        } else {
            $xml->addChild("$key", htmlspecialchars("$value"));
        }
    }
}

/**
 * Validates and extracts API request parameters
 * 
 * @param array $requiredParams List of required parameter names
 * @param array $optionalParams List of optional parameter names
 * @return array Array of validated parameters
 */
function getApiParameters($requiredParams = [], $optionalParams = []) {
    // Determine request method
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Get parameters based on request method
    $params = [];
    if ($method === 'GET') {
        $params = $_GET;
    } else if ($method === 'POST' || $method === 'PUT') {
        // Check if content type is JSON
        $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        
        if (strpos($contentType, 'application/json') !== false) {
            // Get JSON input
            $jsonInput = file_get_contents('php://input');
            $jsonParams = json_decode($jsonInput, true);
            
            if ($jsonParams) {
                $params = $jsonParams;
            }
        } else {
            // Regular form data
            $params = $_POST;
        }
    }
    
    // Validate required parameters
    $missingParams = [];
    foreach ($requiredParams as $param) {
        if (!isset($params[$param]) || $params[$param] === '') {
            $missingParams[] = $param;
        }
    }
    
    if (!empty($missingParams)) {
        sendApiResponse([
            'error' => 'Missing required parameters',
            'missing_params' => $missingParams
        ], 400);
    }
    
    // Filter parameters to only include required and optional ones
    $validParams = [];
    $allParams = array_merge($requiredParams, $optionalParams);
    
    foreach ($allParams as $param) {
        if (isset($params[$param])) {
            $validParams[$param] = $params[$param];
        }
    }
    
    return $validParams;
}

/**
 * Sample API authentication function that can be used in API endpoints
 * Handles API key authentication and produces appropriate error responses
 * 
 * @param mysqli $conn Database connection
 * @return int The verified system ID
 */
function authenticateApiRequest($conn) {
    // Get API key from header
    $headers = getallheaders();
    $apiKey = isset($headers['X-API-Key']) ? $headers['X-API-Key'] : '';
    
    // Verify API key
    $systemId = verifyApiKey($conn, $apiKey);
    if (!$systemId) {
        sendApiResponse([
            'error' => 'Authentication failed',
            'message' => 'Invalid or missing API key'
        ], 401);
    }
    
    return $systemId;
}

/**
 * Sample integration check to ensure the database has the required tables
 * 
 * @param mysqli $conn Database connection
 * @return bool True if integration tables exist, false otherwise
 */
function checkIntegrationTablesExist($conn) {
    $tables = ['system_integrations', 'external_references', 'integration_events'];
    $existingTables = [];
    
    // Check each table
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            $existingTables[] = $table;
        }
    }
    
    return count($existingTables) === count($tables);
}

/**
 * Creates the integration tables if they don't exist
 * 
 * @param mysqli $conn Database connection
 * @return bool Success or failure
 */
function createIntegrationTables($conn) {
    // Define SQL for creating tables
    $sql = "
    -- Integration table for external system references
    CREATE TABLE IF NOT EXISTS system_integrations (
        id INT PRIMARY KEY AUTO_INCREMENT,
        system_name VARCHAR(100) NOT NULL,
        system_url VARCHAR(255) NOT NULL,
        api_key VARCHAR(255) NOT NULL,
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Cross-reference table for mapping external IDs
    CREATE TABLE IF NOT EXISTS external_references (
        id INT PRIMARY KEY AUTO_INCREMENT,
        system_id INT NOT NULL,
        local_table VARCHAR(50) NOT NULL,
        local_id INT NOT NULL,
        external_id VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (system_id) REFERENCES system_integrations(id) ON DELETE CASCADE,
        UNIQUE KEY unique_external_ref (system_id, local_table, local_id, external_id)
    );

    -- Event log for tracking integration activities
    CREATE TABLE IF NOT EXISTS integration_events (
        id INT PRIMARY KEY AUTO_INCREMENT,
        system_id INT NOT NULL,
        event_type ENUM('push', 'pull', 'error', 'sync') NOT NULL,
        description TEXT NOT NULL,
        status ENUM('success', 'failure', 'pending') NOT NULL,
        entity_type VARCHAR(50),
        entity_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (system_id) REFERENCES system_integrations(id) ON DELETE CASCADE
    );";
    
    return $conn->multi_query($sql);
}
?> 