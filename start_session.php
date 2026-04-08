<?php
session_start();
require __DIR__ . "/db.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "professor") {
    die("Unauthorized.");
}
if (!isset($_POST["course_id"])) {
    die("Invalid request.");
}

$course_id    = $_POST["course_id"];
$professor_id = $_SESSION["user_id"];
$duration     = max(1, min(180, (int)($_POST["duration"] ?? 30))); // clamp 1–180 mins

try {
    $stmt = $pdo->prepare("
        SELECT cs.Session_ID, c.Course_Name
        FROM Class_Session cs
        JOIN Course c ON cs.Course_ID = c.Course_ID
        WHERE c.professor_id = ? AND cs.Active = 1
        LIMIT 1
    ");
    $stmt->execute([$professor_id]);
    $already_active = $stmt->fetch();

    if ($already_active) {
        $name = htmlspecialchars($already_active['Course_Name']);
        die("You already have an active session in \"$name\". Please end it before starting a new one.");
    }

    $stmt = $pdo->prepare("
        INSERT INTO Class_Session (Course_ID, Start_Time, Active, Duration_Minutes)
        VALUES (?, NOW(), 1, ?)
    ");
    $stmt->execute([$course_id, $duration]);
    $session_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT User_ID FROM Enrollment WHERE Course_ID = ?");
    $stmt->execute([$course_id]);
    $students = $stmt->fetchAll();

    $insertStmt = $pdo->prepare("
        INSERT INTO Attendance (Session_ID, User_ID, Session_Date, Status)
        VALUES (?, ?, NOW(), 'absent')
    ");
    foreach ($students as $student) {
        $insertStmt->execute([$session_id, $student['User_ID']]);
    }

    header("Location: professor_home.php");
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
