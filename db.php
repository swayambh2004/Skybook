<?php
/**
 * Dynamic Environment Loader
 * Safely parses the root .env file without requiring external composer dependencies.
 */
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments starting with #
        if (strpos(trim($line), '#') === 0) continue;
        
        // Split by the first '=' found
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

// Load environment configurations from the root folder
loadEnv(__DIR__ . '/.env');

/* 🔐 Fallback Hierarchy: Check environment first, default back to XAMPP values if missing */
$host = isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost';
$user = isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : 'root';
$pass = isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : '';
$db   = isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'flight_booking';

// Establish secure object-oriented connection
$conn = new mysqli($host, $user, $pass, $db);

// Verify Connection integrity
if ($conn->connect_error) {
    die("Database connection failed. Database handles are offline.");
}

// Set charset to UTF-8 to prevent SQL injection vulnerabilities via multi-byte encoding characters
$conn->set_charset("utf8mb4");
?>