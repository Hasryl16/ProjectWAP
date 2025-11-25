<?php
session_start();
include '../connection.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sunrise - Booking Hotel Terpercaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1> Hello World </h1>
    <?php include 'header.php'; ?>
    <?php include 'hero.php'; ?>
    <?php include 'hotels.php'; ?>
    <?php include 'about.php'; ?>
    <?php include 'services.php'; ?>
    <?php include 'footer.php'; ?>
</body>
</html>
