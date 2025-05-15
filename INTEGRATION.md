# SkyAgro Transport Coordinator - Integration Guide

This document provides detailed instructions for integrating the SkyAgro Transport Coordinator system with other websites and external systems using MySQL as the shared data source.

## Table of Contents

1. [Database Integration](#database-integration)
2. [API Endpoints](#api-endpoints)
3. [Authentication & Security](#authentication--security)
4. [Common Integration Scenarios](#common-integration-scenarios)
5. [Data Synchronization Strategies](#data-synchronization-strategies)
6. [Troubleshooting](#troubleshooting)

## Database Integration

### Database Schema

The SkyAgro Transport Coordinator uses the following MySQL database structure:

- **users**: Authentication and user management
- **transporters**: Driver information and status
- **dispatch_assignments**: Records of driver assignments to dispatch
- **load_assignments**: Information about transport loads
- **driver_load_assignments**: Junction table linking drivers to loads

### Shared Database Approach

#### Option 1: Direct Database Access

For tight integration within the same organization, you can have multiple systems connect to the same database:

1. Configure all systems to use the same MySQL server and database
2. Ensure consistent table prefixes to avoid conflicts (e.g., `skyagro_users` vs `othersystem_users`)
3. Use the provided `db_config.php` as a template for connection settings

Example configuration:

```php
// SkyAgro DB Config
define('DB_SERVER', 'shared-mysql-server.example.com');
define('DB_USERNAME', 'shared_db_user');
define('DB_PASSWORD', 'secure_password');
define('DB_NAME', 'shared_transportation_db');
```

#### Option 2: Database Replication

For systems with high volume or geographic distribution:

1. Set up MySQL replication between primary and secondary instances
2. Configure the Transport Coordinator as either the master or a slave
3. Implement appropriate replication strategy (Master-Slave, Master-Master)
4. Set up appropriate replication monitoring

### Extended Schema for Integration

To enable better integration, consider adding these additional tables:

```sql
-- Integration table for external system references
CREATE TABLE system_integrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_name VARCHAR(100) NOT NULL,
    system_url VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cross-reference table for mapping external IDs
CREATE TABLE external_references (
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
CREATE TABLE integration_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_id INT NOT NULL,
    event_type ENUM('push', 'pull', 'error', 'sync') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('success', 'failure', 'pending') NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (system_id) REFERENCES system_integrations(id) ON DELETE CASCADE
);
```

Execute these SQL statements to add integration support tables.

## API Endpoints

The SkyAgro Transport Coordinator exposes several REST API endpoints that can be used by external systems:

### Authentication

**Endpoint**: `/api/login.php`  
**Method**: POST  
**Description**: Authenticates a user and returns a session token  
**Parameters**:
- `username`: User's login name
- `password`: User's password

### Get Available Drivers

**Endpoint**: `/api/get_drivers.php`  
**Method**: GET  
**Description**: Returns a list of all transporters/drivers  
**Parameters**: None

### Get Driver Details

**Endpoint**: `/api/get_driver.php`  
**Method**: GET  
**Description**: Returns details for a specific driver  
**Parameters**:
- `id`: Driver's ID

### Get Loads

**Endpoint**: `/api/get_loads.php`  
**Method**: GET  
**Description**: Returns a list of all load assignments  
**Parameters**: None

### Forward Driver to Dispatch

**Endpoint**: `/api/forward_driver.php`  
**Method**: POST  
**Description**: Forwards a driver to the dispatch coordinator  
**Parameters**:
- `driver_id`: ID of the driver to forward
- `notes`: Additional notes for dispatch
- `date_time`: Timestamp of the forwarding action

### Send Load Information

**Endpoint**: `/api/send_load_info.php`  
**Method**: POST  
**Description**: Assigns a load to one or more drivers  
**Parameters**:
- `load_id`: ID of the existing load or null for a new load
- `title`: Load title (for new loads)
- `pickup_location`: Pickup address (for new loads) 
- `delivery_location`: Delivery address (for new loads)
- `date`: Load date (for new loads)
- `weight`: Load weight in kg (for new loads)
- `cost`: Estimated cost (for new loads)
- `notes`: Additional notes (for new loads)
- `driver_ids`: Array of driver IDs to assign the load to

### Delete Driver

**Endpoint**: `/api/delete_driver.php`  
**Method**: DELETE  
**Description**: Removes a driver from the system  
**Parameters**:
- `id`: ID of the driver to delete

## Authentication & Security

### API Key Authentication

For integrating external systems, implement an API key authentication mechanism:

1. Create API keys for each external system in the `system_integrations` table
2. Require API key in the headers of all API requests:

```php
// Example of API key verification in PHP
function verifyApiKey($conn, $apiKey) {
    $sql = "SELECT id FROM system_integrations WHERE api_key = ? AND active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['id'];
    }
    
    return false;
}

// Usage in API endpoints
$headers = getallheaders();
$apiKey = isset($headers['X-API-Key']) ? $headers['X-API-Key'] : '';

$systemId = verifyApiKey($conn, $apiKey);
if (!$systemId) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}
```

### Securing Database Access

1. Create separate MySQL users for each system with appropriate permissions
2. Use least privilege principle - grant only necessary permissions
3. Example MySQL user creation:

```sql
CREATE USER 'external_app'@'%' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE ON skyagro_transport.transporters TO 'external_app'@'%';
GRANT SELECT ON skyagro_transport.load_assignments TO 'external_app'@'%';
FLUSH PRIVILEGES;
```

## Common Integration Scenarios

### Scenario 1: Integrating with a Fleet Management System

1. **Database Mapping**:
   - Map driver IDs between systems using the `external_references` table
   - Regularly synchronize driver status changes

2. **Implementation Steps**:
   ```php
   // Example of pushing driver status updates to the SkyAgro system
   function updateDriverStatus($externalDriverId, $status) {
       global $conn;
       
       // Find the SkyAgro driver ID from external reference
       $sql = "SELECT local_id FROM external_references 
               WHERE system_id = ? AND external_id = ? AND local_table = 'transporters'";
       $stmt = $conn->prepare($sql);
       $stmt->bind_param("is", $SYSTEM_ID, $externalDriverId);
       $stmt->execute();
       $result = $stmt->get_result();
       
       if ($result->num_rows > 0) {
           $row = $result->fetch_assoc();
           $driverId = $row['local_id'];
           
           // Update the driver status
           $update = "UPDATE transporters SET status = ? WHERE id = ?";
           $updateStmt = $conn->prepare($update);
           $updateStmt->bind_param("si", $status, $driverId);
           
           if ($updateStmt->execute()) {
               return true;
           }
       }
       
       return false;
   }
   ```

### Scenario 2: Integrating with an Order Management System

1. **Database Mapping**:
   - Map load assignments to orders in the external system
   - Share driver assignments between systems

2. **Implementation Steps**:
   ```php
   // Example of creating a new load from an external order
   function createLoadFromOrder($orderData) {
       global $conn;
       
       // Begin transaction
       $conn->begin_transaction();
       
       try {
           // Insert the load assignment
           $sql = "INSERT INTO load_assignments 
                  (title, pickup_location, delivery_location, load_date, weight, cost, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
           $stmt = $conn->prepare($sql);
           $stmt->bind_param("ssssdds", 
               $orderData['title'], 
               $orderData['pickup'], 
               $orderData['delivery'], 
               $orderData['date'],
               $orderData['weight'],
               $orderData['cost'],
               $orderData['notes']
           );
           $stmt->execute();
           $loadId = $conn->insert_id;
           
           // Create external reference
           $ref = "INSERT INTO external_references 
                  (system_id, local_table, local_id, external_id) 
                  VALUES (?, 'load_assignments', ?, ?)";
           $refStmt = $conn->prepare($ref);
           $refStmt->bind_param("iis", $SYSTEM_ID, $loadId, $orderData['order_id']);
           $refStmt->execute();
           
           // Commit transaction
           $conn->commit();
           return $loadId;
       } catch (Exception $e) {
           // Roll back on error
           $conn->rollback();
           throw $e;
       }
   }
   ```

## Data Synchronization Strategies

### Real-time Synchronization

For immediate updates between systems:

1. Implement webhooks that trigger on important events:

```php
// Example webhook receiver for driver status changes
function processDriverStatusWebhook($data) {
    global $conn;
    
    // Validate the webhook data
    if (!isset($data['driver_id']) || !isset($data['status'])) {
        return false;
    }
    
    // Find the local driver ID
    $externalId = $data['driver_id'];
    $status = $data['status'];
    
    // Map external status to SkyAgro status
    $statusMap = [
        'on_duty' => 'available',
        'on_assignment' => 'assigned',
        'off_duty' => 'unavailable'
    ];
    
    $skyagroStatus = isset($statusMap[$status]) ? $statusMap[$status] : 'unavailable';
    
    // Update the driver status
    return updateDriverStatus($externalId, $skyagroStatus);
}
```

2. Implement API endpoints for external systems to push updates:

```php
// Example API endpoint for receiving driver updates
// Place this in a new file: api/external_driver_update.php
<?php
require_once '../db_config.php';

// Verify API key
$headers = getallheaders();
$apiKey = isset($headers['X-API-Key']) ? $headers['X-API-Key'] : '';

$systemId = verifyApiKey($conn, $apiKey);
if (!$systemId) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

// Process the update
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

// Update driver status
$result = processDriverStatusWebhook($data);

// Return result
if ($result) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Failed to update driver status']);
}
?>
```

### Scheduled Synchronization

For less time-sensitive data or batch updates:

1. Create a cron job that periodically checks for updates:

```php
// Example cron job script for syncing driver data
// Save as sync_drivers.php
<?php
require_once 'db_config.php';

// Get last sync time
$lastSyncTime = getLastSyncTime($conn, 'transporters');

// Fetch updated records from external system
$externalDrivers = fetchExternalDrivers($lastSyncTime);

// Update local records
foreach ($externalDrivers as $driver) {
    updateLocalDriver($conn, $driver);
}

// Update sync time
updateSyncTime($conn, 'transporters');

function getLastSyncTime($conn, $entity) {
    $sql = "SELECT MAX(created_at) as last_sync FROM integration_events 
            WHERE entity_type = ? AND event_type = 'sync' AND status = 'success'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $entity);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['last_sync'] ?: '1970-01-01 00:00:00';
    }
    
    return '1970-01-01 00:00:00';
}

function updateSyncTime($conn, $entity) {
    $sql = "INSERT INTO integration_events 
            (system_id, event_type, description, status, entity_type) 
            VALUES (?, 'sync', 'Scheduled sync completed', 'success', ?)";
    $stmt = $conn->prepare($sql);
    $systemId = 1; // Change to your system ID
    $stmt->bind_param("is", $systemId, $entity);
    return $stmt->execute();
}
?>
```

2. Set up the cron job to run at your desired interval:

```
# Run driver sync every 15 minutes
*/15 * * * * php /path/to/sync_drivers.php >> /var/log/driver_sync.log 2>&1
```

## Troubleshooting

### Common Integration Issues

1. **Database Connection Failures**
   - Check MySQL credentials and permissions
   - Verify network connectivity between systems
   - Ensure database server allows remote connections

2. **Data Mapping Issues**
   - Use the `external_references` table to maintain data relationships
   - Implement error logging for failed mappings
   - Create a reconciliation process for orphaned records

3. **API Authentication Problems**
   - Verify API keys are correctly stored and compared
   - Check for HTTPS requirements (API keys should only be transmitted over secure connections)
   - Implement request logging for debugging

4. **Data Synchronization Delays**
   - Monitor cron job execution logs
   - Implement timeout handling for external API calls
   - Create alerts for failed synchronization attempts

### Logging Integration Events

Always log integration activities for troubleshooting:

```php
function logIntegrationEvent($conn, $systemId, $eventType, $description, $status, $entityType = null, $entityId = null) {
    $sql = "INSERT INTO integration_events 
           (system_id, event_type, description, status, entity_type, entity_id) 
           VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", $systemId, $eventType, $description, $status, $entityType, $entityId);
    return $stmt->execute();
}

// Usage example
logIntegrationEvent(
    $conn, 
    $systemId, 
    'push', 
    'Pushed driver status update to Fleet System', 
    'success', 
    'transporters', 
    $driverId
);
```

### MySQL Performance Considerations

1. **Indexing Key Columns**
   - Add indexes to frequently queried fields:
   ```sql
   ALTER TABLE transporters ADD INDEX idx_status (status);
   ALTER TABLE load_assignments ADD INDEX idx_load_date (load_date);
   ALTER TABLE external_references ADD INDEX idx_external_id (external_id);
   ```

2. **Query Optimization**
   - Use prepared statements for all queries
   - Limit result sets when possible
   - Use appropriate JOIN types for relationship queries

3. **Connection Pooling**
   - Implement connection pooling for high-traffic integrations
   - Consider a connection manager library for PHP applications

---

## Implementation Checklist

- [ ] Set up shared database or replication
- [ ] Create integration-specific database users
- [ ] Add integration tables to schema
- [ ] Implement API key authentication
- [ ] Set up data mapping between systems
- [ ] Configure synchronization method (real-time or scheduled)
- [ ] Implement error handling and logging
- [ ] Test integration with sample data
- [ ] Monitor system performance after integration

For further assistance with integration, contact the SkyAgro development team at dev@skyagro.example.com. 