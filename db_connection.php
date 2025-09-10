<?php
$servername = "localhost";
$username = "root"; // This is the default username for XAMPP. If you changed it, use the new username.
$password = ""; // This is the default password for XAMPP. If you set a password, enter it here.
$dbname = "school_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
