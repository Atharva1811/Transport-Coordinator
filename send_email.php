<?php
header('Content-Type: application/json');

// Function to send email
function sendEmail($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: SkyAgro Transport <noreply@skyagro.com>' . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit;
}

// Extract data
$loadInfo = $data['load_info'];
$drivers = $data['drivers'];

$success = true;
$failedEmails = [];

// Prepare email template
$emailTemplate = "
<html>
<body>
    <h2>New Load Assignment</h2>
    <p>Dear Driver,</p>
    <p>A new load has been assigned to you:</p>
    
    <div>
        <p><strong>Title:</strong> {$loadInfo['title']}</p>
        <p><strong>Pickup:</strong> {$loadInfo['pickup_location']}</p>
        <p><strong>Delivery:</strong> {$loadInfo['delivery_location']}</p>
        <p><strong>Date:</strong> {$loadInfo['date']}</p>
        <p><strong>Weight:</strong> {$loadInfo['weight']} kg</p>
        <p><strong>Cost:</strong> \${$loadInfo['cost']}</p>
        <p><strong>Notes:</strong> {$loadInfo['notes']}</p>
    </div>
    
    <p>Please confirm your acceptance of this load assignment.</p>
    <p>SkyAgro Transport System</p>
</body>
</html>";

// Send emails to each driver
foreach ($drivers as $driver) {
    $subject = "New Load Assignment - " . $loadInfo['title'];
    
    if (!sendEmail($driver['email'], $subject, $emailTemplate)) {
        $success = false;
        $failedEmails[] = $driver['email'];
    }
}

// Return response
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Emails sent successfully' : 'Some emails failed to send',
    'failed_emails' => $failedEmails
]);
?> 