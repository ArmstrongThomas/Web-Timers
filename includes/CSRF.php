<?php

class CSRF {
    /**
     * Generate a CSRF token and store it in the session
     * 
     * @return string The generated token
     */
    public static function generateToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate a submitted CSRF token against the one stored in the session
     * 
     * @param string $token The token to validate
     * @return bool True if the token is valid, false otherwise
     */
    public static function validateToken($token): bool {
        // If there's no token in the session or the submitted token doesn't match, it's invalid
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        
        // Regenerate token after successful validation for better security
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return true;
    }
    
    /**
     * Generate HTML for a hidden CSRF token input field
     * 
     * @return string HTML for the hidden input field
     */
    public static function tokenField(): string {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}
?>