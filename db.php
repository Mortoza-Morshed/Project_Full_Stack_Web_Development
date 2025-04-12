<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "energy_tracker";

$conn = new mysqli($servername, $username, $password, $database);

if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?> 