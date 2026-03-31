<?php
session_start();
require __DIR__ . "/db.php";

// Make sure course_id is provided
if (!isset($_GET["course_id"])) {
    die("Invalid scan: no course_id provided.");
}

// Make sure user is logged in as student
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login Required</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                margin-top: 80px;
            }
            .status-box {
                border: 1px solid #ddd;
                padding: 40px;
                width: 350px;
                margin: auto;
                border-radius: 10px;
                box-shadow: 0px 2px 8px rgba(0,0,0,0.15);
            }
            .error { color: #e74c3c; }
            .login-btn {
                margin-top: 20px;
                padding: 10px 20px;
                font-size: 16px;
                background-color: #3498db;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
            }
        </style>
    </head>
    <body>
    <div class="status-box">
        <h2 class="error">Login Required</h2>
        <p>You must be logged in as a student to record attendance.</p>
        <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="login-btn">Log In</a>
    </div>
    </body>
    </html>
    <?php
    exit;
}

$course_id = $_GET["course_id"];
$user_id = $_SESSION["user_id"];

try {
    // Find the active session for this course
    $stmt = $pdo->prepare("
        SELECT Session_ID
        FROM Class_Session
        WHERE Course_ID = ? AND Active = 1
        ORDER BY Start_Time DESC
        LIMIT 1
    ");
    $stmt->execute([$course_id]);
    $session = $stmt->fetch();

    if (!$session) {
        die("No active class session.");
    }

    $session_id = $session["Session_ID"];

    // Update attendance to present (Option B workflow)
    $updateStmt = $pdo->prepare("
        UPDATE Attendance
        SET Status = 'present', Session_Date = NOW()
        WHERE session_id = ? AND user_id = ?
    ");
    $updateStmt->execute([$session_id, $user_id]);

    // Check if row was affected
    if ($updateStmt->rowCount() > 0) {
        $success = true;
        $message = "Attendance recorded successfully!";
    } else {
        $success = false;
        $message = "Attendance already recorded or row missing.";
    }

} catch (PDOException $e) {
    // Catch any database error
    $success = false;
    $message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Status</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            text-align:center;
            margin-top:80px;
        }
        .status-box{
            border:1px solid #ddd;
            padding:40px;
            width:350px;
            margin:auto;
            border-radius:10px;
            box-shadow:0px 2px 8px rgba(0,0,0,0.15);
        }
        .status-box img{
            width:120px;
        }
        .success{ color:#2ecc71; }
        .error{ color:#e74c3c; }
        .home-btn{
            margin-top: 20px;
            padding: 10px 20px;
            font-size: 16px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="status-box">
    <?php if ($success): ?>
        <img src="checkmark.png" alt="Checkmark">
        <h2 class="success">Attendance Recorded</h2>
    <?php else: ?>
        <h2 class="error">Attendance Error</h2>
    <?php endif; ?>

    <p><?php echo htmlspecialchars($message); ?></p>
    <p><?php echo date("F j, Y g:i A"); ?></p>

    <a href="student_home.php"><button class="home-btn">Return to Home</button></a>
</div>

</body>
</html>
