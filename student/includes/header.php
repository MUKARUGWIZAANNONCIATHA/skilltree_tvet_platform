<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>SkillTree TVET - Student Portal</title>
    <!-- Google Fonts + Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Core styles (inline for reliability, but you can link external) -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f4f9fc;
            font-family: 'Inter', sans-serif;
            color: #1e2f3e;
            line-height: 1.5;
        }

        /* ========== STUDENT NAVBAR ========== */
        .student-navbar {
            background: linear-gradient(135deg, #0b3b4f 0%, #1a5a78 100%);
            padding: 0.9rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo i {
            font-size: 2rem;
            color: #ffda7c;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        .logo span {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff, #ffda7c);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.8rem;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: #f0f6fe;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .nav-links a:hover {
            color: #ffda7c;
            transform: translateY(-2px);
        }
        .user-menu {
            position: relative;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,240,0.15);
            padding: 0.4rem 1rem;
            border-radius: 40px;
            transition: 0.2s;
        }
        .user-menu:hover {
            background: rgba(255,255,240,0.3);
        }
        .user-menu .dropdown {
            display: none;
            position: absolute;
            top: 42px;
            right: 0;
            background: white;
            color: #1e2f3e;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 0.5rem 0;
            min-width: 160px;
            z-index: 200;
        }
        .user-menu:hover .dropdown {
            display: block;
        }
        .dropdown a {
            display: block;
            padding: 0.5rem 1.2rem;
            color: #1e2f3e;
            text-decoration: none;
            font-size: 0.85rem;
            transition: 0.1s;
        }
        .dropdown a:hover {
            background: #f0f6fa;
            color: #1a5a78;
        }

        /* ========== MAIN CONTAINER ========== */
        .student-main {
            max-width: 1300px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 800px) {
            .student-navbar {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            .nav-links {
                justify-content: center;
                gap: 1rem;
            }
            .student-main {
                padding: 0 1rem;
            }
        }
    </style>
    <!-- Additional page-specific CSS can be added in each page -->
</head>
<body>
<div class="app-wrapper">
    <nav class="student-navbar">
        <div class="logo">
            <i class="fas fa-seedling"></i>
            <span>SkillTree TVET</span>
        </div>
        <div class="nav-links">
            <a href="/student/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="/student/library.php"><i class="fas fa-book-open"></i> Library</a>
            <a href="/student/ai-practice.php"><i class="fas fa-robot"></i> AI Practice</a>
            <a href="/student/internships.php"><i class="fas fa-briefcase"></i> Internships</a>
            <a href="/student/community.php"><i class="fas fa-comments"></i> Community</a>
            <div class="user-menu">
                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Student') ?>
                <div class="dropdown">
                    <a href="/student/profile.php"><i class="fas fa-user-edit"></i> My Profile</a>
                    <a href="/student/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <main class="student-main">