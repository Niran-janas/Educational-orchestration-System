<?php
session_start();

// 🔥 DESTROY SESSION COMPLETELY
$_SESSION = array();  // Clear all session variables
session_destroy();    // Destroy session data

// 🔥 Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 🔥 Redirect to login page
header("Location: ../index.php");
exit();
?>
