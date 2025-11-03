<?php
// auth.php - Authentication functions
session_start();
require_once 'db.php';

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function register($username, $email, $password, $firstName, $lastName) {
        try {
            // Check if username or email already exists
            $stmt = $this->pdo->prepare("SELECT userid FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }

            // Insert new user
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, firstName, lastName) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password, $firstName, $lastName]);

            return ['success' => true, 'message' => 'Registration successful'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    public function login($username, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT userid, username, email, password, firstName, lastName, isAdmin FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $password === $user['password']) {
                $_SESSION['user_id'] = $user['userid'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['firstName'] = $user['firstname'];
                $_SESSION['lastName'] = $user['lastname'];
                $_SESSION['isAdmin'] = $user['isadmin'];
                
                return ['success' => true, 'message' => 'Login successful'];
            } else {
                return ['success' => false, 'message' => 'Invalid username/email or password'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        return isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'];
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: signin.php');
            exit();
        }
    }

    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: dashboard.php');
            exit();
        }
    }
}

$auth = new Auth($pdo);
?>