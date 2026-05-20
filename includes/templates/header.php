<?php
/**
 * Site Header Template - Role Based Navigation (UPDATED)
 * Path: /includes/templates/header.php
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillTree TVET | <?php echo isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) . ' Dashboard' : 'Learning Platform'; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Your existing styles – unchanged */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; line-height: 1.5; }
        
        /* Navbar */
        .navbar { background: #1a1a2e; padding: 0; position: sticky; top: 0; z-index: 1000; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }
        .nav-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 0 24px; min-height: 70px; }
        .nav-logo { display: flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-icon { font-size: 28px; }
        .logo-text { font-size: 22px; font-weight: 800; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .logo-text span { color: #ff8c42; background: none; -webkit-background-clip: unset; }
        
        /* Navigation Menu */
        .nav-menu { display: flex; list-style: none; gap: 8px; align-items: center; margin: 0; padding: 0; }
        .nav-menu > li { position: relative; }
        .nav-menu > li > a { display: flex; align-items: center; gap: 8px; padding: 8px 16px; color: #e0e0e0; text-decoration: none; font-weight: 500; transition: all 0.3s; border-radius: 8px; }
        .nav-menu > li > a:hover { background: rgba(255,255,255,0.1); color: white; }
        
        /* Active menu item */
        .nav-menu > li.active > a { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        
        /* Dropdown */
        .dropdown-menu { display: none; position: absolute; top: 100%; right: 0; background: white; min-width: 220px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); list-style: none; padding: 8px 0; z-index: 100; }
        .dropdown:hover .dropdown-menu { display: block; }
        .dropdown-menu li a { display: flex; align-items: center; gap: 10px; padding: 10px 20px; color: #333; text-decoration: none; font-size: 14px; transition: background 0.3s; }
        .dropdown-menu li a:hover { background: #f5f5f5; color: #667eea; }
        .dropdown-divider { height: 1px; background: #eee; margin: 8px 0; }
        
        /* User Avatar */
        .user-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px; }
        
        /* Mobile menu */
        .nav-toggle { display: none; flex-direction: column; cursor: pointer; }
        .nav-toggle span { width: 25px; height: 3px; background: white; margin: 3px 0; transition: 0.3s; }
        
        @media (max-width: 900px) {
            .nav-menu { display: none; position: absolute; top: 70px; left: 0; right: 0; background: #1a1a2e; flex-direction: column; padding: 20px; gap: 15px; border-top: 1px solid rgba(255,255,255,0.1); }
            .nav-menu.active { display: flex; }
            .nav-toggle { display: flex; }
            .dropdown-menu { position: static; background: rgba(255,255,255,0.1); box-shadow: none; margin-top: 5px; }
            .dropdown-menu li a { color: #e0e0e0; }
        }
        
        .main-content { min-height: calc(100vh - 200px); }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="#" class="nav-logo">
                <span class="logo-icon">🌳</span>
                <span class="logo-text">SkillTree<span>TVET</span></span>
            </a>
            
            <ul class="nav-menu">
                <?php if(isset($_SESSION['user_id'])): 
                    $role = $_SESSION['user_role'];
                    $currentPage = basename($_SERVER['PHP_SELF']);
                ?>
                    
                    <?php if($role === 'admin'): ?>
                        <!-- ADMIN MENU (unchanged) -->
                        <li class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                            <a href="/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        </li>
                        <li class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
                            <a href="/admin/users.php"><i class="fas fa-users"></i> Users</a>
                        </li>
                        <li class="<?php echo $currentPage === 'modules.php' ? 'active' : ''; ?>">
                            <a href="/admin/modules.php"><i class="fas fa-book"></i> Modules</a>
                        </li>
                        <li class="<?php echo ($currentPage === 'sectors.php' || $currentPage === 'trades.php' || $currentPage === 'levels.php') ? 'active' : ''; ?>">
                            <a href="#" class="dropdown-toggle"><i class="fas fa-chart-pie"></i> System <i class="fas fa-chevron-down" style="font-size: 12px; margin-left: 5px;"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="/admin/sectors.php"><i class="fas fa-chart-pie"></i> Sectors</a></li>
                                <li><a href="/admin/trades.php"><i class="fas fa-briefcase"></i> Trades</a></li>
                                <li><a href="/admin/levels.php"><i class="fas fa-layer-group"></i> Levels</a></li>
                                <li class="dropdown-divider"></li>
                                <li><a href="/admin/system-logs.php"><i class="fas fa-history"></i> System Logs</a></li>
                                <li><a href="/admin/anti-cheat-logs.php"><i class="fas fa-shield-alt"></i> Anti-Cheat Logs</a></li>
                            </ul>
                        </li>
                        <li class="<?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
                            <a href="/admin/settings.php"><i class="fas fa-cog"></i> Settings</a>
                        </li>
                        
                    <?php elseif($role === 'teacher'): ?>
                        <!-- TEACHER MENU -->
                        <li><a href="/teacher/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="/teacher/upload-curriculum.php"><i class="fas fa-upload"></i> Upload Curriculum</a></li>
                        <li><a href="/teacher/curriculum-editor.php"><i class="fas fa-edit"></i> Curriculum Editor</a></li>
                        <li><a href="/teacher/quiz-builder.php"><i class="fas fa-pencil-alt"></i> Quiz Builder</a></li>
                        <li><a href="/teacher/exam-builder.php"><i class="fas fa-file-alt"></i> Exam Builder</a></li>
                        <li><a href="/teacher/student-progress.php"><i class="fas fa-chart-line"></i> Student Progress</a></li>
                        
                    <?php elseif($role === 'company'): ?>
                        <!-- COMPANY MENU -->
                        <li><a href="/company/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="/company/internships.php"><i class="fas fa-briefcase"></i> Internships</a></li>
                        <li><a href="/company/applications.php"><i class="fas fa-file-alt"></i> Applications</a></li>
                        
                    <?php else: ?>
                        <!-- STUDENT MENU -->
                        <li><a href="/student/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a href="/student/my-learning.php"><i class="fas fa-book-open"></i> My Learning</a></li>
                        <li><a href="/student/skill-tree.php"><i class="fas fa-tree"></i> Skill Tree</a></li>
                        <li><a href="/student/ai-chat.php"><i class="fas fa-comment-dots"></i> AI Chat</a></li>
                        <li><a href="/student/ai-practice.php"><i class="fas fa-robot"></i> AI Practice</a></li>
                        <li><a href="/student/companies.php"><i class="fas fa-building"></i> Companies</a></li>
                    <?php endif; ?>
                    
                    <!-- User Dropdown (Common for all roles) -->
                
                <li class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <div class="user-avatar" style="display: inline-flex; vertical-align: middle;">
                                <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <span style="margin-left: 8px;"><?php echo htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'User')[0]); ?></span>
                            <i class="fas fa-chevron-down" style="margin-left: 8px; font-size: 12px;"></i>
                        </a>~
                        <ul class="dropdown-menu">
                            <li><a href="/<?php echo $role; ?>/profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                            <li><a href="/<?php echo $role; ?>/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                            <li class="dropdown-divider"></li>
                            <li><a href="/includes/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                    
                    
                <?php else: ?>
                    <!-- GUEST MENU – updated with About, Features, Contact, etc. -->
                    <li><a href="/">Home</a></li>
                    <li><a href="/about.php">About</a></li>
                    <li><a href="/features.php">Features</a></li>
                    <li><a href="/contact.php">Contact</a></li>
                    <li><a href="/company/register.php"><i class="fas fa-building"></i> Company</a></li>
                    <li><a href="/includes/auth/login.php">Login</a></li>
                    <li><a href="/includes/auth/register.php" style="background: linear-gradient(135deg, #667eea, #764ba2); padding: 8px 20px; border-radius: 30px;">Register</a></li>
                <?php endif; ?>
            </ul>
            <div class="nav-toggle">
                <span></span><span></span><span></span>
            </div>
        </div>
    </nav>
    
    <main class="main-content">