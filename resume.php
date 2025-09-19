<?php
require_once 'db.php';
require_once 'auth.php';

// Check if viewing specific user's resume (for admin) or current user's resume
$viewUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if ($viewUserId && $auth->isAdmin()) {
    // Admin viewing any user's resume
    $userId = $viewUserId;
} else {
    // Regular user - must be logged in and can only view their own resume
    $auth->requireLogin();
    $userId = $_SESSION['user_id'];
}

// Get user's personal info
$personal = $pdo->prepare("SELECT * FROM personal_info WHERE user_id = ? LIMIT 1");
$personal->execute([$userId]);
$personal = $personal->fetch(PDO::FETCH_ASSOC);

if (!$personal) {
    // No resume found
    if ($auth->isAdmin() && $viewUserId) {
        echo "<h1>This user hasn't created a resume yet.</h1>";
        echo "<a href='admin.php'>Back to Admin Panel</a>";
    } else {
        header('Location: home.php');
    }
    exit();
}

$personalId = $personal['id'];

$personalSkills = $pdo->prepare("SELECT skill_name, percentage FROM professional_skills WHERE personal_id = ?");
$personalSkills->execute([$personalId]);
$personalSkills = $personalSkills->fetchAll(PDO::FETCH_ASSOC);

$education = $pdo->prepare("SELECT degree, institution, date_range, gwa FROM education WHERE personal_id = ? ORDER BY id DESC");
$education->execute([$personalId]);
$education = $education->fetchAll(PDO::FETCH_ASSOC);

$technicalSkills = $pdo->prepare("SELECT skill_name FROM technical_skills WHERE personal_id = ?");
$technicalSkills->execute([$personalId]);
$technicalSkills = $technicalSkills->fetchAll(PDO::FETCH_ASSOC);

$projects = $pdo->prepare("SELECT project_name, description, date FROM projects WHERE personal_id = ? ORDER BY id DESC");
$projects->execute([$personalId]);
$projects = $projects->fetchAll(PDO::FETCH_ASSOC);

function generateSkillBar($skill) {
    return "
        <div class='skill-item'>
            <span>{$skill['skill_name']}</span>
            <div class='skill-bar'>
                <div class='skill-progress' style='width: {$skill['percentage']}%'></div>
            </div>
        </div>
    ";
}

function generateContactItem($icon, $text) {
    return "
        <div class='contact-item'>
            <i class='$icon'></i>
            <span>$text</span>
        </div>
    ";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($personal['full_name']); ?> - Resume</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-rounded/css/uicons-solid-rounded.css'>
    <link rel="stylesheet" href="stylesheet.css">
    <style>
        .back-btn {
            position: fixed;
            top: 30px;
            left: 30px;
            background: #131922;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 4px 15px #113145;
            transition: transform 0.2s ease;
            z-index: 1000;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
        }
        
        .back-btn:hover {
            transform: scale(1.1);
        }
        
        @media print {
            .back-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php if ($auth->isAdmin() && $viewUserId): ?>
        <a href="admin.php" class="back-btn" title="Back to Admin Panel">
            <i class="fas fa-arrow-left"></i>
        </a>
    <?php else: ?>
        <a href="home.php" class="back-btn" title="Back to Dashboard">
            <i class="fas fa-home"></i>
        </a>
    <?php endif; ?>
    
    <div class="resume-container">
        <div class="left-section">
            <div class="profile-section">
                <img src="images/profile.jpg" alt="Profile Picture" class="profile-image">
                <div class="name"><?php echo htmlspecialchars($personal['full_name']); ?></div>
                <div class="title"><?php echo "Full Stack Developer" ?></div>
            </div>
            
            <div class="section">
                <div class="section-title"><i class="fas fa-address-book"></i> Contact</div>
                <?php 
                echo generateContactItem('fas fa-phone', $personal['number'] ?: 'Not provided');
                echo generateContactItem('fas fa-envelope', $personal['email']);
                echo generateContactItem('fas fa-map-marker-alt', $personal['location']);
                echo generateContactItem('fab fa-linkedin', $personal['linkedin']);
                ?>
            </div>
            
            <?php if (!empty($personalSkills)): ?>
            <div class="section">
                <div class="section-title"><i class="fas fa-cogs"></i> Professional Skills</div>
                <?php foreach ($personalSkills as $skill) echo generateSkillBar($skill); ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($technicalSkills)): ?>
            <div class="section">
                <div class="section-title"><i class="fas fa-code"></i> Technical Skills</div>
                <div class="technical-skills">
                    <?php foreach ($technicalSkills as $skill): ?>
                        <div class="tech-skill">
                            <span><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="right-section">
            <div class="section">
                <div class="section-title"><i class="fas fa-bullseye"></i> Summary</div>
                <div class="objective"><?php echo htmlspecialchars($personal['summary']); ?></div>
            </div>
            
            <?php if (!empty($education)): ?>
            <div class="section">
                <div class="section-title"><i class="fas fa-graduation-cap"></i> Education</div>
                <?php foreach ($education as $edu): ?>
                    <div class="education-item">
                        <h3><?php echo htmlspecialchars($edu['degree']); ?></h3>
                        <div class="education-details"><?php echo htmlspecialchars($edu['institution']); ?></div>
                        <div class="date"><?php echo htmlspecialchars($edu['date_range']); ?></div>
                        <?php if ($edu['gwa']): ?>
                        <div class="gwa">GWA: <?php echo htmlspecialchars($edu['gwa']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($projects)): ?>
            <div class="section">
                <div class="section-title"><i class="fas fa-project-diagram"></i> Projects</div>
                <div class="project-list">
                    <?php foreach ($projects as $proj): ?>
                    <div class="project-item">
                        <h3><?php echo htmlspecialchars($proj['project_name']); ?></h3>
                        <div class="project-details"><?php echo htmlspecialchars($proj['description']); ?></div>
                        <?php if ($proj['date']): ?>
                        <div class="date"><?php echo htmlspecialchars($proj['date']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>  
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <button class="print-btn" onclick="window.print()" title="Print Resume"><i class="fas fa-print"></i></button>
</body>
</html>