<?php
require_once 'auth.php';

$auth->requireLogin();

$message = '';
$userId = $_SESSION['user_id'];

// Check if user already has a resume
$existing = $pdo->prepare("SELECT id FROM personal_info WHERE user_id = ? LIMIT 1");
$existing->execute([$userId]);

if ($existing->fetch()) {
    header('Location: edit_resume.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Insert personal info
        $stmt = $pdo->prepare("
            INSERT INTO personal_info (user_id, full_name, location, email, number, linkedin, summary) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $_POST['full_name'],
            $_POST['location'],
            $_POST['email'],
            $_POST['number'],
            $_POST['linkedin'],
            $_POST['summary']
        ]);
        
        $personalId = $pdo->lastInsertId();
        
        // Insert professional skills
        if (!empty($_POST['professional_skills'])) {
            $profSkillStmt = $pdo->prepare("INSERT INTO professional_skills (personal_id, skill_name, percentage) VALUES (?, ?, ?)");
            foreach ($_POST['professional_skills'] as $index => $skill) {
                if (!empty($skill)) {
                    $percentage = $_POST['skill_percentages'][$index] ?? 80;
                    $profSkillStmt->execute([$personalId, $skill, $percentage]);
                }
            }
        }
        
        // Insert technical skills
        if (!empty($_POST['technical_skills'])) {
            $techSkillStmt = $pdo->prepare("INSERT INTO technical_skills (personal_id, skill_name) VALUES (?, ?)");
            foreach ($_POST['technical_skills'] as $skill) {
                if (!empty($skill)) {
                    $techSkillStmt->execute([$personalId, $skill]);
                }
            }
        }
        
        // Insert education
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
        
        // Insert projects
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
        header('Location: home.php?created=1');
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
            color: #333;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(17, 49, 69, 0.3);
            overflow: hidden;
        }

        .header {
            background: #131922;
            color: white;
            padding: 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-container {
            padding: 40px;
        }

        .section-title {
            font-size: 20px;
            color: #131922;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #131922;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #131922;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #131922;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .dynamic-section {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .add-btn {
            background: #16a34a;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }

        .remove-btn {
            background: #dc2626;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
        }

        .submit-btn {
            background: #131922;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 30px;
            width: 100%;
        }

        .submit-btn:hover {
            background: #1e293b;
        }

        .back-link {
            display: inline-block;
            color: #131922;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        .skill-with-percentage {
            display: flex;
            gap: 15px;
            align-items: end;
        }

        .skill-with-percentage input[type="range"] {
            flex: 0 0 100px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-plus-circle"></i> Create Your Resume</h1>
            <p>Fill in your information to build your professional resume</p>
        </div>

        <div class="form-container">
            <a href="home.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>

            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="section-title">
                    <i class="fas fa-user"></i> Personal Information
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="number">Phone Number</label>
                        <input type="tel" id="number" name="number">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location">
                    </div>
                    <div class="form-group">
                        <label for="linkedin">LinkedIn Profile</label>
                        <input type="text" id="linkedin" name="linkedin">
                    </div>
                </div>

                <div class="form-group">
                    <label for="summary">Professional Summary *</label>
                    <textarea id="summary" name="summary" required placeholder="Brief description of your professional background and goals..."></textarea>
                </div>

                <div class="section-title">
                    <i class="fas fa-cogs"></i> Professional Skills
                </div>

                <div id="professional-skills">
                    <div class="skill-with-percentage">
                        <div class="form-group" style="flex: 1;">
                            <label>Skill Name</label>
                            <input type="text" name="professional_skills[]" placeholder="e.g., Project Management">
                        </div>
                        <div class="form-group" style="flex: 0 0 150px;">
                            <label>Proficiency (%)</label>
                            <input type="number" name="skill_percentages[]" min="1" max="100" value="80">
                        </div>
                    </div>
                </div>
                <button type="button" class="add-btn" onclick="addProfessionalSkill()">Add Professional Skill</button>

                <div class="section-title">
                    <i class="fas fa-code"></i> Technical Skills
                </div>

                <div id="technical-skills">
                    <div class="form-group">
                        <input type="text" name="technical_skills[]" placeholder="e.g., Python, JavaScript, React">
                    </div>
                </div>
                <button type="button" class="add-btn" onclick="addTechnicalSkill()">Add Technical Skill</button>

                <div class="section-title">
                    <i class="fas fa-graduation-cap"></i> Education
                </div>

                <div id="education-section">
                    <div class="dynamic-section">
                        <div class="form-group">
                            <label>Degree</label>
                            <input type="text" name="degree[]" placeholder="e.g., Bachelor of Science in Computer Science">
                        </div>
                        <div class="form-group">
                            <label>Institution</label>
                            <input type="text" name="institution[]" placeholder="e.g., University Name">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date Range</label>
                                <input type="text" name="date_range[]" placeholder="e.g., 2020 - 2024">
                            </div>
                            <div class="form-group">
                                <label>GWA/GPA (Optional)</label>
                                <input type="number" name="gwa[]" step="0.01" min="1" max="4" placeholder="3.50">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="add-btn" onclick="addEducation()">Add Education</button>

                <div class="section-title">
                    <i class="fas fa-project-diagram"></i> Projects
                </div>

                <div id="projects-section">
                    <div class="dynamic-section">
                        <div class="form-group">
                            <label>Project Name</label>
                            <input type="text" name="project_name[]" placeholder="e.g., E-commerce Website">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="project_description[]" placeholder="Brief description of the project and your role..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Date/Duration</label>
                            <input type="text" name="project_date[]" placeholder="e.g., Jan 2024 - Mar 2024">
                        </div>
                    </div>
                </div>
                <button type="button" class="add-btn" onclick="addProject()">Add Project</button>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Create Resume
                </button>
            </form>
        </div>
    </div>

    <script>
        function addProfessionalSkill() {
            const container = document.getElementById('professional-skills');
            const div = document.createElement('div');
            div.className = 'skill-with-percentage';
            div.innerHTML = `
                <div class="form-group" style="flex: 1;">
                    <input type="text" name="professional_skills[]" placeholder="e.g., Project Management">
                </div>
                <div class="form-group" style="flex: 0 0 150px;">
                    <input type="number" name="skill_percentages[]" min="1" max="100" value="80">
                </div>
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(div);
        }

        function addTechnicalSkill() {
            const container = document.getElementById('technical-skills');
            const div = document.createElement('div');
            div.className = 'form-group';
            div.innerHTML = `
                <input type="text" name="technical_skills[]" placeholder="e.g., Python, JavaScript, React">
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(div);
        }

        function addEducation() {
            const container = document.getElementById('education-section');
            const div = document.createElement('div');
            div.className = 'dynamic-section';
            div.innerHTML = `
                <div class="form-group">
                    <label>Degree</label>
                    <input type="text" name="degree[]" placeholder="e.g., Bachelor of Science in Computer Science">
                </div>
                <div class="form-group">
                    <label>Institution</label>
                    <input type="text" name="institution[]" placeholder="e.g., University Name">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Date Range</label>
                        <input type="text" name="date_range[]" placeholder="e.g., 2020 - 2024">
                    </div>
                    <div class="form-group">
                        <label>GWA/GPA (Optional)</label>
                        <input type="number" name="gwa[]" step="0.01" min="1" max="4" placeholder="3.50">
                    </div>
                </div>
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(div);
        }

        function addProject() {
            const container = document.getElementById('projects-section');
            const div = document.createElement('div');
            div.className = 'dynamic-section';
            div.innerHTML = `
                <div class="form-group">
                    <label>Project Name</label>
                    <input type="text" name="project_name[]" placeholder="e.g., E-commerce Website">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="project_description[]" placeholder="Brief description of the project and your role..."></textarea>
                </div>
                <div class="form-group">
                    <label>Date/Duration</label>
                    <input type="text" name="project_date[]" placeholder="e.g., Jan 2024 - Mar 2024">
                </div>
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">Remove</button>
            `;
            container.appendChild(div);
        }
    </script>
</body>
</html>