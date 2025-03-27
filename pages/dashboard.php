<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/Timer.php';
require_once __DIR__ . '/../includes/Sound.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/CSRF.php';

// Remove hardcoded timezone setting—PHP will use the default from php.ini (UTC).
// date_default_timezone_set('America/Chicago');

$db = new Database();
$user = new User($db->conn);
$session = new Session();
$timer = new Timer($db->conn);
$sound = new Sound(__DIR__ . '/../public/sounds');

if (!$session->isLoggedIn()) {
    header('Location: /');
    exit;
}

renderHeader('Dashboard');

$userData = $user->getUserById($session->getUserId());

// Include the timer.js script.
echo "<script src='/scripts/timer.js'></script>";

echo "Welcome, " . htmlspecialchars($userData['name']) . "!<br>";
echo "<a href='/logout'>Logout</a></div>";
echo "<div class='container'>";

echo "<div class='create-timer-container'>";
echo "<h2>Create a New Timer</h2>";
echo "<form method='POST'>
        " . CSRF::tokenField() . "
        <input type='text' name='name' placeholder='Timer Name' required>
        <input type='number' name='length' placeholder='Length (seconds)' required min='1' max='157680000'>
        <br>
        <label for='sound'>Sound:</label>
        <select name='sound' id='sound' required>";


$sounds = $sound->getAvailableSounds();
foreach ($sounds as $soundFile) {
    echo "<option value=\"{$soundFile['path']}\">{$soundFile['name']} ({$soundFile['type']})</option>";
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

// Fetch the timers for the current user.
$timers = $timer->getTimersByUserId($session->getUserId());
if (!empty($timers)) {
    echo "<div class='timers-grid'>";
    echo "</div>";
    try {
        echo "<script id='timers-data' type='application/json'>" . json_encode($timers, JSON_THROW_ON_ERROR) . "</script>";
    } catch (JsonException $e) {
        // Handle JSON error if necessary.
    }
}

// Add sound settings UI
echo "<div class='sound-settings'>
    <h3>Sound Settings</h3>
    <div>
        <label for='sound-enabled'>Enable Sounds: </label>
        <input type='checkbox' id='sound-enabled' checked>
    </div>
    <div>
        <label for='sound-volume'>Volume: </label>
        <input type='range' id='sound-volume' min='0' max='1' step='0.1' value='0.7'>
    </div>
    <button id='test-sound-btn'>Test Sound</button>
</div>";

// Add sound settings JavaScript
echo "<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize sound settings from localStorage
    const soundSettings = JSON.parse(localStorage.getItem('soundSettings') || '{\"enabled\":true,\"volume\":0.7}');
    document.getElementById('sound-enabled').checked = soundSettings.enabled;
    document.getElementById('sound-volume').value = soundSettings.volume;
    
    // Save sound settings when changed
    document.getElementById('sound-enabled').addEventListener('change', function() {
        const soundSettings = JSON.parse(localStorage.getItem('soundSettings') || '{\"enabled\":true,\"volume\":0.7}');
        soundSettings.enabled = this.checked;
        localStorage.setItem('soundSettings', JSON.stringify(soundSettings));
    });
    
    document.getElementById('sound-volume').addEventListener('input', function() {
        const soundSettings = JSON.parse(localStorage.getItem('soundSettings') || '{\"enabled\":true,\"volume\":0.7}');
        soundSettings.volume = parseFloat(this.value);
        localStorage.setItem('soundSettings', JSON.stringify(soundSettings));
    });
    
    // Test sound button
    document.getElementById('test-sound-btn').addEventListener('click', function() {
        const soundOptions = document.getElementById('sound');
        const selectedSound = soundOptions.value;
        if (selectedSound) {
            playTimerSound(selectedSound);
        }
    });
});
</script>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
        echo "<p style='color: red;'>Invalid form submission. Please try again.</p>";
        renderFooter();
        exit;
    }
    
    // Sanitize and validate inputs
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    if (empty($name) || strlen($name) > 255) {
        echo "<p style='color: red;'>Timer name must be between 1 and 255 characters.</p>";
        renderFooter();
        exit;
    }
    
    // Validate timer length
    $length = isset($_POST['length']) ? filter_var($_POST['length'], FILTER_VALIDATE_INT) : 0;
    if ($length <= 0 || $length > 157680000) {
        echo "<p style='color: red;'>Timer length must be between 1 and 157680000 seconds.</p>";
        renderFooter();
        exit;
    }
    
    // Validate sound path
    $sound = isset($_POST['sound']) ? trim($_POST['sound']) : '';
    if (empty($sound) || !preg_match('/^\/sounds\/[a-zA-Z0-9_\-\.]+\.(mp3|wav|ogg)$/i', $sound)) {
        echo "<p style='color: red;'>Invalid sound selection.</p>";
        renderFooter();
        exit;
    }
    
    $user_id = $session->getUserId();
    
    if ($timer->createTimer($user_id, $name, $length, $sound)) {
        echo "<p style='color: green;'>Timer created successfully!</p>";
    } else {
        echo "<p style='color: red;'>" . htmlspecialchars($timer->getError()) . "</p>";
    }
}

renderFooter();
?>
