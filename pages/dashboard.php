<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/Timer.php';
require_once __DIR__ . '/../includes/layout.php';

date_default_timezone_set('America/Chicago');

$db = new Database();
$user = new User($db->conn);
$session = new Session();
$timer = new Timer($db->conn);

if (!$session->isLoggedIn()) {
    header('Location: /');
    exit;
}

renderHeader('Dashboard');

$userData = $user->getUserById($session->getUserId());
echo "<script src='/scripts/timer.js'></script>";
echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                fetch('/api/set_timezone.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ timezone })
                });
            });
        </script>";

echo "Welcome, " . htmlspecialchars($userData['name']) . "!<br>";
echo "<a href='/logout'>Logout</a></div>";
echo "<div class='container'>";

echo "<div class='create-timer-container'>";
echo "<h2>Create a New Timer</h2>";
echo "<form method='POST'>
        <input type='text' name='name' placeholder='Timer Name' required>
        <input type='number' name='length' placeholder='Length (seconds)' required>
        <label for='sound'>Sound:</label>
        <select name='sound' id='sound' required>";

$sounds = $timer->getAvailableSounds(__DIR__ . '/../public/sounds');
foreach ($sounds as $sound) {
    echo "<option value=\"$sound\">$sound</option>";
}

echo "</select>
        <button type='button' onclick='previewSound()'>Preview Sound</button>
        <button type='submit'>Create Timer</button>
      </form>
      <audio id='audioPreview' controls style='display:none;'></audio>
      <script>
        function previewSound() {
            let sound = document.getElementById('sound').value;
            let audio = document.getElementById('audioPreview');
            audio.src = sound;
            audio.style.display = 'block';
            audio.play();
        }
      </script>
      </div>";

$timers = $timer->getTimersByUserId($session->getUserId());
if (!empty($timers)) {
    echo "<div class='timers-grid'>";
    foreach ($timers as $timerData) {
        $createdAt = new DateTime($timerData['created_at'], new DateTimeZone('America/Chicago'));
        $createdAt->setTimezone(new DateTimeZone($_SESSION['user_timezone'] ?? 'America/New_York'));
        $remainingTime = $timerData['length'] - (time() - $createdAt->getTimestamp());

        if ($timerData['status'] === 'active' && $remainingTime < 0) {
            $timer->updateStatus($timerData['id'], 'completed');
            $timerData['status'] = 'completed';
        }

        echo "<div class='timer-container' data-id='{$timerData['id']}' data-remaining='{$remainingTime}'>
            <button class='delete-btn' onclick='deleteTimer({$timerData['id']})'>X</button>
            <div class='timer-circle' id='timer-{$timerData['id']}'>
                <div class='inner-circle'>
                    <span class='remaining-time'>{$remainingTime}</span>
                    <span class='original-length'>/{$timerData['length']}</span>
                </div>
            </div>
            <div class='timer-title'>{$timerData['name']}</div>
            <button class='pause-resume-btn' onclick='pauseResumeTimer({$timerData['id']})'>Pause/Resume</button>
            <button class='reset-btn' onclick='resetTimer({$timerData['id']})'>Reset</button>
          </div>";
    }
    echo "</div>";
    try {
        echo "<script id='timers-data' type='application/json'>" . json_encode($timers, JSON_THROW_ON_ERROR) . "</script>";
    } catch (JsonException $e) {
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $length = (int)$_POST['length'];
    $sound = $_POST['sound'];
    $user_id = $session->getUserId();

    if ($timer->createTimer($user_id, $name, $length, $sound)) {
        echo "<p>Timer created successfully!</p>";
    } else {
        echo "<p>" . htmlspecialchars($timer->getError()) . "</p>";
    }
}


renderFooter();
?>