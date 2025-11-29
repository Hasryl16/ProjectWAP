<?php
session_save_path('/var/lib/php/sessions');
session_start();
include '../connection.php';


if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];

    $conn = getConnection();

    
    $stmt = $conn->prepare("SELECT user_id FROM booking WHERE booking_id = ?");
    $stmt->bind_param("s", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();

        if ($booking['user_id'] === $user_id) {
            
            $stmt = $conn->prepare("UPDATE booking SET status = 'cancelled' WHERE booking_id = ?");
            $stmt->bind_param("s", $booking_id);

            if ($stmt->execute()) {                
                header("Location: booking_history.php?message=cancelled");
                exit();
            } else {
                
                header("Location: booking_history.php?error=cancel_failed");
                exit();
            }
        } else {
            header("Location: booking_history.php?error=unauthorized");
            exit();
        }
    } else {
        header("Location: booking_history.php?error=not_found");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: booking_history.php");
    exit();
}
?>
