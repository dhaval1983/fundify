<?php

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function register($data) {
    $errors = [];
    
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($data['password']) || strlen($data['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    if (empty($data['full_name'])) {
        $errors[] = 'Full name is required';
    }
    
    if (!in_array($data['role'], ['entrepreneur', 'investor', 'browser'])) {
        $errors[] = 'Valid role is required';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    $query = "SELECT id FROM users WHERE email = ?";
    $existing = $this->db->fetchOne($query, [$data['email']]);
    if ($existing) {
        return ['success' => false, 'errors' => ['Email already registered']];
    }
    
    try {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $verificationToken = bin2hex(random_bytes(32));
        
        $query = "INSERT INTO users (email, password_hash, full_name, phone, role, email_verification_token, email_verification_expires) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['email'],
            $hashedPassword,
            $data['full_name'],
            $data['phone'],
            $data['role'],
            $verificationToken,
            date('Y-m-d H:i:s', strtotime('+24 hours'))
        ];
        
        // ONLY execute once
        $this->db->execute($query, $params);
        $userId = $this->db->lastInsertId();

        // Send verification email
        require_once __DIR__ . '/Mailer.php';
        $mailer = new Mailer();
        $emailSent = $mailer->sendVerificationEmail($data['email'], $verificationToken, $data['full_name']);

        if ($emailSent) {
            return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful! Please check your email to verify your account before logging in.'];
        } else {
            error_log("Verification email failed to send for user: " . $data['email']);
            return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful! However, we couldn\'t send the verification email. Please contact support.'];
        }
        
    } catch (Exception $e) {
        error_log("User registration failed: " . $e->getMessage());
        return ['success' => false, 'errors' => ['Registration failed. Please try again.']];
    }
}
    
    public function login($email, $password, $remember = false) {
        $query = "SELECT id, email, password_hash, full_name, role, email_verified, account_status FROM users WHERE email = ? AND account_status = 'active'";
        
        $user = $this->db->fetchOne($query, [$email]);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }
        
        if (!$user['email_verified']) {
            return ['success' => false, 'error' => 'Please verify your email first'];
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        
        $this->db->execute("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
        
        return ['success' => true, 'user' => $user];
    }
    
   public function verifyEmail($token) {
    $query = "SELECT id, email, full_name, role FROM users WHERE email_verification_token = ? AND email_verification_expires > NOW()";
    $user = $this->db->fetchOne($query, [$token]);
    
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid or expired verification token'];
    }
    
    $this->db->execute("UPDATE users SET email_verified = 1, email_verification_token = NULL, email_verification_expires = NULL WHERE id = ?", [$user['id']]);
    
    // ADD THESE 3 LINES:
    require_once __DIR__ . '/Mailer.php';
    $mailer = new Mailer();
    $mailer->sendWelcomeEmail($user['email'], $user['full_name'], $user['role']);
    
    return ['success' => true, 'message' => 'Email verified successfully! Welcome to Fundify - you can now log in.'];
}
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $query = "SELECT * FROM users WHERE id = ? AND account_status = 'active'";
        return $this->db->fetchOne($query, [$_SESSION['user_id']]);
    }
    
    public function getUserById($id) {
        $query = "SELECT * FROM users WHERE id = ? AND account_status = 'active'";
        return $this->db->fetchOne($query, [$id]);
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
}