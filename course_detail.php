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
    SELECT Course_Name, Tag_ID FROM Course
    WHERE Course_ID = ? AND professor_id = ?
");
$stmt->execute([$course_id, $professor_id]);
$course = $stmt->fetch();

if (!$course) {
    die("Course not found.");
}

// Build scan URL
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$dir       = dirname($_SERVER['SCRIPT_NAME']);
$base_path = ($dir === '/' || $dir === '\\') ? '' : rtrim($dir, '/\\');
$scan_url  = $protocol . '://' . $host . $base_path . '/scan.php?course_id=' . $course_id;

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
    <meta charset="UTF-8">
    <link rel="stylesheet" href="abc.css">
    <title><?php echo htmlspecialchars($course['Course_Name']); ?> - Detail</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f4f6fb; }
        h1, h2, h3 { text-align: center; }
        .back-link { display: block; text-align: center; margin-bottom: 24px; font-size: 0.95rem; }

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

        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 9px 12px; text-align: left; }
        th { background: #f4f4f4; font-size: 0.88rem; text-transform: uppercase; letter-spacing: 0.04em; }
        td { font-size: 0.95rem; }

        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .badge-open   { background: #d4efdf; color: #1e8449; }
        .badge-closed { background: #f0f0f0; color: #888; }

        .stat-good { color: #27ae60; font-weight: bold; }
        .stat-warn { color: #e67e22; font-weight: bold; }
        .stat-bad  { color: #c0392b; font-weight: bold; }

        /* URL section */
        .url-row {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f4f6fb;
            border: 1px solid #dde1ea;
            border-radius: 7px;
            padding: 10px 14px;
            margin-bottom: 14px;
            font-family: monospace;
            font-size: 0.85rem;
            word-break: break-all;
        }
        .url-text { flex: 1; }
        .copy-btn {
            flex-shrink: 0;
            padding: 7px 16px;
            background: #2d5be3;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.83rem;
            font-weight: bold;
            cursor: pointer;
        }
        .copy-btn.copied { background: #1a8a4a; }

        .qr-wrap { text-align: center; margin-top: 10px; }
        .qr-wrap a { display: inline-block; margin-top: 8px; font-size: 0.82rem; color: #2d5be3; }

        /* Older sessions toggle */
        .older-toggle {
            width: 100%;
            background: #f4f4f4;
            border: none;
            border-top: 1px solid #ddd;
            padding: 10px 14px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: bold;
            color: #666;
            cursor: pointer;
        }
        .older-toggle:hover { background: #e8e8e8; }
        .older-sessions { display: none; }
        .older-sessions.open { display: table-row-group; }
    </style>
</head>
<body>

<h1><?php echo htmlspecialchars($course['Course_Name']); ?></h1>
<a class="back-link" href="professor_home.php">Back to Dashboard</a>

<!-- Scan URL + QR -->
<div class="section-box">
    <h3>Student Scan URL</h3>
    <div class="url-row">
        <span class="url-text" id="scan-url-text"><?php echo htmlspecialchars($scan_url); ?></span>
        <button class="copy-btn" id="copy-btn" onclick="copyURL()">Copy</button>
    </div>
    <div class="qr-wrap">
        <div id="qrcode"></div>
        <a id="qr-download" href="#" download="qr_<?php echo $course_id; ?>.png">Download QR Code</a>
    </div>
</div>

<!-- Roster -->
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
                if ($total_sessions > 0) {
                    $pct = $attended / $total_sessions;
                    $cls = $pct >= 0.8 ? 'stat-good' : ($pct >= 0.5 ? 'stat-warn' : 'stat-bad');
                } else {
                    $cls = '';
                }
                $parts     = explode(" ", $student['Name'], 2);
                $first     = $parts[0];
                $last      = $parts[1] ?? '';
            ?>
            <tr>
                <td><?php echo htmlspecialchars($first . ' ' . $last); ?></td>
                <td><?php echo htmlspecialchars($student['Email']); ?></td>
                <td class="<?php echo $cls; ?>"><?php echo "$attended / $total_sessions sessions"; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<!-- Sessions -->
<div class="section-box">
    <h3>Sessions (<?php echo $total_sessions; ?> total)</h3>
    <?php if (empty($sessions)): ?>
        <p>No sessions have been started yet.</p>
    <?php else:
        $recent = array_slice($sessions, 0, 3);
        $older  = array_slice($sessions, 3);
    ?>
        <table>
            <thead>
                <tr>
                    <th>Date &amp; Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $s): ?>
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
            </tbody>

            <?php if (!empty($older)): ?>
            <tbody class="older-sessions" id="older-sessions">
                <?php foreach ($older as $s): ?>
                <tr>
                    <td><?php echo date("F j, Y g:i A", strtotime($s['Start_Time'])); ?></td>
                    <td>
                        <span class="badge badge-closed">Closed</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <?php endif; ?>
        </table>

        <?php if (!empty($older)): ?>
        <button class="older-toggle" id="older-toggle" onclick="toggleOlder()">
            Show <?php echo count($older); ?> older session<?php echo count($older) !== 1 ? 's' : ''; ?>
        </button>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const scanUrl = <?php echo json_encode($scan_url); ?>;

// Generate QR
const qr = new QRCode(document.getElementById('qrcode'), {
    text: scanUrl,
    width: 160,
    height: 160,
    colorDark: '#1c1a17',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
});

// Set download link after QR renders
setTimeout(() => {
    const canvas = document.querySelector('#qrcode canvas');
    if (canvas) document.getElementById('qr-download').href = canvas.toDataURL('image/png');
}, 400);

// Copy URL
function copyURL() {
    navigator.clipboard.writeText(scanUrl).then(() => {
        const btn = document.getElementById('copy-btn');
        btn.textContent = 'Copied!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = 'Copy';
            btn.classList.remove('copied');
        }, 2000);
    });
}

// Older sessions toggle
function toggleOlder() {
    const body = document.getElementById('older-sessions');
    const btn  = document.getElementById('older-toggle');
    const open = body.classList.toggle('open');
    const count = <?php echo count($older ?? []); ?>;
    btn.textContent = open
        ? 'Hide older sessions'
        : 'Show ' + count + ' older session' + (count !== 1 ? 's' : '');
}
</script>
</body>
</html>
