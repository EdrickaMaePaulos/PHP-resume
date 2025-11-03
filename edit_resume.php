<?php
require_once 'auth.php';

$auth->requireLogin();

$userId = $_SESSION['user_id'];
$message = '';
$success = false;

// Fetch current data
$personal = $pdo->prepare("SELECT * FROM personal_info WHERE user_id = ? LIMIT 1");
$personal->execute([$userId]);
$personal = $personal->fetch(PDO::FETCH_ASSOC);

if (!$personal) {
    // Create initial record if none exists
    $stmt = $pdo->prepare("INSERT INTO personal_info (user_id, full_name, email) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $_SESSION['firstName'] . ' ' . $_SESSION['lastName'], $_SESSION['email']]);
    $personal = ['user_id' => $userId, 'full_name' => '', 'email' => $_SESSION['email']];
}

$personalId = $personal['id'] ?? null;

// Handle profile picture deletion
if (isset($_GET['delete_pic']) && $_GET['delete_pic'] == '1') {
    try {
        // Delete the file from server
        if (!empty($personal['profile_pic']) && file_exists('uploads/profiles/' . $personal['profile_pic'])) {
            unlink('uploads/profiles/' . $personal['profile_pic']);
        }
        
        // Update database
        $stmt = $pdo->prepare("UPDATE personal_info SET profile_pic = NULL WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        header('Location: edit_resume.php?pic_deleted=1');
        exit();
    } catch (Exception $e) {
        $message = 'Error deleting profile picture: ' . $e->getMessage();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Handle profile picture upload
$profilePic = $personal['profile_pic'] ?? null;

if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $tmpPath = $_FILES['profile_pic']['tmp_name'];
    
    // Use getimagesize to properly detect file type
    $imageInfo = @getimagesize($tmpPath);
    
    if ($imageInfo && in_array($imageInfo['mime'], $allowedTypes)) {
        $fileType = $imageInfo['mime'];
        
        // Delete old profile picture if exists
        if (!empty($personal['profile_pic']) && file_exists('uploads/profiles/' . $personal['profile_pic'])) {
            unlink('uploads/profiles/' . $personal['profile_pic']);
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = 'uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $profilePic = uniqid('profile_') . '.' . $extension;
        $targetPath = $uploadDir . $profilePic;
        
        try {
            // Create image resource based on MIME type
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
                    throw new Exception("Unsupported image type");
            }
            
            // Check if image creation was successful
            if (!$sourceImage) {
                throw new Exception("Failed to create image from file");
            }
            
            // Get original dimensions
            list($origWidth, $origHeight) = getimagesize($tmpPath);
            
            // Set new dimensions (resize to 300x300)
            $newWidth = 300;
            $newHeight = 300;
            
            // Create new image with desired dimensions
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            
            if (!$resizedImage) {
                imagedestroy($sourceImage);
                throw new Exception("Failed to create resized image");
            }
            
            // Preserve transparency for PNG and GIF
            if ($fileType == 'image/png' || $fileType == 'image/gif') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
            }
            
            // Resize the image
            imagecopyresampled(
                $resizedImage, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $origWidth, $origHeight
            );
            
            // Save the resized image
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
            
            // Free up memory
            imagedestroy($sourceImage);
            imagedestroy($resizedImage);
            
        } catch (Exception $e) {
            // If image processing fails, just store the filename
            // The image won't be resized but will still be saved
            if (move_uploaded_file($tmpPath, $targetPath)) {
                $profilePic = basename($targetPath);
            } else {
                $profilePic = $personal['profile_pic'] ?? null;
            }
        }
    } else {
        $message = 'Invalid image format. Please upload JPG, PNG, or GIF image.';
    }
}

        
        // Update personal info (including profile picture)
        $stmt = $pdo->prepare("
            UPDATE personal_info 
            SET full_name = ?, role = ?, email = ?, number = ?, location = ?, linkedin = ?, summary = ?, profile_pic = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $_POST['full_name'],
            $_POST['role'],
            $_POST['email'],
            $_POST['number'],
            $_POST['location'],
            $_POST['linkedin'],
            $_POST['summary'],
            $profilePic,
            $userId
        ]);
        
        // Update professional skills
        if (isset($_POST['professional_skills'])) {
            $pdo->prepare("DELETE FROM professional_skills WHERE personal_id = ?")->execute([$personalId]);
            foreach ($_POST['professional_skills'] as $index => $skill) {
                if (!empty($skill)) {
                    $percentage = $_POST['skill_percentages'][$index] ?? 80;
                    $stmt = $pdo->prepare("INSERT INTO professional_skills (personal_id, skill_name, percentage) VALUES (?, ?, ?)");
                    $stmt->execute([$personalId, $skill, $percentage]);
                }
            }
        }
        
        // Update technical skills
        if (isset($_POST['technical_skills'])) {
            $pdo->prepare("DELETE FROM technical_skills WHERE personal_id = ?")->execute([$personalId]);
            foreach ($_POST['technical_skills'] as $skill) {
                if (!empty($skill)) {
                    $stmt = $pdo->prepare("INSERT INTO technical_skills (personal_id, skill_name) VALUES (?, ?)");
                    $stmt->execute([$personalId, $skill]);
                }
            }
        }
        
        // Update education
        if (isset($_POST['degree'])) {
            $pdo->prepare("DELETE FROM education WHERE personal_id = ?")->execute([$personalId]);
            foreach ($_POST['degree'] as $index => $degree) {
                if (!empty($degree)) {
                    $stmt = $pdo->prepare("INSERT INTO education (personal_id, degree, institution, date_range, gwa) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $personalId,
                        $degree,
                        $_POST['institution'][$index] ?? '',
                        $_POST['date_range'][$index] ?? '',
                        $_POST['gwa'][$index] ?? null
                    ]);
                }
            }
        }
        
        // Update projects
        if (isset($_POST['project_name'])) {
            $pdo->prepare("DELETE FROM projects WHERE personal_id = ?")->execute([$personalId]);
            foreach ($_POST['project_name'] as $index => $projectName) {
                if (!empty($projectName)) {
                    $stmt = $pdo->prepare("INSERT INTO projects (personal_id, project_name, description, date) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $personalId,
                        $projectName,
                        $_POST['project_description'][$index] ?? '',
                        $_POST['project_date'][$index] ?? ''
                    ]);
                }
            }
        }
        
        $pdo->commit();
        $success = true;
        $message = 'Resume updated successfully!';
        
        // Refresh data
        $personal = $pdo->prepare("SELECT * FROM personal_info WHERE user_id = ? LIMIT 1");
        $personal->execute([$userId]);
        $personal = $personal->fetch(PDO::FETCH_ASSOC);
        $personalId = $personal['id'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Error updating resume: ' . $e->getMessage();
    }
}

