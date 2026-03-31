<?php
session_start();
require __DIR__ . "/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

$professor_id = $_SESSION['user_id'];
$course_id    = intval($_GET['course_id'] ?? 0);

if (!$course_id) {
    die("Invalid course.");
}

// Verify this course belongs to this professor
$stmt = $pdo->prepare("
    SELECT Course_Name FROM Course
    WHERE Course_ID = ? AND professor_id = ?
");
$stmt->execute([$course_id, $professor_id]);
$course = $stmt->fetch();

if (!$course) {
    die("Course not found.");
}

// Get all sessions for this course
$stmt = $pdo->prepare("
    SELECT Session_ID, Start_Time, Active
    FROM Class_Session
    WHERE Course_ID = ?
    ORDER BY Start_Time DESC
");
$stmt->execute([$course_id]);
$sessions = $stmt->fetchAll();

$total_sessions = count($sessions);

// Get roster with attendance stats
$stmt = $pdo->prepare("
    SELECT
        u.User_ID,
        u.Name,
        u.Email,
        COUNT(a.Session_ID) AS sessions_attended
    FROM Enrollment e
    JOIN Users u ON e.User_ID = u.User_ID
    LEFT JOIN Attendance a
        ON a.User_ID = u.User_ID
        AND a.Status = 'present'
        AND a.Session_ID IN (
            SELECT Session_ID FROM Class_Session WHERE Course_ID = ?
        )
    WHERE e.Course_ID = ?
    GROUP BY u.User_ID, u.Name, u.Email
    ORDER BY u.Name ASC
");
$stmt->execute([$course_id, $course_id]);
$roster = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="abc.css">
    <title><?php echo htmlspecialchars($course['Course_Name']); ?> — Detail</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f6fb; }
        h1, h2, h3 { text-align: center; }
        .back-link { display: block; text-align: center; margin-bottom: 24px; font-size: 0.95rem; }

        /* --- Sections --- */
        .section-box {
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 30px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        .section-box h3 {
            margin-bottom: 14px;
            font-size: 1.1rem;
            color: #333;
            text-align: left;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }

        /* --- Tables --- */
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 9px 12px; text-align: left; }
        th { background: #f4f4f4; font-size: 0.88rem; text-transform: uppercase; letter-spacing: 0.04em; }
        td { font-size: 0.95rem; }

        /* --- Session status badges --- */
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .badge-open   { background: #d4efdf; color: #1e8449; }
        .badge-closed { background: #f0f0f0; color: #888; }

        /* --- Attendance fraction --- */
        .stat-good { color: #27ae60; font-weight: bold; }
        .stat-warn { color: #e67e22; font-weight: bold; }
        .stat-bad  { color: #c0392b; font-weight: bold; }
    </style>
</head>
<body>

<h1><?php echo htmlspecialchars($course['Course_Name']); ?></h1>
<a class="back-link" href="professor_home.php">← Back to Dashboard</a>

<div class="section-box">
    <h3>Roster (<?php echo count($roster); ?> students)</h3>
    <?php if (empty($roster)): ?>
        <p>No students enrolled yet.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Attendance</th>
            </tr>
            <?php foreach ($roster as $student):
                $attended = intval($student['sessions_attended']);
                // Attendance % for colour coding (only when sessions exist)
                if ($total_sessions > 0) {
                    $pct = $attended / $total_sessions;
                    $cls = $pct >= 0.8 ? 'stat-good' : ($pct >= 0.5 ? 'stat-warn' : 'stat-bad');
                } else {
                    $cls = '';
                }
                // Split Name into first / last
                $parts      = explode(" ", $student['Name'], 2);
                $first_name = $parts[0];
                $last_name  = $parts[1] ?? '';
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></td>
                    <td><?php echo htmlspecialchars($student['Email']); ?></td>
                    <td class="<?php echo $cls; ?>">
                        <?php echo "$attended / $total_sessions sessions"; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<!-- ── Past Sessions ── -->
<div class="section-box">
    <h3>Sessions (<?php echo $total_sessions; ?> total)</h3>
    <?php if (empty($sessions)): ?>
        <p>No sessions have been started yet.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Date &amp; Time</th>
                <th>Status</th>
            </tr>
            <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><?php echo date("F j, Y g:i A", strtotime($s['Start_Time'])); ?></td>
                    <td>
                        <?php if ($s['Active']): ?>
                            <span class="badge badge-open">Open</span>
                        <?php else: ?>
                            <span class="badge badge-closed">Closed</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<!-- ── Course Roster ── -->

</body>
</html>
