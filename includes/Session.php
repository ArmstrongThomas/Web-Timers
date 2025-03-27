<?php

class Session
{
    public function __construct()
    {
        session_start();
    }

    public function startSession($user_id, $remember_me): void
    {
        $_SESSION['user_id'] = $user_id;
        if ($remember_me) {
            $session_key = bin2hex(random_bytes(16));
            setcookie('session_key', $session_key, time() + (30 * 24 * 60 * 60), "/"); // 30 days
            $this->updateSessionKey($user_id, $session_key);
        } else {
            setcookie('session_key', '', time() - 3600, "/"); // Clear any existing session_key cookie
        }
    }

    public function updateSessionKey($user_id, $session_key): void
    {
        $db = new Database();
        $conn = $db->conn;
        $session_expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $stmt = $conn->prepare("UPDATE users SET session_key = ?, session_expires = ? WHERE id = ?");
        $stmt->bind_param("ssi", $session_key, $session_expires, $user_id);
        $stmt->execute();
    }

    public function validateSession(): bool
    {
        if (isset($_COOKIE['session_key'])) {
            // Sanitize and validate session key
            $session_key = trim($_COOKIE['session_key']);
            
            // Check for valid format (32-character hex string)
            if (empty($session_key) || !preg_match('/^[a-f0-9]{32}$/i', $session_key)) {
                // Invalid session key format, clear it
                setcookie('session_key', '', time() - 3600, "/");
                return false;
            }
            
            $db = new Database();
            $conn = $db->conn;
            $stmt = $conn->prepare("SELECT id FROM users WHERE session_key = ? AND session_expires > NOW()");
            $stmt->bind_param("s", $session_key);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $_SESSION['user_id'] = $user['id'];
                $this->updateSessionKey($user['id'], $session_key);
                return true;
            }
        }
        return false;
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function getUserId()
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        // Validate that user_id is a positive integer
        $user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
        if ($user_id === false || $user_id <= 0) {
            // Invalid user ID in session, clear it
            unset($_SESSION['user_id']);
            return null;
        }
        
        return $user_id;
    }

    public function logout(): void
    {
        setcookie('session_key', '', time() - 3600, "/");
        session_destroy();
    }
}