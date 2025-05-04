<?php
$servername = "10.1.0.47";
$username = "root";
$password = "pTT!CT01";
$dbname = "map_ptt";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
