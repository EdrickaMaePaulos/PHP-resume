<?php
require_once 'auth.php';

$message = '';

if ($auth->isLoggedIn()) {
    header('Location: home.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $message = 'Please fill in all fields';
    } else {
        $result = $auth->login($username, $password);
        if ($result['success']) {
            header('Location: home.php');
            exit();
        } else {
            $message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Resume Builder</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Istok+Web:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="styles/login.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-user-circle"></i> Welcome Back</h1>
            <p>Sign in to your resume builder account</p>
        </div>
        
        <div class="login-form">
            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username or Email</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
                
                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>