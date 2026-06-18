<?php
session_start();

// 1. Clear all session variables
$_SESSION = array();

// 2. Destroy the browser cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the server-side session
session_destroy();

// 4. Prevent back-button caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 5. Redirect to login
header("Location: login.php?msg=logged_out");
exit();
?>