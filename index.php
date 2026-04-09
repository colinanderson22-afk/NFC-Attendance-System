<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

/* If already logged in, go to the correct dashboard */
if (isset($_SESSION["user_id"])) {
    if ($_SESSION["role"] === "professor") {
        header("Location: professor_home.php");
    } else {
        header("Location: student_home.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="abc.css">
    <title>ABC Attendance</title>
</head>

<body>

<h1>Attendance ABC</h1>
<h3>Attendance that is as easy as ABC</h3>

<br>

<div class="index-actions">
    <a href="login.php">
        <button>Login</button>
    </a>
    <a href="register.php">
        <button>Register</button>
    </a>
</div>

<div style="text-align: center; margin-top: 24px;">
    <a href="about.php" style="font-size: 0.9rem; color: #6b7280;">About this project</a>
</div>

</body>
</html>
