# SkyAgro Transport Coordinator - Connection Guide

This document explains how to connect this application to a PHP/MySQL server once you're ready to implement the backend functionality.

## Current Implementation

Currently, the application is implemented as a client-side mockup with:

1. `index.html` - Entry point that redirects to login
2. `login.html` - Login page with static credentials
3. `coordinator_dashboard.html` - Dashboard for transport coordinators
4. `driver_profile.html` - Driver profile page with static data

## Setting Up PHP and MySQL

### 1. Install a Local Development Environment

The easiest way to set up PHP and MySQL is to install one of these packages:

- **XAMPP**: https://www.apachefriends.org/ (Windows, Mac, Linux)
- **WAMP**: https://www.wampserver.com/ (Windows)
- **MAMP**: https://www.mamp.info/ (Mac, Windows)

#### Installation Steps:

1. Download and install the package for your operating system
2. Start the Apache and MySQL services from the control panel
3. The default web root directory is typically:
   - XAMPP: `C:\xampp\htdocs` (Windows) or `/Applications/XAMPP/htdocs` (Mac)
   - WAMP: `C:\wamp64\www`
   - MAMP: `/Applications/MAMP/htdocs`

### 2. Set Up the Database

1. Open phpMyAdmin (usually at http://localhost/phpmyadmin)
2. Create a new database named `transport_db`
3. Import the database structure from `database_init.sql` if available
4. If not available, you'll need to create these tables:
   - `transporters` - Driver/transporter information
   - `load_assignments` - Load details (title, locations, weight, etc.)
   - `driver_load_assignments` - Junction table linking drivers to loads

### 3. Update Database Configuration

1. Edit the `db_config.php` file to match your database settings:

```php
<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'your_username'); // Change this (default is often 'root')
define('DB_PASSWORD', 'your_password'); // Change this
define('DB_NAME', 'transport_db');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect to the database. " . mysqli_connect_error());
}
?>
```

### 4. Move Files to Web Server

1. Copy all files from this project to your web server's document root directory
2. Ensure the file structure is maintained
3. Make sure you include all HTML, PHP, and CSS files

### 5. Update Client-Side Code

For a complete implementation, you'll need to:

1. Replace the simulated authentication in `login.html` with real PHP authentication:
   - Create a `login.php` file to handle POST requests from the login form
   - Add server-side validation and session management
   - Update the login form's action attribute to point to `login.php`

2. Create PHP backend files for the coordinator dashboard:
   - `get_drivers.php` - To fetch available drivers for the dashboard
   - `get_dashboard_stats.php` - To fetch statistics for the dashboard
   - `forward_to_dispatch.php` - To handle driver forwarding
   - `get_driver_profile.php` - To fetch driver profile data

3. Create a load management system:
   - `load_form.php` - UI for creating/editing loads
   - `save_load.php` - Backend for saving load information
   - `assign_load.php` - Backend for assigning loads to drivers

### 6. Testing

1. Navigate to http://localhost/your-project-folder/ in your web browser
2. The index.html file should redirect to the login page
3. Use the test credentials to log in:
   - Coordinator: transport1/transport1
   - Driver: driver1/driver1

## PHP Email Configuration

To enable email functionality for notifications, you'll need to:

1. Configure a local mail server or use an SMTP service
2. Create a mailer helper function or class
3. For production, consider using a proper email library like PHPMailer

### Sample PHP Mailer Implementation:

```php
// Using PHPMailer (you'd need to install it via Composer)
function sendEmail($to, $subject, $body, $isHtml = true) {
    require 'vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com';
        $mail->Password = 'your-password';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('transport@skyagro.com', 'SkyAgro Transport');
        $mail->addAddress($to);
        $mail->addReplyTo('transport@skyagro.com', 'SkyAgro Transport');
        
        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
```

## Security Considerations

Before deploying to production:

1. Implement proper user authentication and session management
2. Use prepared statements for all database queries
3. Add input validation and sanitization
4. Enable HTTPS for secure data transmission
5. Implement proper password hashing (bcrypt, Argon2)
6. Add CSRF protection
7. Configure proper file permissions

## Need Help?

If you need assistance with the PHP/MySQL implementation, consider:

1. Consulting the documentation for your web server package (XAMPP, WAMP, etc.)
2. Referring to PHP and MySQL documentation
3. Hiring a web developer familiar with PHP backend development 