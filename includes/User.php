<?php
class User {
    private $conn;
    private $error;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function startSession($user_id, $remember_me): void {
        $session = new Session();
        $session->startSession($user_id, $remember_me);
    }

    public function getUserById($user_id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateLoginDetails($id, $last_login_ip, $session_id, $remember_me, $user_agent): void {
        $session_key = null;
        $session_expires = null;

        if ($remember_me) {
            $session_key = bin2hex(random_bytes(16));
            $session_expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        }

        $sql = "UPDATE users SET last_login = NOW(), last_login_ip = ?, session_key = ?, session_expires = ?, user_agent = ?, remember_me = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssi", $last_login_ip, $session_key, $session_expires, $user_agent, $remember_me, $id);
        $stmt->execute();
    }

    public function incrementFailedLogins($id): void {
        $sql = "UPDATE users SET failed_logins = failed_logins + 1, locked = IF(failed_logins >= 2, 1, 0), locked_until = IF(failed_logins >= 2, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL) WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    public function register($name, $email, $password, $creation_ip, $verification_code, $code_expiry): bool {
        // Validate name
        $name = trim($name);
        if (empty($name) || strlen($name) > 255) {
            $this->error = "Name must be between 1 and 255 characters.";
            return false;
        }
        
        // Validate email
        $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $this->error = "Please enter a valid email address.";
            return false;
        }
        
        // Validate password strength (already hashed, this is a second check)
        if (strlen($password) < 60) { // BCRYPT hash is typically 60 chars
            $this->error = "Password hash is invalid.";
            return false;
        }
        
        // Validate IP address
        if (!filter_var($creation_ip, FILTER_VALIDATE_IP)) {
            $creation_ip = "0.0.0.0"; // Default if invalid
        }
        
        // Check if email exists
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $this->error = "Email already exists.";
            return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, creation_ip, code1, code1_timer) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $email, $password, $creation_ip, $verification_code, $code_expiry);
        if ($stmt->execute()) {
            return true;
        }

        $this->error = "Registration failed. Please try again.";
        return false;
    }

    public function verifyUser($code): bool {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE code1 = ? AND code1_timer > NOW()");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $stmt = $this->conn->prepare("UPDATE users SET code1 = NULL, code1_timer = NULL WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            return $stmt->execute();
        }
        return false;
    }

    public function setResetToken($user_id, $reset_token, $token_expiry) {
        $stmt = $this->conn->prepare("UPDATE users SET code1 = ?, code1_timer = ? WHERE id = ?");
        $stmt->bind_param("ssi", $reset_token, $token_expiry, $user_id);
        return $stmt->execute();
    }

    public function resetPassword($token, $new_password): bool {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE code1 = ? AND code1_timer > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $stmt = $this->conn->prepare("UPDATE users SET password = ?, code1 = NULL, code1_timer = NULL WHERE id = ?");
            $stmt->bind_param("si", $new_password, $user['id']);
            return $stmt->execute();
        }
        return false;
    }

    public function sendMagicLoginLink($user_id, $email): bool {
        $magic_token = bin2hex(random_bytes(16));
        $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt = $this->conn->prepare("UPDATE users SET code2 = ?, code2_timer = ? WHERE id = ?");
        $stmt->bind_param("ssi", $magic_token, $token_expiry, $user_id);
        if ($stmt->execute()) {
            $mailer = new Mailer();
            $subject = "Account Locked - Magic Login Link";
            $body = "It looks like you're having trouble logging in! We've locked your account. You can click the link below to unlock it and log in:\n";
            $body .= "https://timers.dotting.page/magic-login?token=$magic_token\n";
            $body .= "If this wasn't you, we apologize for the inconvenience.";
            return $mailer->sendMail($email, $subject, $body);
        }
        return false;
    }

    public function unlockAndLogin($token): bool {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE code2 = ? AND code2_timer > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $stmt = $this->conn->prepare("UPDATE users SET code2 = NULL, code2_timer = NULL, locked = 0, failed_logins = 0 WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            return $stmt->execute();
        }
        return false;
    }

    public function getError() {
        return $this->error;
    }
}
?>