<?php
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = $_POST["email"];

    $stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user) {

        $_SESSION["user_id"] = $user["User_ID"];
        $_SESSION["role"] = $user["role"];

        // If user was redirected here from scan.php
        if (isset($_GET["redirect"])) {
            header("Location: " . $_GET["redirect"]);
            exit;
        }

        // Otherwise go to normal dashboards
        if ($user["role"] === "professor") {
            header("Location: professor_home.php");
        } else {
            header("Location: student_home.php");
        }

        exit;

    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="abc.css">
    <title>Login</title>
</head>
<body>

<h1>Login</h1>
<div class="form-box">
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <br><br>
        <button type="submit">Login</button>
    </form>

    <?php if (isset($error)) echo "<p class='error-msg'>$error</p>"; ?>

    <div style="margin-top: 16px;">
        <a href="index.php"><button class="btn-secondary">Cancel</button></a>
    </div>
</div>

</body>
</html>
