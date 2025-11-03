<?php
require_once 'db.php';

// Get resume ID from URL parameter
$resume_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($resume_id === 0) {
    die("Resume not found.");
}

try {
    // Fetch personal info by ID (not user_id) - PUBLIC ACCESS
    $personal = $pdo->prepare("SELECT * FROM personal_info WHERE id = ? LIMIT 1");
    $personal->execute([$resume_id]);
    $personal = $personal->fetch(PDO::FETCH_ASSOC);

    if (!$personal) {
        die("Resume not found.");
    }

    // Fetch professional skills
    $personalSkills = $pdo->prepare("SELECT * FROM professional_skills WHERE personal_id = ? ORDER BY percentage DESC");
    $personalSkills->execute([$resume_id]);
    $personalSkills = $personalSkills->fetchAll(PDO::FETCH_ASSOC);

    // Fetch technical skills
    $technicalSkills = $pdo->prepare("SELECT * FROM technical_skills WHERE personal_id = ?");
    $technicalSkills->execute([$resume_id]);
    $technicalSkills = $technicalSkills->fetchAll(PDO::FETCH_ASSOC);

    // Fetch projects
    $projects = $pdo->prepare("SELECT * FROM projects WHERE personal_id = ? ORDER BY date DESC");
    $projects->execute([$resume_id]);
    $projects = $projects->fetchAll(PDO::FETCH_ASSOC);

    // Fetch education
    $education = $pdo->prepare("SELECT * FROM education WHERE personal_id = ? ORDER BY id DESC");
    $education->execute([$resume_id]);
    $education = $education->fetchAll(PDO::FETCH_ASSOC);

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

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($personal['full_name'] ?? 'Resume'); ?> - Resume</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="styles/resume.css" rel="stylesheet">
</head>

<body>
        <a href="landing_page.php" class="back-btn" title="Back to Landing Page">
            <i class="fas fa-home"></i>
        </a>
    
    <div class="resume-container">
        <div class="left-section">
            <div class="profile-section">
        <?php 
            // Get profile picture from database
            $profilePath = 'uploads/profiles/' . $personal['profile_pic'];
            if (!empty($personal['profile_pic']) && file_exists($profilePath)): 
        ?>
            <img src="<?php echo htmlspecialchars($profilePath); ?>" 
                alt="<?php echo htmlspecialchars($personal['full_name']); ?>" 
                class="profile-picture"
                onerror="this.src='uploads/profiles/profile_no.jpg';">
        <?php else: ?>
            <img src="uploads/profiles/profile_no.jpg" alt="Profile Picture" class="profile-picture">
        <?php endif; ?>
        
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