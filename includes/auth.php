<?php
// Simple auth - everything in one file

session_start();

define('PASSWORD', getenv('ADMIN_PASSWORD') ?: 'store2026');
define('STORE', 'Giza Kids');

function isLoggedIn() {
    return !empty($_SESSION['logged']) && $_SESSION['logged'] === true;
}

function login($pass) {
    if ($pass === PASSWORD) {
        $_SESSION['logged'] = true;
        $_SESSION['time'] = time();
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
}

function guard() {
    if (!isLoggedIn()) {
        header('Location: login');
        exit;
    }
}

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
