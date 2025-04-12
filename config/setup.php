<?php
// Connect to MySQL without selecting a database
$servername = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read the SQL file
$sql = file_get_contents('setup_database.sql');

// Execute the SQL commands
if ($conn->multi_query($sql)) {
    echo "Database setup successfully. <a href='../index.php'>Go to homepage</a>";
} else {
    echo "Error setting up database: " . $conn->error;
}

// Close connection
$conn->close();
?> 