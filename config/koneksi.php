<?php
if(session_status() === PHP_SESSION_NONE) { session_start(); }
$host = 'localhost';
$db   = 'db_hipertensi_cerme';
$user = 'root'; // Sesuaikan jika ada username khusus
$pass = '';     // Sesuaikan jika ada password database
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Koneksi Database Gagal: " . $e->getMessage());
}
?>
