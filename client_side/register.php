<?php
session_start();
include '../connection.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        $conn = getConnection();

        // Check if email already exists
        $stmt = $conn->prepare("SELECT email FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $message = "Email already exists.";
        } else {
            // Generate new user_id
            $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(user_id, 2) AS UNSIGNED)) AS max_id FROM user");
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $next_id = $row['max_id'] + 1;
            $user_id = 'U' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

            // Hash password
            $hashed_password = hash('sha256', $password);

            // Insert new user
            $stmt = $conn->prepare("INSERT INTO user (user_id, name, email, password, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->bind_param("ssss", $user_id, $name, $email, $hashed_password);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $message = "Registration failed. Please try again.";
            }
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Sunrise Hotel</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-box">
        <h2>Register</h2>
        <?php if ($message): ?>
            <p style="color: red; text-align: center;"><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="user-box">
                <input type="text" name="name" required="">
                <label>Name</label>
            </div>
            <div class="user-box">
                <input type="email" name="email" required="">
                <label>Email</label>
            </div>
            <div class="user-box">
                <input type="password" name="password" required="">
                <label>Password</label>
            </div>
            <div class="user-box">
                <input type="password" name="confirm_password" required="">
                <label>Confirm Password</label>
            </div>
            <button type="submit" style="background: none; border: none; color: #03e9f4; cursor: pointer; font-size: 16px; text-transform: uppercase; margin-top: 40px; letter-spacing: 4px;">Register</button>
        </form>
        <p style="text-align: center; margin-top: 20px;"><a href="login.php" style="color: #03e9f4; text-decoration: none;">Back to Login</a></p>
    </div>
</body>
</html>
