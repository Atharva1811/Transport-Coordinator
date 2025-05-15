# SkyAgro Transport Coordinator System

A simple web-based transport coordinator system for SkyAgro company that includes login functionality, driver management, and the ability to forward drivers to the dispatch coordinator.

## Features

- User authentication for Transport Coordinators
- Dashboard with key metrics (Total Transporters, Available Today, Assigned Orders, New Transporters)
- Driver management with listing, status tracking, and forwarding capabilities
- Forwarding drivers to Dispatch Coordinator with automatic date/time capture
- Responsive design for desktop and mobile devices

## Requirements

- PHP 7.0 or higher
- MySQL 5.7 or higher
- Web server (Apache, Nginx, etc.)

## Installation

1. Clone or download this repository to your web server's document root directory.

2. Set up a MySQL database:
   - Create a new database in MySQL or use an existing one.
   - Import the `database_init.sql` file to set up the tables and sample data.
   - You can run the SQL script using phpMyAdmin or the MySQL command line:
     ```
     mysql -u username -p < database_init.sql
     ```

3. Configure the database connection:
   - Open `db_config.php` and update the database credentials:
     ```php
     define('DB_SERVER', 'localhost');
     define('DB_USERNAME', 'your_username');
     define('DB_PASSWORD', 'your_password');
     define('DB_NAME', 'skyagro_transport');
     ```

4. Test the application:
   - Navigate to the index.html file in your web browser.
   - Log in using the sample credentials:
     - Username: transport
     - Password: transport123

## Usage

1. **Login**:
   - Use your assigned username and password to log in.

2. **Dashboard**:
   - View key metrics in the dashboard.
   - The list of transporters (drivers) is displayed in a table.

3. **Managing Transporters**:
   - View all transporters, their status, and vehicle type.
   - Forward available transporters to the Dispatch Coordinator by clicking the "Forward" button.
   - Delete transporters from the system if needed.

4. **Forwarding to Dispatch**:
   - Click the "Forward" button next to an available driver.
   - Add any necessary notes in the forwarding form.
   - The current date and time are automatically filled in.
   - Submit the form to forward the driver to the Dispatch Coordinator.

## Security Notes

- This is a simple implementation for demonstration purposes.
- In a production environment, consider the following security enhancements:
  - Use HTTPS to encrypt data transmission.
  - Implement proper password hashing (bcrypt, Argon2, etc.).
  - Add more robust input validation and sanitization.
  - Implement proper authentication and session management.
  - Add CSRF protection on forms.

## License

This project is licensed under the MIT License. 