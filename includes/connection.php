<?php
$servername = "sql106.infinityfree.com";
$username = "if0_40823559";
$password = "AXLLcfFYfOFK1";
$dbname = "if0_40823559_pawnshop_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>