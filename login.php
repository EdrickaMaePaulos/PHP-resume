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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: #1e293b;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(17, 49, 69, 0.3);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: #131922;
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="40" r="1.5" fill="white" opacity="0.1"/><circle cx="40" cy="80" r="1" fill="white" opacity="0.1"/></svg>');
            opacity: 0.3;
        }

        .login-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .login-header p {
            opacity: 0.8;
            position: relative;
            z-index: 1;
        }

        .login-form {
            padding: 40px 30px;
            background: #fafafa;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #131922;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: #131922;
            box-shadow: 0 0 0 3px rgba(19, 25, 34, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: #131922;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .login-btn:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(19, 25, 34, 0.3);
        }

        .register-link {
            text-align: center;
            color: #64748b;
        }

        .register-link a {
            color: #131922;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            font-size: 14px;
        }

        .alert.success {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }

        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }
            
            .login-header, .login-form {
                padding: 30px 20px;
            }
        }
    </style>
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