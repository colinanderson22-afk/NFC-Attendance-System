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
    <title>ABC</title>
</head>

<body>

<h1>Attendance ABC</h1>
<h3>Attendance that is as easy as ABC</h3>

<br>

<div class="index-actions">
<a href="login.php">
<button class="card" onclick="window.location='login.php'">Login</button>
</a>

<br><br>

<a href="register.php">
<button class="card" onclick="window.location='register.php'">Register</button>
</a>
</div>
</body>
</html>
