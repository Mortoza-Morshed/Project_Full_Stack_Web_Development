<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// message div ke liye
function set_message($message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// ChecK user has specific role
function has_role($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function redirect($location) {
    header("Location: $location");
    exit();
}

// Secure password hash
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_name($name) {
    return preg_match("/^[a-zA-Z ]*$/", $name);
}
?> 