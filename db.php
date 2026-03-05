<?php
// Database connection using MySQLi
const DB_HOST = 'localhost';
const DB_USER = 'rsk9_05';
const DB_PASS = '123456';
const DB_NAME = 'rsk9_05';
 
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
 
if ($conn->connect_error) {
    die('Database connection failed: ' . htmlspecialchars($conn->connect_error));
}
 
$conn->set_charset('utf8mb4');
 
 
