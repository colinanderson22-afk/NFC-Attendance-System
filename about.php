<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="abc.css">
    <title>About — ABC Attendance</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500&display=swap');

        body {
            padding: 0;
            background: #f4f6fb;
        }

        /* ── Top nav bar ── */
        .about-nav {
            background: #fff;
            border-bottom: 1px solid #dde1ea;
            padding: 14px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .about-nav .brand {
            font-family: 'Lora', serif;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a1d23;
            text-decoration: none;
        }

        .about-nav .nav-links {
            display: flex;
            gap: 12px;
        }

        .about-nav .nav-links a {
            font-family: 'Inter', sans-serif;
            font-size: 0.88rem;
            color: #6b7280;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: background 0.15s, color 0.15s;
        }

        .about-nav .nav-links a:hover {
            background: #f4f6fb;
            color: #1a1d23;
        }

        .about-nav .nav-links a.btn-nav {
            background: #4a69bd;
            color: #fff;
        }

        .about-nav .nav-links a.btn-nav:hover {
            background: #3a559d;
            color: #fff;
        }

        /* ── Hero band ── */
        .hero-band {
            background: linear-gradient(135deg, #1a1d23 0%, #2c3e6b 100%);
            padding: 72px 32px 56px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-band::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 20% 50%, rgba(74,105,189,0.25) 0%, transparent 60%),
                              radial-gradient(circle at 80% 20%, rgba(74,105,189,0.15) 0%, transparent 50%);
        }

        .hero-band h1 {
            font-family: 'Lora', serif;
            font-size: 2.6rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 10px;
            position: relative;
        }

        .hero-band .tagline {
            font-family: 'Inter', sans-serif;
            font-size: 1.05rem;
            color: rgba(255,255,255,0.65);
            font-weight: 300;
            position: relative;
        }

        /* ── Page content ── */
        .about-content {
            max-width: 820px;
            margin: 0 auto;
            padding: 56px 24px 80px;
        }

        /* ── Creator card ── */
        .creator-card {
            background: #fff;
            border: 1px solid #dde1ea;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
            padding: 40px 40px 36px;
            display: flex;
            gap: 36px;
            align-items: flex-start;
            margin-bottom: 40px;
        }

        .creator-card img {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            object-position: center top;
            border: 3px solid #dde1ea;
            flex-shrink: 0;
        }

        .creator-info h2 {
            font-family: 'Lora', serif;
            font-size: 1.6rem;
            font-weight: 600;
            color: #1a1d23;
            text-align: left;
            margin-bottom: 4px;
        }

        .creator-info .title {
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            color: #4a69bd;
            font-weight: 500;
            margin-bottom: 14px;
        }

        .creator-info p {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: #4b5563;
            line-height: 1.7;
            max-width: none;
        }

        /* ── Section cards ── */
        .info-section {
            background: #fff;
            border: 1px solid #dde1ea;
            border-radius: 12px;
            padding: 32px 36px;
            margin-bottom: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .info-section h3 {
            font-family: 'Lora', serif;
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a1d23;
            text-align: left;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-section h3 .icon {
            font-size: 1.3rem;
        }

        .info-section p,
        .info-section li {
            font-family: 'Inter', sans-serif;
            font-size: 0.95rem;
            color: #4b5563;
            line-height: 1.75;
            max-width: none;
        }

        .info-section ul {
            margin: 10px 0 0 18px;
        }

        .info-section li {
            margin-bottom: 6px;
        }

        /* ── Tech stack pills ── */
        .tech-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .pill {
            background: #f0f4ff;
            color: #4a69bd;
            border: 1px solid #c7d4f5;
            border-radius: 100px;
            padding: 4px 14px;
            font-family: 'Inter', sans-serif;
            font-size: 0.82rem;
            font-weight: 500;
        }

        /* ── Back link ── */
        .back-bar {
            text-align: center;
            margin-top: 40px;
        }

        .back-bar a {
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            color: #4a69bd;
            text-decoration: none;
        }

        .back-bar a:hover { text-decoration: underline; }

        /* ── Responsive ── */
        @media (max-width: 600px) {
            .creator-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 28px 20px;
            }
            .creator-info h2,
            .creator-info .title { text-align: center; }
            .info-section { padding: 24px 20px; }
            .hero-band h1 { font-size: 1.9rem; }
        }
    </style>
</head>
<body>

<!-- Nav -->
<nav class="about-nav">
    <a href="index.php" class="brand">ABC Attendance</a>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="login.php" class="btn-nav">Log In</a>
    </div>
</nav>

<!-- Hero -->
<div class="hero-band">
    <h1>About ABC Attendance</h1>
    <p class="tagline">Attendance that is as easy as ABC</p>
</div>

<!-- Content -->
<div class="about-content">

    <!-- Creator -->
    <div class="creator-card">
        <img src="headshot-colin.PNG" alt="Colin Anderson">
        <div class="creator-info">
            <h2>Colin Anderson</h2>
            <div class="title">Senior · Information Science · Christopher Newport University</div>
            <p>
                Colin is a senior at Christopher Newport University pursuing a degree in Information Science.
                ABC Attendance was built as his capstone project; A hands-on demonstration of how modern
                web technologies and hardware (NFC tags) can come together to solve a real, everyday
                problem on university campuses.
            </p>
        </div>
    </div>

    <!-- Project goal -->
    <div class="info-section">
        <h3><span class="icon">🎯</span> Project Goal</h3>
        <p>
            Traditional attendance methods like paper sign-in sheets, manual roll calls, clicker devices are
            slow, prone to error, and frustrating for both professors and students. ABC Attendance was designed
            to make the entire process <strong>frictionless</strong>.
        </p>
        <p style="margin-top: 10px;">
            A professor sets up a course once, writes a URL to a cheap NFC tag (or prints a QR code),
            and presents to students during class time. Students tap their phone on the NFC tag and attendance is recorded instantly, nothing else required.
        </p>
    </div>

    <!-- How it works -->
    <div class="info-section">
        <h3><span class="icon">⚙️</span> How It Works</h3>
        <ul>
            <li><strong>Professors</strong> create courses and assign a unique NFC tag or QR code to each one.</li>
            <li>At the start of class, the professor opens a session from their dashboard.</li>
            <li><strong>Students</strong> tap the NFC tag (or scan the QR code) with their smartphone's web browser.</li>
            <li>Attendance is marked <em>present</em> in real time. Students who don't scan are marked <em>absent</em> automatically when the session ends.</li>
            <li>Professors can view student attendance and logged session history at any time.</li>
        </ul>
    </div>

    <!-- Tech stack -->
    <div class="info-section">
        <h3><span class="icon">🛠️</span> Built With</h3>
        <p>ABC Attendance runs on a straightforward server-side stack via AWS EC2.</p>
        <div class="tech-pills">
            <span class="pill">PHP</span>
            <span class="pill">MySQL</span>
            <span class="pill">PDO</span>
            <span class="pill">HTML / CSS</span>
            <span class="pill">NFC Tags</span>
            <span class="pill">QR Codes</span>
            <span class="pill">AWS EC2 Linux</span>
        </div>
    </div>

    <!-- Placeholder sections for Colin to fill in -->
    <div class="info-section">
        <h3><span class="icon">📚</span> Academic Context </h3>
        <p>
            This system was created to support professors who find that attendance is key for their course, this is likely most relevant in lower level college courses that are lecture-based. This project acts as proof of concept for what an attendance system could look like if it were adapted by CNU using their RFID ID card system. 
        </p>
    </div>

    <div class="info-section">
        <h3><span class="icon">🔮</span> The Project's Future </h3>
        <p>
            The trajectory of this project may very well continue after graduation, some potential updates may include an iOS/Android mobile app, LMS integrations (Canvas, Blackboard),
            analytics exports for professors, or department-wide deployment.
        </p>
    </div>

    <div class="back-bar">
        <a href="index.php">← Back to Home</a>
    </div>

</div>

</body>
</html>
