<?php
/**
 * SkyAgro Integration Setup Endpoint
 * 
 * This file provides an endpoint for setting up integration between SkyAgro
 * and external systems. It handles creating the necessary integration tables,
 * registering new external systems, and generating API keys.
 */

require_once 'api_utils.php';

// Set headers for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests for this endpoint
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendApiResponse([
        'error' => 'Method not allowed',
        'message' => 'This endpoint only supports POST requests'
    ], 405);
}

// Create integration tables if they don't exist
if (!checkIntegrationTablesExist($conn)) {
    if (!createIntegrationTables($conn)) {
        sendApiResponse([
            'error' => 'Database error',
            'message' => 'Failed to create integration tables: ' . $conn->error
        ], 500);
    }
}

// Process request based on action parameter
$data = json_decode(file_get_contents('php://input'), true);
$action = isset($data['action']) ? $data['action'] : '';

switch ($action) {
    case 'register_system':
        registerExternalSystem($conn, $data);
        break;
    case 'validate_key':
        validateApiKey($conn, $data);
        break;
    case 'test_connection':
        testConnection($conn);
        break;
    default:
        sendApiResponse([
            'error' => 'Invalid action',
            'message' => 'The specified action is not supported',
            'valid_actions' => ['register_system', 'validate_key', 'test_connection']
        ], 400);
}

/**
 * Registers a new external system and generates an API key
 * 
 * @param mysqli $conn Database connection
 * @param array $data Request data
 */
function registerExternalSystem($conn, $data) {
    // Validate required parameters
    $requiredParams = ['system_name', 'system_url', 'admin_password'];
    $missingParams = [];
    
    foreach ($requiredParams as $param) {
        if (!isset($data[$param]) || empty($data[$param])) {
            $missingParams[] = $param;
        }
    }
    
    if (!empty($missingParams)) {
        sendApiResponse([
            'error' => 'Missing parameters',
            'missing' => $missingParams
        ], 400);
    }
    
    // Verify admin password (simple implementation - in production, use proper authentication)
    $adminPassword = 'skyagro_admin'; // This should be stored securely in production
    if ($data['admin_password'] !== $adminPassword) {
        sendApiResponse([
            'error' => 'Authentication failed',
            'message' => 'Invalid admin password'
        ], 401);
    }
    
    // Check if system already exists
    $checkSql = "SELECT id FROM system_integrations WHERE system_name = ? OR system_url = ?";
    $checkStmt = $conn->prepare($checkSql);
    
    if (!$checkStmt) {
        sendApiResponse([
            'error' => 'Database error',
            'message' => 'Failed to prepare statement: ' . $conn->error
        ], 500);
    }
    
    $checkStmt->bind_param("ss", $data['system_name'], $data['system_url']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        sendApiResponse([
            'error' => 'System already exists',
            'message' => 'A system with this name or URL is already registered'
        ], 409);
    }
    
    // Generate a unique API key
    $apiKey = generateApiKey();
    
    // Insert the new system
    $sql = "INSERT INTO system_integrations (system_name, system_url, api_key) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        sendApiResponse([
            'error' => 'Database error',
            'message' => 'Failed to prepare statement: ' . $conn->error
        ], 500);
    }
    
    $stmt->bind_param("sss", $data['system_name'], $data['system_url'], $apiKey);
    
    if (!$stmt->execute()) {
        sendApiResponse([
            'error' => 'Database error',
            'message' => 'Failed to register system: ' . $stmt->error
        ], 500);
    }
    
    $systemId = $conn->insert_id;
    
    // Log the integration event
    logIntegrationEvent(
        $conn,
        $systemId,
        'sync',
        'System registered with SkyAgro Transport Coordinator',
        'success'
    );
    
    // Return the API key and system ID
    sendApiResponse([
        'success' => true,
        'message' => 'System registered successfully',
        'system_id' => $systemId,
        'api_key' => $apiKey,
        'instructions' => 'Store this API key securely. You will need it for all API requests.'
    ]);
}

/**
 * Validates an API key and returns system details
 * 
 * @param mysqli $conn Database connection
 * @param array $data Request data
 */
function validateApiKey($conn, $data) {
    // Check if API key is provided
    if (!isset($data['api_key']) || empty($data['api_key'])) {
        sendApiResponse([
            'error' => 'Missing parameter',
            'message' => 'API key is required'
        ], 400);
    }
    
    // Verify the API key
    $apiKey = $data['api_key'];
    $sql = "SELECT id, system_name, system_url, active, created_at FROM system_integrations WHERE api_key = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        sendApiResponse([
            'error' => 'Database error',
            'message' => 'Failed to prepare statement: ' . $conn->error
        ], 500);
    }
    
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendApiResponse([
            'error' => 'Invalid API key',
            'message' => 'The provided API key is not valid',
            'valid' => false
        ], 401);
    }
    
    $system = $result->fetch_assoc();
    
    // Check if the system is active
    if (!$system['active']) {
        sendApiResponse([
            'error' => 'System inactive',
            'message' => 'This system has been deactivated',
            'valid' => false
        ], 403);
    }
    
    // Return system details
    sendApiResponse([
        'success' => true,
        'valid' => true,
        'system' => [
            'id' => $system['id'],
            'name' => $system['system_name'],
            'url' => $system['system_url'],
            'registered_date' => $system['created_at']
        ]
    ]);
}

/**
 * Tests the connection to the SkyAgro system
 * 
 * @param mysqli $conn Database connection
 */
function testConnection($conn) {
    // Get database info
    $dbInfo = [
        'server' => defined('DB_SERVER') ? DB_SERVER : 'unknown',
        'name' => defined('DB_NAME') ? DB_NAME : 'unknown',
        'connected' => $conn->connect_errno === 0
    ];
    
    // Check integration tables
    $integrationTables = checkIntegrationTablesExist($conn);
    
    // Get system info
    $systemCount = 0;
    $result = $conn->query("SELECT COUNT(*) AS count FROM system_integrations");
    if ($result) {
        $row = $result->fetch_assoc();
        $systemCount = $row['count'];
    }
    
    // Return connection status
    sendApiResponse([
        'success' => true,
        'connection' => [
            'status' => 'connected',
            'database' => $dbInfo,
            'integration_tables_exist' => $integrationTables,
            'registered_systems' => $systemCount
        ],
        'version' => '1.0',
        'api_documentation' => 'See INTEGRATION.md for detailed documentation'
    ]);
}

/**
 * Generates a secure API key
 * 
 * @return string Generated API key
 */
function generateApiKey() {
    // Generate a secure random string
    $randomBytes = random_bytes(32);
    
    // Convert to a URL-safe string
    $apiKey = 'skyagro_' . bin2hex($randomBytes);
    
    return $apiKey;
}
?> 