<?php
$host = 'db';              // <-- numele serviciului, NU localhost!
$user = 'user';
$pass = 'userpass';
$dbname = 'site_db';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Eroare: " . $e->getMessage());
}

?>