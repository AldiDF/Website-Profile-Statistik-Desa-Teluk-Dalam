<?php
$server = "localhost";
$user = "root";
$password = "";
$db_nama = "db_telukdalam";



$conn = mysqli_connect($server, $user, $password, $db_nama);

if (!$conn) {
    die("Gagal terhubung ke database: " . mysqli_connect_error());
}
