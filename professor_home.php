<?php
session_start();
require "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT Name FROM Users WHERE User_ID = ?");
$stmt->execute([$_SESSION["user_id"]]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT Course_ID, Course_Name, Tag_ID, Course_DateTime
    FROM Course
    WHERE professor_id = ?
");
$stmt->execute([$_SESSION["user_id"]]);
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="abc.css">
    <title>Professor Dashboard</title>
    <style>
        .course-box {
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 10px;
            width: 450px;
        }
        .status-box {
            margin-top: 10px;
            padding: 10px;
            border-radius: 6px;
        }
        .status-on  { color: #27ae60; font-weight: bold; }
        .status-off { color: #c0392b; font-weight: bold; }
        .dashboard-header {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<h1>Professor Dashboard</h1>
<h2>Welcome, Professor <?php echo htmlspecialchars($user["Name"]); ?>!</h2>

<!-- Setup Course button -->
<div style="margin: 16px 0 28px;">
    <a href="setup_course.php">
        <button class="card">+ Setup New Course</button>
    </a>
</div>

<?php foreach ($courses as $course): ?>
<?php
    // Check active session
    $stmt = $pdo->prepare("
        SELECT Session_ID FROM Class_Session
        WHERE Course_ID = ? AND Active = 1
        LIMIT 1
    ");
    $stmt->execute([$course["Course_ID"]]);
    $session = $stmt->fetch();
    $session_active = $session ? true : false;

    // Attendance count
    $attendance_count = 0;
    if ($session_active) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM Attendance
            WHERE Session_ID = ? AND Status = 'present'
        ");
        $stmt->execute([$session["Session_ID"]]);
        $attendance_count = $stmt->fetchColumn();
    }

    // Total enrolled
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Enrollment WHERE Course_ID = ?");
    $stmt->execute([$course["Course_ID"]]);
    $total_students = $stmt->fetchColumn();
?>

<div class="course-box">
    <h2><a href="course_detail.php?course_id=<?php echo $course['Course_ID']; ?>" style="color:inherit;text-decoration:none;border-bottom:2px solid #4a69bd;"><?php echo htmlspecialchars($course["Course_Name"]); ?></a></h2>
    <p>Tag ID: <?php echo htmlspecialchars($course["Tag_ID"]); ?></p>

    <div class="status-box">
        <?php if ($session_active): ?>
            <span class="status-on">Session Active</span>
            <p>Students Present: <strong><?php echo $attendance_count; ?></strong> / <?php echo $total_students; ?></p>
        <?php else: ?>
            <span class="status-off">Session Off</span>
        <?php endif; ?>
    </div>

    <form method="POST" action="start_session.php">
        <input type="hidden" name="course_id" value="<?php echo $course["Course_ID"]; ?>">
        <button type="submit">Start Session</button>
    </form>
    <form method="POST" action="end_session.php">
        <input type="hidden" name="course_id" value="<?php echo $course["Course_ID"]; ?>">
        <button type="submit">End Session</button>
    </form>
</div>

<?php endforeach; ?>

</body>
</html>
