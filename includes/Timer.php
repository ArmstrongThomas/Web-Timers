<?php

class Timer
{
    private $conn;
    private $error;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Creates a new timer.
     * The timer is inserted with:
     *  - start_time = NOW()
     *  - end_time = NOW() + INTERVAL length SECOND
     *  - remaining_time initially set equal to the total length.
     *  - status set to 'active'
     */
    public function createTimer($user_id, $name, $length, $sound): bool
    {
        // Validate inputs
        if (!is_numeric($user_id) || $user_id <= 0) {
            $this->error = "Invalid user ID.";
            return false;
        }

        // Sanitize and validate name
        $name = trim($name);
        if (empty($name) || strlen($name) > 255) {
            $this->error = "Timer name must be between 1 and 255 characters.";
            return false;
        }

        // Validate sound path
        if (empty($sound) || !preg_match('/^\/sounds\/[a-zA-Z0-9_\-\.]+\.(mp3|wav|ogg)$/i', $sound)) {
            $this->error = "Invalid sound selection.";
            return false;
        }

        // Validate the timer length.
        if (!is_numeric($length) || $length < 1) {
            $this->error = "Timer length must be at least 1 second.";
            return false;
        }
        if ($length > 157680000) { // 5 years
            $this->error = "Timer length cannot exceed 157680000 seconds.";
            return false;
        }

        $stmt = $this->conn->prepare("SELECT COALESCE(MAX(position), 0) + 1 AS next_position FROM timers WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $next_position = $result['next_position'];

        $stmt = $this->conn->prepare("
            INSERT INTO timers (user_id, name, length, remaining_time, start_time, end_time, sound, status, position)
            VALUES (?, ?, ?, ?, NOW(), NOW() + INTERVAL ? SECOND, ?, 'active', ?)
        ");
        $stmt->bind_param("isiiisi", $user_id, $name, $length, $length, $length, $sound, $next_position);
        return $stmt->execute();
    }

    public function updateTimerPosition($timer_id, $new_position, $user_id): bool {
        $stmt = $this->conn->prepare("UPDATE timers SET position = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $new_position, $timer_id, $user_id);
        return $stmt->execute();
    }

    /**
     * Pauses an active timer.
     * Sets paused_at to NOW(), calculates remaining_time as the difference (in seconds) between NOW() and end_time,
     * and sets the status to 'paused'.
     */
    public function pauseTimer($timer_id): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE timers
            SET paused_at = NOW(),
                remaining_time = TIMESTAMPDIFF(SECOND, NOW(), end_time),
                status = 'paused'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $timer_id);
        if ($stmt->execute()) {
            return true;
        }
        $this->error = "Failed to pause timer. Please try again.";
        return false;
    }

    /**
     * Resumes a paused timer.
     * Recalculates start_time and end_time based on the stored remaining_time.
     * Clears paused_at and sets the status back to 'active'.
     */
    public function resumeTimer($timer_id): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE timers
            SET start_time = NOW(),
                end_time = NOW() + INTERVAL remaining_time SECOND,
                paused_at = NULL,
                remaining_time = NULL,
                status = 'active'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $timer_id);
        if ($stmt->execute()) {
            return true;
        }
        $this->error = "Failed to resume timer. Please try again.";
        return false;
    }

    /**
     * Resets a timer.
     * Restarts the timer from scratch: start_time becomes NOW(), end_time is recalculated based on length,
     * remaining_time is reset to length, and status is set to 'active'.
     */
    public function resetTimer($timer_id): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE timers
            SET start_time = NOW(),
                end_time = NOW() + INTERVAL length SECOND,
                paused_at = NULL,
                remaining_time = NULL,
                status = 'active'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $timer_id);
        if ($stmt->execute()) {
            return true;
        }
        $this->error = "Failed to reset timer. Please try again.";
        return false;
    }

    /**
     * Retrieves timers for a specific user.
     * Returns an array of all timers for the user.
     * Each timer is an associative array with the following keys: id, name, sound, length, remaining_time, start_time, end_time, paused_at, status.
     * If no timers are found, an empty array is returned.
     */
     public function getTimersByUserId($user_id) {
         $stmt = $this->conn->prepare("
             SELECT id, name, sound, length, remaining_time, start_time, end_time, paused_at, status, position
             FROM timers
             WHERE user_id = ?
             ORDER BY position ASC
         ");
         $stmt->bind_param("i", $user_id);
         $stmt->execute();
         return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
     }

    /**
     * Retrieves information for a specific timer by its ID.
     * Returns an associative array with the following keys: id, name, sound, length, remaining_time, start_time, end_time, paused_at, status.
     * If the timer is not found, null is returned.
     */
    public function getTimerById($timer_id, $user_id)
    {
        $stmt = $this->conn->prepare("SELECT id, name, sound, length, remaining_time, start_time, end_time, paused_at, status FROM timers WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $timer_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    /**
     * Updates the status of a timer.
     * The status can be one of 'active', 'paused', or 'completed'.
     * If the status is 'completed', the timer is considered finished and no longer active.
     * If the status is 'paused', the timer is considered paused and can be resumed.
     * If the status is 'active', the timer is considered running.
     * Returns true if the status was updated successfully, false otherwise.
     */
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

    /**
     * Deletes a timer.
     * Returns true if the timer was deleted successfully, false otherwise.
     */
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

    /**
     * Checks if a user is the owner of a timer.
     * Returns true if the user is the owner of the timer, false otherwise.
     */
    public function isOwner($timer_id, $user_id): bool
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM timers WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $timer_id, $user_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        return $count > 0;
    }

    /**
     * Returns the last error message.
     */
    public function getError(): string
    {
        return $this->error;
    }

}
