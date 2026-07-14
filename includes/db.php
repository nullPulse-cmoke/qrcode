<?php
// Simple DB connection - uses PostgreSQL via PDO (Supabase compatible)

function db() {
    static $c = null;
    if (!$c) {
        // Use Supabase connection string
        $dbUrl = getenv('DATABASE_URL') ?: '';
        
        if ($dbUrl) {
            // Parse URL like: postgresql://postgres:password@host:5432/postgres
            $c = new PDO($dbUrl);
        } else {
            // Fallback to individual params
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '5432';
            $user = getenv('DB_USER') ?: 'postgres';
            $pass = getenv('DB_PASS') ?: '';
            $name = getenv('DB_NAME') ?: 'shop_db';
            
            $dsn = "pgsql:host=$host;port=$port;dbname=$name";
            $c = new PDO($dsn, $user, $pass);
        }
        
        $c->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $c;
}

// Helper: execute query with params (returns PDOStatement)
function query($sql, $params = []) {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

// Helper: fetch all rows
function fetchAll($sql, $params = []) {
    return query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
}

// Helper: fetch one row
function fetchOne($sql, $params = []) {
    return query($sql, $params)->fetch(PDO::FETCH_ASSOC);
}
