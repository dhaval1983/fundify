<?php

// =====================================================
// 6. CLASSES/UTILS.PHP - Utility Functions
// =====================================================

class Utils {
    // Mask names for privacy
    public static function maskName($name) {
        if (strlen($name) <= 2) {
            return str_repeat('*', strlen($name));
        }
        
        $words = explode(' ', $name);
        $maskedWords = [];
        
        foreach ($words as $word) {
            if (strlen($word) <= 2) {
                $maskedWords[] = str_repeat('*', strlen($word));
            } else {
                $maskedWords[] = substr($word, 0, 2) . str_repeat('*', strlen($word) - 2);
            }
        }
        
        return implode(' ', $maskedWords);
    }
    
    // Format currency for Indian market
    public static function formatCurrency($amount) {
        return '₹' . number_format($amount, 0, '.', ',');
    }
    
    // Generate secure random token
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // Sanitize input
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    // Check if user has required role
    public static function checkRole($requiredRole) {
        if (!isset($_SESSION['user_role'])) {
            return false;
        }
        
        $roleHierarchy = ['browser' => 1, 'entrepreneur' => 2, 'investor' => 2, 'admin' => 3];
        $userLevel = $roleHierarchy[$_SESSION['user_role']] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    // Redirect with message
    public static function redirect($url, $message = null, $type = 'info') {
        if ($message) {
            $_SESSION['flash_message'] = $message;
            $_SESSION['flash_type'] = $type;
        }
        header("Location: $url");
        exit;
    }
    
    // Get and clear flash message
    public static function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'info';
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            return ['message' => $message, 'type' => $type];
        }
        return null;
    }
}
