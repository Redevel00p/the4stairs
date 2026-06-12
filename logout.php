<?php
/**
 * THE 4 STAIRS MUSIC HALL - LOGOUT
 * -------------------------------
 * Menghapus data session dan mengalihkan admin ke halaman login.
 */

session_start();

// Hapus semua data di session
$_SESSION = array();

// Hancurkan session cookie jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session server-side
session_destroy();

// Alihkan ke home
header("Location: index");
exit;
?>
