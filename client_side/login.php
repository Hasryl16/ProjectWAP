<?php
session_save_path('/var/lib/php/sessions');
session_start();
include '../connection.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $conn = getConnection();

    $stmt = $conn->prepare("SELECT user_id, name, email, password, role FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (hash('sha256', $password) === $user['password']) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            if ($user['role'] === 'admin') {
                header("Location: ../admin_side/admin.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $message = "Invalid password.";
        }
    } else {
        $message = "User not found.";
    }

    $stmt->close();
    $conn->close();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Sunrise Hotel</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>
        <?php if ($message): ?>
            <p style="color: red; text-align: center;"><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="user-box">
                <input type="email" name="email" required="">
                <label>Email</label>
            </div>
            <div class="user-box">
                <input type="password" name="password" required="">
                <label>Password</label>
            </div>
            <button type="submit" style="background: none; border: none; color: #03e9f4; cursor: pointer; font-size: 16px; text-transform: uppercase; margin-top: 40px; letter-spacing: 4px;">Login</button>
        </form>
        <p style="text-align: center; margin-top: 20px;"><a href="register.php" style="color: #03e9f4; text-decoration: none;">Sign Up</a></p>
    </div>
</body>
</html>
