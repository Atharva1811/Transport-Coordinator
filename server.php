<?php
// Start PHP's built-in server
$host = 'localhost';
$port = 8000;

echo "Starting server at http://$host:$port\n";
echo "Press Ctrl+C to stop the server\n";

// Start the server
$command = "php -S $host:$port";
system($command);
?> 