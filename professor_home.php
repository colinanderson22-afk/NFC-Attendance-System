<?php
session_start();
require "db.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    header("Location: login.php");
    exit;
}

$prof_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT Name FROM Users WHERE User_ID = ?");
$stmt->execute([$prof_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT Course_ID, Course_Name, Tag_ID, Course_DateTime
    FROM Course WHERE professor_id = ?
");
$stmt->execute([$prof_id]);
$courses = $stmt->fetchAll();

// Auto-close sessions that have exceeded their own Duration_Minutes
$pdo->prepare("
    UPDATE Class_Session
    SET Active = 0
    WHERE Active = 1
    AND DATE_ADD(Start_Time, INTERVAL Duration_Minutes MINUTE) < NOW()
")->execute();

// Build scan base URL
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host      = $_SERVER['HTTP_HOST'];
$dir       = dirname($_SERVER['SCRIPT_NAME']);
$base_path = ($dir === '/' || $dir === '\\') ? '' : rtrim($dir, '/\\');

$course_data = [];
foreach ($courses as $course) {
    $cid = $course['Course_ID'];

    $stmt = $pdo->prepare("
        SELECT Session_ID, Start_Time, Duration_Minutes
        FROM Class_Session
        WHERE Course_ID = ? AND Active = 1
        LIMIT 1
    ");
    $stmt->execute([$cid]);
    $session = $stmt->fetch();
    $session_active = (bool)$session;

    $roster    = [];
    $time_left = null;
    $duration  = 30;

    if ($session_active) {
        $duration = (int)$session['Duration_Minutes'];
        $stmt = $pdo->prepare("
            SELECT TIMESTAMPDIFF(SECOND, NOW(),
                DATE_ADD(Start_Time, INTERVAL Duration_Minutes MINUTE)) AS secs_left
            FROM Class_Session WHERE Session_ID = ?
        ");
        $stmt->execute([$session['Session_ID']]);
        $time_left = max(0, (int)$stmt->fetchColumn());

        $stmt = $pdo->prepare("
            SELECT u.Name, a.Status
            FROM Enrollment e
            JOIN Users u ON e.User_ID = u.User_ID
            LEFT JOIN Attendance a ON a.User_ID = e.User_ID AND a.session_id = ?
            WHERE e.Course_ID = ?
            ORDER BY u.Name ASC
        ");
        $stmt->execute([$session['Session_ID'], $cid]);
        $roster = $stmt->fetchAll();
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Enrollment WHERE Course_ID = ?");
    $stmt->execute([$cid]);
    $total = (int)$stmt->fetchColumn();

    $present = 0;
    foreach ($roster as $r) {
        if ($r['Status'] === 'present') $present++;
    }

    $scan_url = $protocol . '://' . $host . $base_path . '/scan.php?course_id=' . $cid;

    $course_data[] = [
        'course'         => $course,
        'session_active' => $session_active,
        'session'        => $session,
        'roster'         => $roster,
        'total'          => $total,
        'present'        => $present,
        'scan_url'       => $scan_url,
        'time_left'      => $time_left,
        'duration'       => $duration,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:      #f0ede8;
            --card:    #ffffff;
            --ink:     #1c1a17;
            --muted:   #7a7469;
            --accent:  #2d5be3;
            --green:   #1a8a4a;
            --red:     #c0392b;
            --border:  #ddd9d2;
            --tag-bg:  #eae7e0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
            padding-bottom: 60px;
        }

        .topbar {
            background: var(--ink); color: #fff;
            padding: 18px 36px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .topbar-title { font-family: 'DM Serif Display', serif; font-size: 1.4rem; }
        .topbar-sub   { font-size: 0.82rem; color: #aaa; margin-top: 2px; }
        .btn-new {
            background: var(--accent); color: #fff; border: none;
            border-radius: 6px; padding: 9px 18px; font-size: 0.88rem;
            font-weight: 600; cursor: pointer; text-decoration: none;
        }
        .btn-new:hover { opacity: 0.85; }

        .container { max-width: 860px; margin: 0 auto; padding: 36px 20px 0; }
        .section-label {
            font-size: 0.72rem; font-weight: 600; letter-spacing: 0.12em;
            text-transform: uppercase; color: var(--muted); margin-bottom: 16px;
        }

        .course-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 12px; margin-bottom: 24px; overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            animation: fadeUp 0.4s ease both;
        }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(12px); }
            to   { opacity:1; transform:translateY(0); }
        }

        .card-head {
            padding: 20px 24px 16px; border-bottom: 1px solid var(--border);
            display: flex; align-items: flex-start;
            justify-content: space-between; gap: 12px;
        }
        .course-name {
            font-family: 'DM Serif Display', serif; font-size: 1.25rem;
            color: var(--ink); text-decoration: none;
        }
        .course-name:hover { color: var(--accent); }
        .tag-chip {
            display: inline-block; background: var(--tag-bg); color: var(--muted);
            font-size: 0.72rem; font-weight: 600; letter-spacing: 0.06em;
            padding: 3px 9px; border-radius: 20px; margin-top: 6px;
        }
        .status-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 12px; border-radius: 20px;
            font-size: 0.78rem; font-weight: 600; white-space: nowrap;
        }
        .pill-on  { background: #d4f5e2; color: var(--green); }
        .pill-off { background: #f0ede8; color: var(--muted); }
        .pulse {
            width: 8px; height: 8px; border-radius: 50%; background: var(--green);
            animation: pulse 1.4s infinite;
        }
        @keyframes pulse {
            0%,100% { opacity:1; transform:scale(1); }
            50%      { opacity:0.4; transform:scale(1.3); }
        }

        .card-body { padding: 18px 24px; }

        .controls { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; align-items: center; }
        .btn {
            padding: 8px 16px; border-radius: 6px; font-size: 0.83rem;
            font-weight: 600; cursor: pointer; border: none;
            transition: opacity 0.15s, transform 0.1s;
            font-family: 'DM Sans', sans-serif;
        }
        .btn:active { transform: scale(0.97); }
        .btn-start { background: var(--green); color: #fff; }
        .btn-end   { background: #fde8e6; color: var(--red); }
        .btn-url   { background: var(--accent); color: #fff; }
        .btn:hover { opacity: 0.85; }

        /* Start + duration as one joined control */
        .start-group { display: flex; align-items: stretch; }
        .start-group .btn-start {
            border-radius: 6px 0 0 6px;
            border-right: 1px solid rgba(255,255,255,0.25);
        }
        .duration-select {
            padding: 0 10px;
            border: none;
            border-radius: 0 6px 6px 0;
            background: #138040;
            color: #fff;
            font-size: 0.83rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            appearance: none;
            -webkit-appearance: none;
        }

        .timer-wrap { margin-bottom: 14px; }
        .timer-label {
            font-size: 0.75rem; color: var(--muted); margin-bottom: 4px;
            display: flex; justify-content: space-between;
        }
        .timer-bar-bg { height: 6px; background: #e8e4dd; border-radius: 3px; overflow: hidden; }
        .timer-bar-fill {
            height: 100%; border-radius: 3px; background: var(--green);
            transition: width 1s linear, background 0.5s;
        }

        .attend-summary { font-size: 0.85rem; color: var(--muted); margin-bottom: 14px; }
        .attend-summary strong { color: var(--ink); font-size: 1rem; }

        .roster-wrap { border: 1px solid var(--border); border-radius: 8px; overflow: hidden; margin-bottom: 4px; }
        .roster-table { width: 100%; border-collapse: collapse; font-size: 0.83rem; }
        .roster-table th {
            background: var(--tag-bg); padding: 8px 12px; text-align: left;
            font-size: 0.72rem; font-weight: 600; letter-spacing: 0.07em;
            text-transform: uppercase; color: var(--muted);
        }
        .roster-table td { padding: 9px 12px; border-top: 1px solid var(--border); }
        .roster-table tr:hover td { background: #faf9f6; }
        .badge { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
        .badge-present { background: #d4f5e2; color: var(--green); }
        .badge-absent  { background: #fde8e6; color: var(--red); }
        .refresh-note { font-size: 0.72rem; color: var(--muted); text-align: right; margin-top: 6px; }

        /* Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.45); z-index: 100;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: #fff; border-radius: 14px; padding: 32px 28px;
            width: 440px; max-width: 95vw;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: popIn 0.2s ease;
        }
        @keyframes popIn {
            from { opacity:0; transform:scale(0.92); }
            to   { opacity:1; transform:scale(1); }
        }
        .modal-header {
            display: flex; justify-content: space-between;
            align-items: flex-start; margin-bottom: 18px;
        }
        .modal-header h3 { font-family: 'DM Serif Display', serif; font-size: 1.2rem; }
        .modal-course-label { font-size: 0.8rem; color: var(--muted); margin-top: 3px; }
        .modal-close {
            background: none; border: none; font-size: 1.4rem;
            cursor: pointer; color: var(--muted); line-height: 1; padding: 0;
        }
        .url-box {
            background: #f4f2ee; border: 1px solid var(--border); border-radius: 7px;
            padding: 10px 12px; font-size: 0.78rem; word-break: break-all;
            color: var(--ink); margin-bottom: 10px; font-family: monospace;
            user-select: all;
        }
        .copy-btn {
            width: 100%; padding: 9px; background: var(--accent); color: #fff;
            border: none; border-radius: 6px; font-weight: 600; font-size: 0.85rem;
            cursor: pointer; margin-bottom: 20px; transition: background 0.2s;
            font-family: 'DM Sans', sans-serif;
        }
        .copy-btn.copied { background: var(--green); }
        .qr-center { text-align: center; }
        .qr-dl { display: inline-block; margin-top: 10px; font-size: 0.78rem; color: var(--accent); }
    </style>
</head>
<body>

<div class="topbar">
    <div>
        <div class="topbar-title">ABC Attendance</div>
        <div class="topbar-sub">Professor Dashboard — <?php echo htmlspecialchars($user['Name']); ?></div>
    </div>
    <a href="setup_course.php" class="btn-new">+ New Course</a>
</div>

<div class="container">
    <div class="section-label">Your Courses</div>

    <?php if (empty($course_data)): ?>
        <p style="color:var(--muted);text-align:center;margin-top:40px;">
            No courses yet. <a href="setup_course.php">Create one →</a>
        </p>
    <?php endif; ?>

    <?php foreach ($course_data as $i => $cd):
        $c      = $cd['course'];
        $cid    = $c['Course_ID'];
        $active = $cd['session_active'];
        $dur    = $cd['duration'];
    ?>
    <div class="course-card" style="animation-delay:<?php echo $i * 0.07; ?>s">

        <div class="card-head">
            <div>
                <a class="course-name" href="course_detail.php?course_id=<?php echo $cid; ?>">
                    <?php echo htmlspecialchars($c['Course_Name']); ?>
                </a>
                <div><span class="tag-chip">TAG · <?php echo htmlspecialchars($c['Tag_ID']); ?></span></div>
            </div>
            <?php if ($active): ?>
                <span class="status-pill pill-on"><span class="pulse"></span> Live</span>
            <?php else: ?>
                <span class="status-pill pill-off">● Inactive</span>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="controls">
                <form method="POST" action="start_session.php" style="margin:0">
                    <input type="hidden" name="course_id" value="<?php echo $cid; ?>">
                    <div class="start-group">
                        <button class="btn btn-start" type="submit">Start</button>
                        <select class="duration-select" name="duration" title="Session duration">
                            <option value="10">10 min</option>
                            <option value="15">15 min</option>
                            <option value="20">20 min</option>
                            <option value="30" selected>30 min</option>
                            <option value="45">45 min</option>
                            <option value="60">60 min</option>
                            <option value="90">90 min</option>
                        </select>
                    </div>
                </form>

                <form method="POST" action="end_session.php" style="margin:0">
                    <input type="hidden" name="course_id" value="<?php echo $cid; ?>">
                    <button class="btn btn-end" type="submit">End</button>
                </form>

                <a href="course_detail.php?course_id=<?php echo $cid; ?>#scan-url-text" class="btn btn-url"
                   style="text-decoration:none; display:inline-block;">
                    Show URL
                </a>
            </div>

            <?php if ($active): ?>
            <div class="timer-wrap">
                <div class="timer-label">
                    <span>Auto-closes in</span>
                    <span id="timer-text-<?php echo $cid; ?>">--:--</span>
                </div>
                <div class="timer-bar-bg">
                    <div class="timer-bar-fill" id="timer-bar-<?php echo $cid; ?>"
                         style="width:<?php echo round(($cd['time_left'] / ($dur * 60)) * 100); ?>%">
                    </div>
                </div>
            </div>

            <div class="attend-summary">
                <strong><?php echo $cd['present']; ?></strong> / <?php echo $cd['total']; ?> students present
            </div>

            <?php if (!empty($cd['roster'])): ?>
            <div class="roster-wrap">
                <table class="roster-table">
                    <thead><tr><th>Student</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($cd['roster'] as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['Name']); ?></td>
                            <td>
                                <?php $s = $r['Status'] ?? 'absent'; ?>
                                <span class="badge <?php echo $s === 'present' ? 'badge-present' : 'badge-absent'; ?>">
                                    <?php echo ucfirst($s); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="refresh-note">Refreshing in <span id="refresh-count-<?php echo $cid; ?>">30</span>s</div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Shared URL modal -->
<div class="modal-overlay" id="url-modal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Student Scan URL</h3>
                <div class="modal-course-label" id="modal-course-name"></div>
            </div>
            <button class="modal-close" onclick="closeURL()">X</button>
        </div>
        <div class="url-box" id="modal-url-text"></div>
        <button class="copy-btn" id="modal-copy-btn" onclick="doCopy()">Copy URL</button>
        <div class="qr-center">
            <div id="modal-qr"></div>
            <a class="qr-dl" id="modal-qr-dl" href="#" download="qr_course.png">Download QR Code</a>
        </div>
    </div>
</div>

<!-- Load QRCode lib BEFORE our script -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
// ── Timers ──
const timers = <?php
    $t = [];
    foreach ($course_data as $cd) {
        if ($cd['session_active']) {
            $t[$cd['course']['Course_ID']] = [
                'secs'     => $cd['time_left'],
                'duration' => $cd['duration'] * 60,
            ];
        }
    }
    echo json_encode($t);
?>;

Object.entries(timers).forEach(([cid, info]) => {
    let s = info.secs;
    const total  = info.duration;
    const textEl = document.getElementById('timer-text-' + cid);
    const barEl  = document.getElementById('timer-bar-' + cid);
    function tick() {
        if (s <= 0) {
            textEl.textContent = 'Closing…';
            barEl.style.width = '0%';
            barEl.style.background = '#c0392b';
            setTimeout(() => location.reload(), 2000);
            return;
        }
        const m = Math.floor(s / 60), sec = s % 60;
        textEl.textContent = m + ':' + String(sec).padStart(2, '0');
        const pct = (s / total) * 100;
        barEl.style.width = pct + '%';
        barEl.style.background = pct < 20 ? '#e74c3c' : pct < 50 ? '#f39c12' : '#1a8a4a';
        s--;
    }
    tick();
    setInterval(tick, 1000);
});

// Auto-refresh roster every 30s
Object.keys(timers).forEach(cid => {
    let countdown = 30;
    const el = document.getElementById('refresh-count-' + cid);
    setInterval(() => { countdown--; if (el) el.textContent = countdown; if (countdown <= 0) location.reload(); }, 1000);
});

// ── URL Modal ──
let _currentURL = '';

function openURL(cid, url, courseName) {
    _currentURL = url;
    document.getElementById('modal-course-name').textContent = courseName;
    document.getElementById('modal-url-text').textContent    = url;
    document.getElementById('modal-copy-btn').textContent    = 'Copy URL';
    document.getElementById('modal-copy-btn').classList.remove('copied');
    document.getElementById('modal-qr-dl').download = 'qr_course_' + cid + '.png';

    // Clear old QR and generate fresh
    const qrEl = document.getElementById('modal-qr');
    qrEl.innerHTML = '';
    new QRCode(qrEl, {
        text: url,
        width: 180,
        height: 180,
        colorDark: '#1c1a17',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });

    setTimeout(() => {
        const canvas = qrEl.querySelector('canvas');
        if (canvas) document.getElementById('modal-qr-dl').href = canvas.toDataURL('image/png');
    }, 400);

    document.getElementById('url-modal').classList.add('open');
}

function closeURL() {
    document.getElementById('url-modal').classList.remove('open');
}

function doCopy() {
    navigator.clipboard.writeText(_currentURL).then(() => {
        const btn = document.getElementById('modal-copy-btn');
        btn.textContent = '✓ Copied!';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = 'Copy URL'; btn.classList.remove('copied'); }, 2000);
    });
}

document.getElementById('url-modal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeURL();
});
</script>
</body>
</html>
