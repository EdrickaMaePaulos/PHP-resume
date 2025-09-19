<?php
require_once 'db.php';

$personal = $pdo->query("SELECT * FROM personal_info LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$personalSkills = $pdo->query("SELECT skill_name, percentage FROM professional_skills WHERE personal_id=1")->fetchAll(PDO::FETCH_ASSOC);
$education = $pdo->query("SELECT degree, institution, date_range, gwa FROM education WHERE personal_id=1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$technicalSkills = $pdo->query("SELECT skill_name FROM technical_skills WHERE personal_id=1")->fetchAll(PDO::FETCH_ASSOC);
$projects = $pdo->query("SELECT project_name, description, date FROM projects WHERE personal_id=1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

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
</head>
<body>
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
                echo generateContactItem('fas fa-phone', $personal['number']);
                echo generateContactItem('fas fa-envelope', $personal['email']);
                echo generateContactItem('fas fa-map-marker-alt', $personal['location']);
                echo generateContactItem('fab fa-linkedin', $personal['linkedin']);
                ?>
            </div>
            
            <div class="section">
                <div class="section-title"><i class="fas fa-cogs"></i> Professional Skills</div>
                <?php foreach ($personalSkills as $skill) echo generateSkillBar($skill); ?>
            </div>

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
        </div>
        
        <div class="right-section">
            <div class="section">
                <div class="section-title"><i class="fas fa-bullseye"></i> Summary</div>
                <div class="objective"><?php echo htmlspecialchars($personal['summary']); ?></div>
            </div>
            
            <div class="section">
                <div class="section-title"><i class="fas fa-graduation-cap"></i> Education</div>
                <?php foreach ($education as $edu): ?>
                    <div class="education-item">
                        <h3><?php echo htmlspecialchars($edu['degree']); ?></h3>
                        <div class="education-details"><?php echo htmlspecialchars($edu['institution']); ?></div>
                        <div class="date"><?php echo htmlspecialchars($edu['date_range']); ?></div>
                        <div class="gwa">GWA: <?php echo htmlspecialchars($edu['gwa']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            

            <div class="section">
                <div class="section-title"><i class="fas fa-project-diagram"></i> Projects</div>
                <div class="project-list">
                    <?php foreach ($projects as $proj): ?>
                    <div class="project-item">
                        <h3><?php echo htmlspecialchars($proj['project_name']); ?></h3>
                        <div class="project-details"><?php echo htmlspecialchars($proj['description']); ?></div>
                        <div class="date"><?php echo htmlspecialchars($proj['date']); ?></div>
                    </div>
                <?php endforeach; ?>
                </div>  
            </div>
        </div>
    </div>
    
    <button class="print-btn" onclick="window.print()" title="Print Resume"><i class="fas fa-print"></i></button>
</body>
</html>
