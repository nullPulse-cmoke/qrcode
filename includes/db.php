<?php
// Simple DB connection - use env vars on Vercel

function db() {
    static $c = null;
    if (!$c) {
        $host = getenv('DB_HOST') ?: 'localhost';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $name = getenv('DB_NAME') ?: 'shop_db';
        
        $c = new mysqli($host, $user, $pass, $name);
        if ($c->connect_error) die('DB error');
        $c->set_charset('utf8mb4');
    }
    return $c;
}