// Check for success messages
if (isset($_GET['pic_deleted'])) {
    $success = true;
    $message = 'Profile picture deleted successfully!';
}

// Fetch current skills, education, projects
$profSkills = $pdo->prepare("SELECT * FROM professional_skills WHERE personal_id = ? ORDER BY id DESC");
$profSkills->execute([$personalId]);
$profSkills = $profSkills->fetchAll(PDO::FETCH_ASSOC);

$techSkills = $pdo->prepare("SELECT * FROM technical_skills WHERE personal_id = ? ORDER BY id DESC");
$techSkills->execute([$personalId]);
$techSkills = $techSkills->fetchAll(PDO::FETCH_ASSOC);

$education = $pdo->prepare("SELECT * FROM education WHERE personal_id = ? ORDER BY id DESC");
$education->execute([$personalId]);
$education = $education->fetchAll(PDO::FETCH_ASSOC);

$projects = $pdo->prepare("SELECT * FROM projects WHERE personal_id = ? ORDER BY id DESC");
$projects->execute([$personalId]);
$projects = $projects->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resume - Resume Builder</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="styles\edit.css" rel="stylesheet">
</head>
<body>
    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="edit-container">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Edit Your Resume</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="edit-form">
            <!-- Personal Information Section -->
            <div class="form-section">
                <h2><i class="fas fa-user"></i> Personal Information</h2>

                <!-- Profile Picture Upload Section -->
