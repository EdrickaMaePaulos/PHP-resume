<?php
require_once 'auth.php';

$auth->requireLogin();

$message = '';
$userId = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $profilePic = null;
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $fileType = $_FILES['profile_pic']['type'];
            
            if (in_array($fileType, $allowedTypes)) {
                $uploadDir = 'uploads/profiles/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
                $profilePic = uniqid('profile_') . '.' . $extension;
                $targetPath = $uploadDir . $profilePic;
                
                $tmpPath = $_FILES['profile_pic']['tmp_name'];
                
                switch ($fileType) {
                    case 'image/jpeg':
                    case 'image/jpg':
                        $sourceImage = imagecreatefromjpeg($tmpPath);
                        break;
                    case 'image/png':
                        $sourceImage = imagecreatefrompng($tmpPath);
                        break;
                    case 'image/gif':
                        $sourceImage = imagecreatefromgif($tmpPath);
                        break;
                    default:
                        $sourceImage = imagecreatefromjpeg($tmpPath);
                }
                
                list($origWidth, $origHeight) = getimagesize($tmpPath);
                
                $newWidth = 300;
                $newHeight = 300;
                
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
                
                if ($fileType == 'image/png') {
                    imagealphablending($resizedImage, false);
                    imagesavealpha($resizedImage, true);
                }
                
                imagecopyresampled(
                    $resizedImage, $sourceImage,
                    0, 0, 0, 0,
                    $newWidth, $newHeight,
                    $origWidth, $origHeight
                );
                
                switch ($fileType) {
                    case 'image/jpeg':
                    case 'image/jpg':
                        imagejpeg($resizedImage, $targetPath, 90);
                        break;
                    case 'image/png':
                        imagepng($resizedImage, $targetPath);
                        break;
                    case 'image/gif':
                        imagegif($resizedImage, $targetPath);
                        break;
                }
                
                imagedestroy($sourceImage);
                imagedestroy($resizedImage);
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO personal_info (user_id, full_name, role, location, email, number, linkedin, summary, profile_pic) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $_POST['full_name'],
            $_POST['role'],
            $_POST['location'],
            $_POST['email'],
            $_POST['number'],
            $_POST['linkedin'],
            $_POST['summary'],
            $profilePic
        ]);
        
        $personalId = $pdo->lastInsertId();
        
        if (!empty($_POST['professional_skills'])) {
            $profSkillStmt = $pdo->prepare("INSERT INTO professional_skills (personal_id, skill_name, percentage) VALUES (?, ?, ?)");
            foreach ($_POST['professional_skills'] as $index => $skill) {
                if (!empty($skill)) {
                    $percentage = $_POST['skill_percentages'][$index] ?? 80;
                    $profSkillStmt->execute([$personalId, $skill, $percentage]);
                }
            }
        }
        
        if (!empty($_POST['technical_skills'])) {
            $techSkillStmt = $pdo->prepare("INSERT INTO technical_skills (personal_id, skill_name) VALUES (?, ?)");
            foreach ($_POST['technical_skills'] as $skill) {
                if (!empty($skill)) {
                    $techSkillStmt->execute([$personalId, $skill]);
                }
            }
        }
        
        if (!empty($_POST['degree'])) {
            $eduStmt = $pdo->prepare("INSERT INTO education (personal_id, degree, institution, date_range, gwa) VALUES (?, ?, ?, ?, ?)");
            foreach ($_POST['degree'] as $index => $degree) {
                if (!empty($degree)) {
                    $eduStmt->execute([
                        $personalId,
                        $degree,
                        $_POST['institution'][$index] ?? '',
                        $_POST['date_range'][$index] ?? '',
                        $_POST['gwa'][$index] ?? null
                    ]);
                }
            }
        }
        
        if (!empty($_POST['project_name'])) {
            $projStmt = $pdo->prepare("INSERT INTO projects (personal_id, project_name, description, date) VALUES (?, ?, ?, ?)");
            foreach ($_POST['project_name'] as $index => $projectName) {
                if (!empty($projectName)) {
                    $projStmt->execute([
                        $personalId,
                        $projectName,
                        $_POST['project_description'][$index] ?? '',
                        $_POST['project_date'][$index] ?? ''
                    ]);
                }
            }
        }
        
        $pdo->commit();
        header('Location: dashboard.php?created=1');
        exit();
        
    } catch (Exception $e) {
        $pdo->rollback();
        $message = 'Error creating resume: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Resume - Resume Builder</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="styles\create_resume.css" rel="stylesheet">
</head>
<body>
    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="edit-container">

        <?php if ($message): ?>
            <div class="alert error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="edit-form">
            <div class="form-section">
                <h2><i class="fas fa-user"></i> Personal Information</h2>

                <div class="form-group full-width">
                    <label>Profile Picture</label>

                    <div class="upload-circle" id="uploadCircle">
                        <div id="uploadPlaceholder" class="upload-placeholder">
                            <i class="fas fa-camera"></i>
                            <p>Click to upload</p>
                            <small>Square image recommended<br>Will be resized to 300×300px</small>
                        </div>

                        <img id="preview" class="profile-pic-preview" alt="Profile Preview" style="display:none;">
                        <button type="button" id="removePhoto" class="remove-photo" title="Remove photo">&times;</button>
                    </div>

                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*" style="display:none;" onchange="previewImage(this)">
                </div>

                <div class="form-grid">

                    <div class="form-group full-width">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="role">Role *</label>
                        <input type="text" id="role" name="role" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="number">Phone Number</label>
                        <input type="tel" id="number" name="number">
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location">
                    </div>

                    <div class="form-group">
                        <label for="linkedin">LinkedIn Profile</label>
                        <input type="text" id="linkedin" name="linkedin">
                    </div>

                    <div class="form-group full-width">
                        <label for="summary">Professional Summary *</label>
                        <textarea id="summary" name="summary" required placeholder="Brief description of your professional background and goals..."></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-cogs"></i> Professional Skills</h2>
                <div id="professional-skills">
                    <div class="skill-row">
                        <input type="text" name="professional_skills[]" placeholder="e.g., Project Management">
                        <input type="number" name="skill_percentages[]" min="1" max="100" value="80" placeholder="%">
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addProfessionalSkill()">
                    <i class="fas fa-plus"></i> Add Professional Skill
                </button>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-code"></i> Technical Skills</h2>
                <div id="technical-skills">
                    <div class="tech-skill-row">
                        <input type="text" name="technical_skills[]" placeholder="e.g., Python, JavaScript, React">
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addTechnicalSkill()">
                    <i class="fas fa-plus"></i> Add Technical Skill
                </button>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-graduation-cap"></i> Education</h2>
                <div id="education-section">
                    <div class="education-row">
                        <input type="text" name="degree[]" placeholder="Degree">
                        <input type="text" name="institution[]" placeholder="Institution">
                        <input type="text" name="date_range[]" placeholder="2020 - 2024">
                        <input type="number" name="gwa[]" step="0.01" min="1" max="4" placeholder="GWA">
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addEducation()">
                    <i class="fas fa-plus"></i> Add Education
                </button>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-project-diagram"></i> Projects</h2>
                <div id="projects-section">
                    <div class="project-row">
                        <input type="text" name="project_name[]" placeholder="Project Name">
                        <textarea name="project_description[]" placeholder="Description"></textarea>
                        <input type="text" name="project_date[]" placeholder="Jan 2024 - Mar 2024">
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn-add" onclick="addProject()">
                    <i class="fas fa-plus"></i> Add Project
                </button>
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Create Resume
                </button>
            </div>
        </form>
    </div>

    <script>
        const previewImg = document.getElementById('preview');
        const placeholder = document.getElementById('uploadPlaceholder');
        const removeBtn = document.getElementById('removePhoto');
        const fileInput = document.getElementById('profile_pic');
        const uploadCircle = document.getElementById('uploadCircle');

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                const allowed = ['image/jpeg','image/png','image/jpg','image/gif'];
                if (!allowed.includes(file.type)) {
                    alert('Only JPG, PNG or GIF images are allowed.');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    placeholder.style.display = 'none';
                    removeBtn.classList.add('visible');
                };
                reader.readAsDataURL(file);
            } else {
                clearPhoto();
            }
        }

        function clearPhoto() {
            fileInput.value = '';
            previewImg.src = '';
            previewImg.style.display = 'none';
            placeholder.style.display = 'block';
            removeBtn.classList.remove('visible');
        }

        removeBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            clearPhoto();
        });

        uploadCircle.addEventListener('click', function(e) {
            if (!e.target.closest('#removePhoto')) {
                fileInput.click();
            }
        });

        previewImg.addEventListener('click', function(e) {
            e.stopPropagation();
            fileInput.click();
        });

        function addProfessionalSkill() {
            const container = document.getElementById('professional-skills');
            const div = document.createElement('div');
            div.className = 'skill-row';
            div.innerHTML = `
                <input type="text" name="professional_skills[]" placeholder="e.g., Project Management">
                <input type="number" name="skill_percentages[]" min="1" max="100" value="80" placeholder="%">
                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
        }

        function addTechnicalSkill() {
            const container = document.getElementById('technical-skills');
            const div = document.createElement('div');
            div.className = 'tech-skill-row';
            div.innerHTML = `
                <input type="text" name="technical_skills[]" placeholder="e.g., Python, JavaScript, React">
                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
        }

        function addEducation() {
            const container = document.getElementById('education-section');
            const div = document.createElement('div');
            div.className = 'education-row';
            div.innerHTML = `
                <input type="text" name="degree[]" placeholder="Degree">
                <input type="text" name="institution[]" placeholder="Institution">
                <input type="text" name="date_range[]" placeholder="2020 - 2024">
                <input type="number" name="gwa[]" step="0.01" min="1" max="4" placeholder="GWA">
                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
        }

        function addProject() {
            const container = document.getElementById('projects-section');
            const div = document.createElement('div');
            div.className = 'project-row';
            div.innerHTML = `
                <input type="text" name="project_name[]" placeholder="Project Name">
                <textarea name="project_description[]" placeholder="Description"></textarea>
                <input type="text" name="project_date[]" placeholder="Jan 2024 - Mar 2024">
                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(div);
        }
    </script>
</body>
</html>
