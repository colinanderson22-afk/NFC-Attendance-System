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
    <style>
        .title-card {
            background: linear-gradient(135deg, #1a1d23 0%, #2c3e6b 100%);
            border-radius: 16px;
            padding: 48px 40px 40px;
            max-width: 480px;
            margin: 48px auto 32px;
            box-shadow: 0 8px 32px rgba(26,29,35,0.18);
            position: relative;
            overflow: hidden;
        }

        .title-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 15% 50%, rgba(74,105,189,0.3) 0%, transparent 60%),
                              radial-gradient(circle at 85% 15%, rgba(74,105,189,0.2) 0%, transparent 50%);
            pointer-events: none;
        }

        .title-card h1 {
            color: #ffffff;
            font-size: 2.2rem;
            margin-bottom: 10px;
            position: relative;
        }

        .title-card p {
            color: rgba(255,255,255,0.6);
            font-size: 1rem;
            margin: 0;
            position: relative;
        }

        .btn-about {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 24px;
            font-size: 0.88rem;
            font-family: inherit;
            font-weight: 500;
            background: transparent;
            color: #6b7280;
            border: 1.5px solid #dde1ea;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.18s, color 0.18s, background 0.18s;
            box-shadow: none;
            min-width: unset;
        }

        .btn-about:hover {
            border-color: #4a69bd;
            color: #4a69bd;
            background: #f0f4ff;
            transform: none;
            box-shadow: none;
        }
    </style>
</head>

<body>

<div class="title-card">
    <h1>ABC Attendance</h1>
    <p>Attendance that is as easy as ABC</p>
</div>

<div class="index-actions">
    <a href="login.php"><button>Login</button></a>
    <a href="register.php"><button>Register</button></a>
</div>

<div style="text-align: center; margin-top: 20px;">
    <a href="about.php" class="btn-about">ℹ️ About this project</a>
</div>

</body>
</html>
