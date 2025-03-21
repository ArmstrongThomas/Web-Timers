<?php

class Timer
{
    private $conn;
    private $error;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function createTimer($user_id, $name, $length, $sound): bool
    {
        $stmt = $this->conn->prepare("INSERT INTO timers (user_id, name, length, sound, start_time, status) VALUES (?, ?, ?, ?, NOW(), 'active')");
        $stmt->bind_param("isis", $user_id, $name, $length, $sound);
        if ($stmt->execute()) {
            return true;
        }

        $this->error = "Failed to create timer. Please try again.";
        return false;
    }

    public function getTimersByUserId($user_id)
    {
        $stmt = $this->conn->prepare("SELECT id, name, length, start_time, paused_at, status FROM timers WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function updateStatus($timer_id, $status): bool
    {
        $stmt = $this->conn->prepare("UPDATE timers SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $timer_id);
        if ($stmt->execute()) {
            return true;
        }

        $this->error = "Failed to update timer status. Please try again.";
        return false;
    }

    public function deleteTimer($timer_id): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM timers WHERE id = ?");
        $stmt->bind_param("i", $timer_id);
        if ($stmt->execute()) {
            return true;
        }

        $this->error = "Failed to delete timer. Please try again.";
        return false;
    }

    public function isOwner($timer_id, $user_id): bool
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM timers WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $timer_id, $user_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        return $count > 0;
    }

    public function pauseTimer($timer_id): bool
    {
        $stmt = $this->conn->prepare("UPDATE timers SET paused_at = NOW(), remaining_time = TIMESTAMPDIFF(SECOND, start_time, NOW()), status = 'paused' WHERE id = ?");
        $stmt->bind_param("i", $timer_id);
        if ($stmt->execute()) {
            return true;
        }

        $this->error = "Failed to pause timer. Please try again.";
        return false;
    }

    public function resumeTimer($timer_id): bool
    {
        $stmt = $this->conn->prepare("UPDATE timers SET start_time = NOW() - INTERVAL remaining_time SECOND, paused_at = NULL, status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $timer_id);
        if ($stmt->execute()) {
            return true;
        }

        $this->error = "Failed to resume timer. Please try again.";
        return false;
    }

    public function resetTimer($timer_id): bool
    {
        $stmt = $this->conn->prepare("UPDATE timers SET start_time = NOW(), remaining_time = NULL, paused_at = NULL, status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $timer_id);
        if ($stmt->execute()) {
            return true;
        }

        $this->error = "Failed to reset timer. Please try again.";
        return false;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getAvailableSounds($directory): array
    {
        $sounds = [];
        $allowed_extensions = ['mp3', 'wav', 'ogg'];
        foreach (scandir($directory) as $file) {
            $file_extension = pathinfo($file, PATHINFO_EXTENSION);
            if (in_array($file_extension, $allowed_extensions, true)) {
                $sounds[] = '/sounds/' . $file;
            }
        }
        return $sounds;
    }
}

?>