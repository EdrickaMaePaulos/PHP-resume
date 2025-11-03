<?php
// Include database connection
require_once 'db.php';


// Check if connection is valid
if (!isset($pdo) || $pdo === null) {
    die("Database connection failed. Please check your db.php file.");
}


// Fetch all resumes from personal_info table
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$resumes = [];


try {
    if (!empty($search_query)) {
        // Search by full name, email, or location
        $search_term = "%" . $search_query . "%";
        $sql = "SELECT id, full_name, role,  email,  location, summary, profile_pic FROM personal_info WHERE 
                full_name ILIKE :search OR email ILIKE :search OR location ILIKE :search LIMIT 12";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':search' => $search_term]);
        $resumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fetch all resumes
        $sql = "SELECT id, full_name, role,  email, location, summary, profile_pic FROM personal_info LIMIT 12";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $resumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="styles/landing_page.css" rel="stylesheet">
    <style>
        
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <div class="logo">
                
            </div>
            <div class="nav-right">
                <a href="signin.php" class="nav-btn signin">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </a>
                <a href="signup.php" class="nav-btn signup">
                    <i class="fas fa-user-plus"></i>
                    Sign Up
                </a>
            </div>
        </div>
    </nav>


    <div class="container">
        <div class="search-section">
            <h2 class="search-title">Browse Public Resumes</h2>
            <form class="search-wrapper" id="searchForm">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search by name, role, email, or location..." 
                    value="<?php echo htmlspecialchars($search_query); ?>"
                    id="searchInput"
                >
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                    Search
                </button>
            </form>
        </div>


        <div class="resumes-grid">
            <?php if (!empty($resumes)): ?>
                <?php foreach ($resumes as $index => $resume): ?>
                    <a href="public_resume.php?id=<?php echo urlencode($resume['id']); ?>" style="text-decoration: none;">
                        <div class="resume-card" style="animation-delay: <?php echo ($index * 0.05); ?>s;">
                            <!-- Profile Photo -->
                            <div class="profile-photo">
                                <?php 
                                    // Simple: Show profile_pic if exists, else show profile_no.jpg
                                    if (!empty($resume['profile_pic'])) {
                                        $imagePath = 'uploads/profiles/' . $resume['profile_pic'];
                                    } else {
                                        $imagePath = 'uploads/profiles/profile_no.jpg';
                                    }
                                ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                    alt="<?php echo htmlspecialchars($resume['full_name']); ?>" 
                                    class="profile-picture">

                            </div>


                            <!-- Name -->
                            <div class="resume-name">
                                <?php 
                                $name = htmlspecialchars($resume['full_name']);
                                echo $name;
                                ?>
                            </div>


                            <!-- Field (using location as field for now) -->
                            <div class="resume-field">
                                <?php 
                                $location = htmlspecialchars($resume['location'] ?? 'Not specified');
                                echo $location;
                                ?>
                            </div>
                            <div class="resume-field">
                                <?php 
                                $role = htmlspecialchars($resume['role'] ?? 'Not specified');
                                echo $role;
                                ?>
                            </div>

                            <!-- Email -->
                            <div class="resume-email">
                                <?php echo htmlspecialchars($resume['email']); ?>
                            </div>


                            <!-- View Resume Button -->
                            <button class="view-resume-btn">
                                View Resume
                            </button>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results" style="grid-column: 1 / -1;">
                    <i class="fas fa-inbox"></i>
                    <h3>No resumes found</h3>
                    <p>Try searching with different keywords or check back later</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>