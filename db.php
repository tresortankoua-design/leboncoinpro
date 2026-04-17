
<?php
// DB connection helper: provide both PDO ($pdo) and mysqli ($conn)
$DB_HOST = 'localhost';
$DB_NAME = 'leboncoin';
$DB_USER = 'root';
$DB_PASS = 'root';

// PDO
try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion PDO : " . $e->getMessage());
}

// mysqli (some pages still use procedural mysqli functions)
$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (!$conn) {
    die('Erreur de connexion MySQLi : ' . mysqli_connect_error());
}






