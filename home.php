<?php
require_once 'auth.php';

$auth->requireLogin();

// Get user's resume data
$userId = $_SESSION['user_id'];
$personal = $pdo->prepare("SELECT * FROM personal_info WHERE user_id = ? LIMIT 1");
$personal->execute([$userId]);
$personalData = $personal->fetch(PDO::FETCH_ASSOC);

$hasResume = !empty($personalData);

// Handle logout
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
            font-family: 'Istok Web', sans-serif;
            background: linear-gradient(135deg, #1b2634 0%, #131922 100%);
            min-height: 100vh;
            color: #bbcfe2;
        }

        .navbar {
            background: #1e293b;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logout-btn {
            background: #dc2626;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
            font-size: 14px;
        }

        .logout-btn:hover {
            background: #b91c1c;
        }

        .admin-btn {
            background: #16a34a;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
            font-size: 14px;
        }

        .admin-btn:hover {
            background: #15803d;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 50px;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
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
            font-size: 36px;
            color: white;
            margin-bottom: 15px;
        }

        .welcome-section p {
            font-size: 18px;
            opacity: 0.8;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .card {
            background: #1e293b;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(17, 49, 69, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="1.5" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="white" opacity="0.1"/></svg>');
            opacity: 0.3;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(17, 49, 69, 0.4);
        }

        .card-content {
            position: relative;
            z-index: 1;
        }

        .card-icon {
            font-size: 48px;
            color: #bbcfe2;
            margin-bottom: 20px;
            display: block;
        }

        .card h3 {
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }

        .card p {
            color: #bbcfe2;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 15px 25px;
            background: white;
            color: #131922;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .btn:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            border-color: rgba(255,255,255,0.5);
        }

        .resume-status {
            background: #16a34a;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-block;
            margin-bottom: 20px;
        }

        .resume-status.no-resume {
            background: #dc2626;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .stat-card {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: white;
            display: block;
        }

        .stat-label {
            color: #bbcfe2;
            font-size: 14px;
            margin-top: 5px;
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
                font-size: 28px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .card {
                padding: 30px 20px;
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
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
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
                        <button class="btn" disabled style="opacity: 0.5; cursor: not-allowed;">
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