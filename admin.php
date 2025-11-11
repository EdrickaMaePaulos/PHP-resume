<?php
require_once 'auth.php';

$auth->requireAdmin();

$stmt = $pdo->query("
    SELECT 
        u.userid, u.username, u.email, u.firstName, u.lastName, u.created_at,
        p.id as personal_id, p.full_name, p.email as resume_email,
        (SELECT COUNT(*) FROM technical_skills ts WHERE ts.personal_id = p.id) as skill_count,
        (SELECT COUNT(*) FROM projects pr WHERE pr.personal_id = p.id) as project_count,
        (SELECT COUNT(*) FROM education e WHERE e.personal_id = p.id) as education_count
    FROM users u
    LEFT JOIN personal_info p ON u.userid = p.user_id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE isAdmin = FALSE")->fetchColumn();
$totalResumes = $pdo->query("SELECT COUNT(*) FROM personal_info")->fetchColumn();
$totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$totalSkills = $pdo->query("SELECT COUNT(*) FROM technical_skills")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Resume Builder</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="styles/admin.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <div class="logo">
                <i class="fas fa-shield-alt"></i> Admin Panel
            </div>
            <div class="nav-right">
                <a href="dashboard.php" class="home-btn">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="?action=logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="admin-header">
            <h1>System Administration</h1>
            <p>Manage users and monitor resume creation activity</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-content">
                    <i class="fas fa-users stat-icon" style="color: #3b82f6;"></i>
                    <span class="stat-number"><?php echo $totalUsers; ?></span>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <i class="fas fa-file-alt stat-icon" style="color: #10b981;"></i>
                    <span class="stat-number"><?php echo $totalResumes; ?></span>
                    <div class="stat-label">Resumes Created</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <i class="fas fa-project-diagram stat-icon" style="color: #f59e0b;"></i>
                    <span class="stat-number"><?php echo $totalProjects; ?></span>
                    <div class="stat-label">Total Projects</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-content">
                    <i class="fas fa-code stat-icon" style="color: #8b5cf6;"></i>
                    <span class="stat-number"><?php echo $totalSkills; ?></span>
                    <div class="stat-label">Skills Listed</div>
                </div>
            </div>
        </div>

        <div class="users-section">
            <h2 class="section-title">
                <i class="fas fa-users"></i> User Management
            </h2>

            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Resume Status</th>
                        <th>Skills</th>
                        <th>Projects</th>
                        <th>Education</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($user['firstname'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></div>
                                        <div style="font-size: 12px; color: #6b7280;">@<?php echo htmlspecialchars($user['username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['personal_id']): ?>
                                    <span class="status-badge status-completed">Completed</span>
                                <?php else: ?>
                                    <span class="status-badge status-pending">No Resume</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user['skill_count'] ?: '0'; ?></td>
                            <td><?php echo $user['project_count'] ?: '0'; ?></td>
                            <td><?php echo $user['education_count'] ?: '0'; ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['personal_id']): ?>
                                    <a href="resume.php?user_id=<?php echo $user['userid']; ?>" class="view-btn" target="_blank">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                <?php else: ?>
                                    <button class="view-btn" disabled>
                                        <i class="fas fa-times"></i> No Resume
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>