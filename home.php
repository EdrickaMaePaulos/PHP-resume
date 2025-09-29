<?php
require_once 'auth.php';

$auth->requireLogin();

$userId = $_SESSION['user_id'];
$personal = $pdo->prepare("SELECT * FROM personal_info WHERE user_id = ? LIMIT 1");
$personal->execute([$userId]);
$personalData = $personal->fetch(PDO::FETCH_ASSOC);

$hasResume = !empty($personalData);

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $auth->logout();
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Resume Builder</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Istok Web', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a0e1a;
            min-height: 100vh;
            color: #ffffff;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, #7877c61a 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, #ff77c61a 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .navbar {
            position: relative;
            z-index: 10;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(30, 41, 59, 0.3);
            padding: 20px 0;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            color: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .logout-btn, .admin-btn {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: translateY(-1px);
        }

        .admin-btn {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        .admin-btn:hover {
            background: rgba(34, 197, 94, 0.2);
            transform: translateY(-1px);
        }

        .container {
            position: relative;
            z-index: 5;
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 60px;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .welcome-section h1 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #fff 0%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .welcome-section p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 400;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(30, 41, 59, 0.3);
            border-radius: 24px;
            padding: 40px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
            animation-fill-mode: both;
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:nth-child(4) { animation-delay: 0.4s; }

        .card:hover {
            transform: translateY(-8px);
            background: rgba(15, 23, 42, 0.8);
            border-color: rgba(96, 165, 250, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .card-content {
            position: relative;
            z-index: 1;
        }

        .card-icon {
            font-size: 48px;
            color: #60a5fa;
            margin-bottom: 20px;
            display: block;
        }

        .card h3 {
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .card p {
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 30px;
            line-height: 1.6;
            font-size: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.1);
        }

        .resume-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .resume-status {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .resume-status.no-resume {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 50px;
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(30, 41, 59, 0.3);
            padding: 30px 20px;
            border-radius: 16px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: rgba(96, 165, 250, 0.3);
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: white;
            display: block;
            margin-bottom: 8px;
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            font-weight: 500;
        }

        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .nav-content {
                flex-direction: column;
                gap: 15px;
            }

            .nav-right {
                flex-direction: column;
                gap: 10px;
            }

            .welcome-section h1 {
                font-size: 32px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .card {
                padding: 30px 20px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                margin-right: 0;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 40px 15px;
            }

            .welcome-section h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <div class="logo">
                <i class="fas fa-file-alt"></i> Resume Builder
            </div>
            <div class="nav-right">
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['firstName']); ?>!</span>
                </div>
                <?php if ($auth->isAdmin()): ?>
                    <a href="admin.php" class="admin-btn">
                        <i class="fas fa-cog"></i> Admin Panel
                    </a>
                <?php endif; ?>
                <a href="?action=logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-section">
            <h1>Your Resume Dashboard</h1>
            <p>Create, manage, and showcase your professional resume</p>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-content">
                    <i class="fas fa-file-alt card-icon"></i>
                    <h3>My Resume</h3>
                    
                    <?php if ($hasResume): ?>
                        <div class="resume-status">Resume Created</div>
                        <p>You have created your resume! You can view it, edit it, or create a new version.</p>
                        <div class="button-group">
                            <a href="resume.php?user_id=<?php echo $userId; ?>" class="btn">
                                <i class="fas fa-eye"></i> View Resume
                            </a>
                            <a href="edit_resume.php" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="resume-status no-resume">No Resume Yet</div>
                        <p>Start building your professional resume now. Add your personal information, skills, education, and experience.</p>
                        <a href="create_resume.php" class="btn">
                            <i class="fas fa-plus"></i> Create Resume
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-content">
                    <i class="fas fa-download card-icon"></i>
                    <h3>Export & Share</h3>
                    <p>Download your resume as PDF or share it with potential employers using a direct link.</p>
                    <?php if ($hasResume): ?>
                        <a href="resume.php?user_id=<?php echo $userId; ?>" class="btn" onclick="setTimeout(() => window.print(), 500)">
                            <i class="fas fa-print"></i> Print/PDF
                        </a>
                    <?php else: ?>
                        <button class="btn" disabled>
                            <i class="fas fa-print"></i> Create Resume First
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-content">
                    <i class="fas fa-chart-line card-icon"></i>
                    <h3>Resume Analytics</h3>
                    <p>Track your resume performance and see insights about your professional profile.</p>
                    <a href="#" class="btn btn-secondary">
                        <i class="fas fa-chart-bar"></i> View Analytics
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-content">
                    <i class="fas fa-users card-icon"></i>
                    <h3>Profile Settings</h3>
                    <p>Update your account information, change password, and manage your profile preferences.</p>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </div>
            </div>
        </div>

        <?php if ($hasResume): ?>
        <div class="quick-stats">
            <div class="stat-card">
                <span class="stat-number">1</span>
                <div class="stat-label">Resume Created</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">
                    <?php
                    $skillCount = $pdo->prepare("SELECT COUNT(*) FROM technical_skills WHERE personal_id = (SELECT id FROM personal_info WHERE user_id = ?)");
                    $skillCount->execute([$userId]);
                    echo $skillCount->fetchColumn();
                    ?>
                </span>
                <div class="stat-label">Technical Skills</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">
                    <?php
                    $projectCount = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE personal_id = (SELECT id FROM personal_info WHERE user_id = ?)");
                    $projectCount->execute([$userId]);
                    echo $projectCount->fetchColumn();
                    ?>
                </span>
                <div class="stat-label">Projects</div>
            </div>
            <div class="stat-card">
                <span class="stat-number">
                    <?php
                    $eduCount = $pdo->prepare("SELECT COUNT(*) FROM education WHERE personal_id = (SELECT id FROM personal_info WHERE user_id = ?)");
                    $eduCount->execute([$userId]);
                    echo $eduCount->fetchColumn();
                    ?>
                </span>
                <div class="stat-label">Education Records</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>