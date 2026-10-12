<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token() {
    if (
        !isset($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token']) ||
        $_SESSION['csrf_token'] === ''
    ) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') .
        '">';
}

function csrf_validate() {
    if (
        !isset($_POST['csrf_token']) ||
        !is_string($_POST['csrf_token']) ||
        !isset($_SESSION['csrf_token']) ||
        !is_string($_SESSION['csrf_token'])
    ) {
        return false;
    }

    return hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token']
    );
}

function csrf_regenerate() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
