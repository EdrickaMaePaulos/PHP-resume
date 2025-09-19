<?php
require_once 'auth.php';

$auth->requireAdmin();

// Get all users with resumes
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

// Statistics
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
            max-width: 1400px;
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

        .home-btn {
            background: #16a34a;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
            font-size: 14px;
        }

        .home-btn:hover {
            background: #15803d;
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

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .admin-header {
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

        .admin-header h1 {
            font-size: 36px;
            color: white;
            margin-bottom: 15px;
        }

        .admin-header p {
            font-size: 18px;
            opacity: 0.8;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: #1e293b;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(17, 49, 69, 0.3);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/></svg>');
            opacity: 0.3;
        }

        .stat-card-content {
            position: relative;
            z-index: 1;
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: white;
            display: block;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #bbcfe2;
            font-size: 16px;
        }

        .users-section {
            background: #1e293b;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(17, 49, 69, 0.3);
        }

        .section-title {
            font-size: 24px;
            color: white;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .users-table th {
            background: #131922;
            color: white;
            padding: 20px 15px;
            text-align: left;
            font-weight: 600;
        }

        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }

        .users-table tr:hover {
            background: #f9fafb;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-completed {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .view-btn {
            background: #3b82f6;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: background 0.3s ease;
        }

        .view-btn:hover {
            background: #2563eb;
        }

        .view-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: #131922;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .nav-content {
                flex-direction: column;
                gap: 15px;
            }

            .admin-header h1 {
                font-size: 28px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .users-table {
                font-size: 14px;
            }

            .users-table th,
            .users-table td {
                padding: 10px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <div class="logo">
                <i class="fas fa-shield-alt"></i> Admin Panel
            </div>
            <div class="nav-right">
                <a href="home.php" class="home-btn">
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