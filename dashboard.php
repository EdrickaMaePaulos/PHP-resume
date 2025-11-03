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
    header('Location: landing_page.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="styles\dashboard.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">

        <div class="nav-content">
            <div class="logo">
                
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
            <h1>Resume Dashboard</h1>
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
                            <a href="edit_resume.php?user_id=<?php echo $userId; ?>" class="btn btn-secondary">
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
<!--
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
                    -->
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
                    <h3>Public Resume</h3>
                    <p>View other user's public resumes</p>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-cog"></i> View
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