<?php
function getConnection(): mysqli {
    $servername = "mysql";
    $username = "root";
    $password = "1234";
    $dbname = "db__hotel";

    
    $conn = new mysqli($servername, $username, $password, $dbname);

    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn; 
}

$conn = getConnection();
?>