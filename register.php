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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Istok Web', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a0e1a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, #7877c61a 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, #ff77c61a 0%, transparent 50%);
            pointer-events: none;
        }


        .register-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 520px;
            text-align: center;
            background: #0f172a4d;
            -webkit-backdrop-filter: blur(20px);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(30, 41, 59, .3);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            animation: fadeInUp .6s ease-out;
            margin: auto;
        }
        .register-container::-webkit-scrollbar {
            width: 6px;
        }

        .register-container::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 3px;
        }

        .register-container::-webkit-scrollbar-thumb {
            background: rgba(96, 165, 250, 0.3);
            border-radius: 3px;
        }

        .register-container::-webkit-scrollbar-thumb:hover {
            background: rgba(96, 165, 250, 0.5);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .welcome-text {
            color: white;
            margin-bottom: 40px;
        }

        .welcome-text h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff 0%, #e2e8f0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-row {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }

        .form-group {
            flex: 1;
            text-align: left;
        }

        .form-group.full-width {
            width: 100%;
            margin-bottom: 24px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #ffffffe6;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1.25rem;
            background: #0f172a99;
            border: 1.5px solid rgba(71, 85, 105, .4);
            border-radius: 12px;
            color: #fff;
            font-size: .9rem;
            transition: all .3s ease;
            position: relative;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-group input:focus {
            outline: none;
            border-color: #222c3c;
            background: #ffffff14;
            box-shadow: 0 0 0 4px rgba(96, 165, 250, 0.1);
        }

        .register-btn {
            width: 100%;
            padding: .875rem 1rem;
            background: #2563ebe6;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s ease;
            margin-bottom: 1rem;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.4);
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .divider-container {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            gap: 1.25rem;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .divider-text {
            font-size: .8rem;
            color: #94a3b8cc;
            white-space: nowrap;
            padding: 0 .5rem;
        }

        .social-login-container {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .social-login-button {
            width: 48px;
            height: 48px;
            background: #1e293b4d;
            border: 1px solid rgba(71, 85, 105, .3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .2s ease;
            color: #cbd5e1cc;
        }

        .social-login-button:hover {
            background: #ffffff14;
            border-color: rgba(71, 85, 105, .5);
            transform: translateY(-1px);
        }

        .social-icon {
            width: 20px;
            height: 20px;
        }

        .login-link {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .login-link a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 16px 20px;
            margin-bottom: 24px;
            border-radius: 12px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            font-size: 14px;
            font-weight: 500;
            text-align: left;
            backdrop-filter: blur(10px);
        }

        .alert.success {
            background: rgba(34, 197, 94, 0.1);
            border-color: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        .terms {
            margin-top: 24px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.5;
        }

        .terms a {
            color: #60a5fa;
            text-decoration: none;
        }

        .terms a:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        @media (max-width: 480px) {
            .register-container {
                margin: 20px 10px;
                padding: 32px 24px;
            }
            
            .back-button {
                top: 20px;
                left: 20px;
                padding: 10px 12px;
            }

            .welcome-text h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

    <div class="register-container">   
        <div class="welcome-text">
            <h1>Create Your Account</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="firstName">First Name</label>
                    <input type="text" id="firstName" name="firstName" placeholder="Enter first name" required 
                           value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="lastName">Last Name</label>
                    <input type="text" id="lastName" name="lastName" placeholder="Enter last name" required 
                           value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group full-width">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group full-width">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="••••••••" required minlength="6">
                </div>
            </div>
            
            <button type="submit" class="register-btn">
                Create Account
            </button>
        </form>

        <div class="divider-container">
            <div class="divider-line"></div>
            <span class="divider-text">Or Register using</span>
            <div class="divider-line"></div>
        </div>

        <div class="social-login-container">
            <button type="button" class="social-login-button">
                <svg class="social-icon" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"></path>
                </svg>
            </button>
            <button type="button" class="social-login-button">
                <svg class="social-icon" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"></path>
                    <path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"></path>
                    <path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"></path>
                    <path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"></path>
                </svg>
            </button>
            <button type="button" class="social-login-button">
                <svg class="social-icon" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"></path>
                </svg>
            </button>
        </div>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>
    </div>
</body>
</html>