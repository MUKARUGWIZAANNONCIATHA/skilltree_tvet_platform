<?php
/**
 * Database Configuration
 * TVET Skill Tree Platform
 */

// Define constants only once
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'tvet_platform');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
}

// Establish PDO connection only once
if (!isset($pdo)) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $db = $pdo;
    } catch(PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        die("Database connection failed. Please check your configuration.");
    }
}

// Define helper functions only once
if (!function_exists('query')) {
    function query($sql, $params = []) {
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

if (!function_exists('getRow')) {
    function getRow($sql, $params = []) {
        $stmt = query($sql, $params);
        return $stmt->fetch();
    }
}

if (!function_exists('getRows')) {
    function getRows($sql, $params = []) {
        $stmt = query($sql, $params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('insert')) {
    function insert($table, $data) {
        global $pdo;
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        return $pdo->lastInsertId();
    }
}

if (!function_exists('update')) {
    function update($table, $data, $where, $whereParams = []) {
        global $pdo;
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "$key = :$key, ";
        }
        $set = rtrim($set, ', ');
        $sql = "UPDATE $table SET $set WHERE $where";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($data, $whereParams));
        return $stmt->rowCount();
    }
}

if (!function_exists('delete')) {
    function delete($table, $where, $params = []) {
        global $pdo;
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}

if (!function_exists('countRows')) {
    function countRows($table, $where = '', $params = []) {
        global $pdo;
        $sql = "SELECT COUNT(*) as total FROM $table";
        if ($where) {
            $sql .= " WHERE $where";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }
}