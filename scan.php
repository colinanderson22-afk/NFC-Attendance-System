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
        <link rel="stylesheet" href="abc.css">
        <title>Login Required</title>
    </head>
    <body>
    <div class="scan-status-box">
        <h2 class="error">Login Required</h2>
        <p>You must be logged in as a student to record attendance.</p>
        <br>
        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">
                <button>Log In</button>
            </a>
            <a href="register.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">
                <button class="btn-secondary">Register</button>
            </a>
        </div>
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
    $success = false;
    $message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="abc.css">
    <title>Attendance Status</title>
</head>
<body>

<div class="scan-status-box">
    <?php if ($success): ?>
        <img src="checkmark.png" alt="Checkmark">
        <h2 class="success">Attendance Recorded</h2>
    <?php else: ?>
        <h2 class="error">Attendance Error</h2>
    <?php endif; ?>

    <p><?php echo htmlspecialchars($message); ?></p>
    <p style="margin-top: 6px; font-size: 0.88rem;"><?php echo date("F j, Y g:i A"); ?></p>

    <br>
    <a href="student_home.php"><button>Return to Home</button></a>
</div>

</body>
</html>
