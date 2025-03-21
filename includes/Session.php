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
            $session_key = $_COOKIE['session_key'];
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
        return $_SESSION['user_id'] ?? null;
    }

    public function logout(): void
    {
        setcookie('session_key', '', time() - 3600, "/");
        session_destroy();
    }
}