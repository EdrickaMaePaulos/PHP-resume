<?php
require_once 'auth.php';

$message = '';

if ($auth->isLoggedIn()) {
    header('Location: home.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);

    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword) || empty($firstName) || empty($lastName)) {
        $message = 'Please fill in all fields';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address';
    } else {
        $result = $auth->register($username, $email, $password, $firstName, $lastName);
        $message = $result['message'];
        
        if ($result['success']) {
            header('Location: login.php?registered=1');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Resume Builder</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="styles/register.css" rel="stylesheet">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1><i class="fas fa-user-plus"></i> Create Account</h1>
            <p>Join us to build your professional resume</p>
        </div>
        
        <div class="register-form">
            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName"><i class="fas fa-user"></i> First Name</label>
                        <input type="text" id="firstName" name="firstName" required 
                               value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="lastName"><i class="fas fa-user"></i> Last Name</label>
                        <input type="text" id="lastName" name="lastName" required 
                               value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="username"><i class="fas fa-at"></i> Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group full-width">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" id="password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword"><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required minlength="6">
                    </div>
                </div>
                
                <button type="submit" class="register-btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Sign in here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>