<div class="form-group full-width">
    <label>Profile Picture</label>

    <div class="upload-circle" id="uploadCircle">
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
    

        <button type="button" id="removePhoto" class="remove-photo" title="Remove photo" 
            <?php echo (!empty($personal['profile_pic']) && file_exists('uploads/profiles/' . $personal['profile_pic'])) ? 'style="display:flex;"' : ''; ?>>
            &times;
        </button>
    </div>

    <input type="file" id="profile_pic" name="profile_pic" accept="image/*" style="display:none;" onchange="previewImage(this)">
    
    <!-- NEW: Show message when picture exists -->
    <?php if (!empty($personal['profile_pic']) && file_exists('uploads/profiles/' . $personal['profile_pic'])): ?>
        <p style="text-align: center; color: #60a5fa; font-size: 13px; margin-top: 10px;">
            <i class="fas fa-check-circle"></i> Current picture displayed • Click circle to change
        </p>
    <?php else: ?>
        <p style="text-align: center; color: #93c5fd; font-size: 13px; margin-top: 10px;">
            <i class="fas fa-info-circle"></i> No profile picture uploaded yet
        </p>
    <?php endif; ?>
</div>


                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($personal['full_name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="role">Role *</label>
                        <input type="text" id="role" name="role" value="<?php echo htmlspecialchars($personal['role'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($personal['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="number">Phone Number</label>
                        <input type="tel" id="number" name="number" value="<?php echo htmlspecialchars($personal['number'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($personal['location'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="linkedin">LinkedIn Profile</label>
                        <input type="text" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($personal['linkedin'] ?? ''); ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="summary">Professional Summary *</label>
                        <textarea id="summary" name="summary" required placeholder="Brief description of your professional background and goals..."><?php echo htmlspecialchars($personal['summary'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Professional Skills Section -->
            <div class="form-section">
                <h2><i class="fas fa-cogs"></i> Professional Skills</h2>
                <div id="professional-skills">
                    <?php foreach ($profSkills as $skill): ?>
                        <div class="skill-row">
                            <input type="text" name="professional_skills[]" placeholder="e.g., Project Management" value="<?php echo htmlspecialchars($skill['skill_name']); ?>">
                            <input type="number" name="skill_percentages[]" min="1" max="100" placeholder="%" value="<?php echo htmlspecialchars($skill['percentage']); ?>">
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-add" onclick="addProfessionalSkill()">
                    <i class="fas fa-plus"></i> Add Professional Skill
                </button>
            </div>

            <!-- Technical Skills Section -->
            <div class="form-section">
                <h2><i class="fas fa-code"></i> Technical Skills</h2>
                <div id="technical-skills">
                    <?php foreach ($techSkills as $skill): ?>
                        <div class="tech-skill-row">
                            <input type="text" name="technical_skills[]" placeholder="e.g., Python, JavaScript, React" value="<?php echo htmlspecialchars($skill['skill_name']); ?>">
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-add" onclick="addTechnicalSkill()">
                    <i class="fas fa-plus"></i> Add Technical Skill
                </button>
            </div>

            <!-- Education Section -->
            <div class="form-section">
                <h2><i class="fas fa-graduation-cap"></i> Education</h2>
                <div id="education-section">
                    <?php foreach ($education as $edu): ?>
                        <div class="education-row">
                            <input type="text" name="degree[]" placeholder="Degree" value="<?php echo htmlspecialchars($edu['degree']); ?>">
                            <input type="text" name="institution[]" placeholder="Institution" value="<?php echo htmlspecialchars($edu['institution']); ?>">
                            <input type="text" name="date_range[]" placeholder="2020 - 2024" value="<?php echo htmlspecialchars($edu['date_range']); ?>">
                            <input type="number" name="gwa[]" step="0.01" min="1" max="4" placeholder="GWA" value="<?php echo htmlspecialchars($edu['gwa'] ?? ''); ?>">
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-add" onclick="addEducation()">
                    <i class="fas fa-plus"></i> Add Education
                </button>
            </div>

            <!-- Projects Section -->
            <div class="form-section">
                <h2><i class="fas fa-project-diagram"></i> Projects</h2>
                <div id="projects-section">
                    <?php foreach ($projects as $proj): ?>
                        <div class="project-row">
                            <input type="text" name="project_name[]" placeholder="Project Name" value="<?php echo htmlspecialchars($proj['project_name']); ?>">
                            <textarea name="project_description[]" placeholder="Description"><?php echo htmlspecialchars($proj['description']); ?></textarea>
                            <input type="text" name="project_date[]" placeholder="Jan 2024 - Mar 2024" value="<?php echo htmlspecialchars($proj['date'] ?? ''); ?>">
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-add" onclick="addProject()">
                    <i class="fas fa-plus"></i> Add Project
                </button>
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <script>
        // Profile picture preview
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
                    removeBtn.style.display = 'flex';
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
            removeBtn.style.display = 'none';
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
