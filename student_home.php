<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require __DIR__ . "/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("You must be logged in as a student.");
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT Name FROM Users WHERE User_ID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT c.Course_ID, c.Course_Name
    FROM Course c
    JOIN Enrollment e ON c.Course_ID = e.Course_ID
    WHERE e.User_ID = ?
");
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="abc.css">
    <title>Student Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1, h2, h3 { text-align: center; }
        h4 { font-family: Garamond; font-size: 120%; }
        .course { border: 1px solid #ddd; padding: 15px; margin-bottom: 20px; border-radius: 8px; background-color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #f4f4f4; }
        .present { color: green; font-weight: bold; }
        .absent  { color: red;   font-weight: bold; }
        .session-open   { color: #2ecc71; font-weight: bold; }
        .session-closed { color: #aaa;    font-style: italic; }
    </style>
</head>
<body>
<h1>Student Dashboard</h1>
<h2>Welcome, <?php echo htmlspecialchars($user['Name']); ?>!</h2>

<div style="text-align: center; margin: 16px 0 28px;">
    <a href="register.php"><button>+ Enroll in Another Course</button></a>
</div>

<h2>Your Courses</h2>

<?php if (empty($courses)): ?>
    <p>You are not enrolled in any courses.</p>
<?php endif; ?>

<?php foreach ($courses as $course): ?>
    <div class="course">
        <h4><?php echo htmlspecialchars($course['Course_Name']); ?></h4>
        <?php
        $stmt = $pdo->prepare("
            SELECT cs.Start_Time, cs.Active, a.Status
            FROM Attendance a
            JOIN Class_Session cs ON a.Session_ID = cs.Session_ID
            WHERE a.User_ID = ? AND cs.Course_ID = ?
            ORDER BY cs.Start_Time DESC
        ");
        $stmt->execute([$user_id, $course['Course_ID']]);
        $attendance = $stmt->fetchAll();
        ?>

        <?php if (!empty($attendance)): ?>
            <table>
                <tr>
                    <th>Session Date</th>
                    <th>Attendance</th>
                    <th>Session Status</th>
                </tr>
                <?php foreach ($attendance as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars(date("F j, Y g:i A", strtotime($row['Start_Time']))); ?></td>
                        <td class="<?php echo htmlspecialchars($row['Status']); ?>">
                            <?php echo ucfirst($row['Status']); ?>
                        </td>
                        <td class="<?php echo $row['Active'] ? 'session-open' : 'session-closed'; ?>">
                            <?php echo $row['Active'] ? 'Open' : 'Closed'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No attendance recorded yet.</p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

</body>
</html>
