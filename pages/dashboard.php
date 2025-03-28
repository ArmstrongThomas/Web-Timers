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

echo "<div class='create-timer-container'>
    <h2>Create a New Timer</h2>
    <form method='POST' class='timer-form'>
        " . CSRF::tokenField() . "
        <div class='form-group'>
            <label for='timer-name'>Timer Name</label>
            <input type='text' id='timer-name' name='name' placeholder='My Timer' required>
        </div>

        <div class='form-group time-inputs'>
            <label>Timer Duration</label>
            <div class='duration-container'>
                <div class='duration-input'>
                    <input type='number' id='hours' min='0' max='43800' value='0'>
                    <span>hours</span>
                </div>
                <div class='duration-input'>
                    <input type='number' id='minutes' min='0' max='59' value='5'>
                    <span>min</span>
                </div>
                <div class='duration-input'>
                    <input type='number' id='seconds' min='0' max='59' value='0'>
                    <span>sec</span>
                </div>
                <input type='hidden' name='length' id='total-seconds' value='300'>
            </div>
        </div>

        <div class='form-group'>
            <label for='sound'>Completion Sound</label>
            <div class='sound-selection'>";

echo "<select name='sound' id='sound' required class='sound-dropdown'>";
$sounds = $sound->getAvailableSounds();
foreach ($sounds as $soundFile) {
    echo "<option value=\"{$soundFile['path']}\">{$soundFile['name']}</option>";
}
echo "</select>
                <button type='button' class='preview-sound-btn' onclick='previewSound()'>
                    <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' viewBox='0 0 16 16'>
                        <path d='M11.536 14.01A8.473 8.473 0 0 0 14.026 8a8.473 8.473 0 0 0-2.49-6.01l-.708.707A7.476 7.476 0 0 1 13.025 8c0 2.071-.84 3.946-2.197 5.303l.708.707z'/>
                        <path d='M10.121 12.596A6.48 6.48 0 0 0 12.025 8a6.48 6.48 0 0 0-1.904-4.596l-.707.707A5.483 5.483 0 0 1 11.025 8a5.483 5.483 0 0 1-1.61 3.89l.706.706z'/>
                        <path d='M8.707 11.182A4.486 4.486 0 0 0 10.025 8a4.486 4.486 0 0 0-1.318-3.182L8 5.525A3.489 3.489 0 0 1 9.025 8 3.49 3.49 0 0 1 8 10.475l.707.707z'/>
                        <path fill-rule='evenodd' d='M6.717 3.55A.5.5 0 0 1 7 4v8a.5.5 0 0 1-.812.39L3.825 10.5H1.5A.5.5 0 0 1 1 10V6a.5.5 0 0 1 .5-.5h2.325l2.363-1.89a.5.5 0 0 1 .529-.06z'/>
                    </svg>
                    Play
                </button>
            </div>
            <div class='audio-container'>
                <audio id='audioPreview' controls></audio>
            </div>
        </div>

        <div class='form-preview'>
            <div class='timer-preview'>
                <div class='timer-circle-preview' id='timer-preview'>
                    <div class='inner-circle'>
                        <span class='preview-time'>5:00</span>
                    </div>
                </div>
                <div class='preview-label'>Preview</div>
            </div>
        </div>

        <button type='submit' class='create-timer-btn'>Create Timer</button>
    </form>
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

    // Form time input handling
    const hoursInput = document.getElementById('hours');
    const minutesInput = document.getElementById('minutes');
    const secondsInput = document.getElementById('seconds');
    const totalSecondsInput = document.getElementById('total-seconds');
    const previewTimeSpan = document.querySelector('.preview-time');

    function updateTotalSeconds() {
        const hours = parseInt(hoursInput.value) || 0;
        const minutes = parseInt(minutesInput.value) || 0;
        const seconds = parseInt(secondsInput.value) || 0;

        const totalSeconds = hours * 3600 + minutes * 60 + seconds;
        totalSecondsInput.value = totalSeconds;

        // Update preview
        let timeDisplay = '';
        if (hours > 0) {
            timeDisplay += hours + ':' + (minutes < 10 ? '0' : '');
        }
        timeDisplay += minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
        previewTimeSpan.textContent = timeDisplay;
    }

    // Initialize with default values
    updateTotalSeconds();

    // Update when inputs change
    hoursInput.addEventListener('input', updateTotalSeconds);
    minutesInput.addEventListener('input', updateTotalSeconds);
    secondsInput.addEventListener('input', updateTotalSeconds);

    // Ensure minutes and seconds stay within 0-59 range
    minutesInput.addEventListener('change', function() {
        if (this.value > 59) this.value = 59;
        if (this.value < 0) this.value = 0;
        updateTotalSeconds();
    });

    secondsInput.addEventListener('change', function() {
        if (this.value > 59) this.value = 59;
        if (this.value < 0) this.value = 0;
        updateTotalSeconds();
    });

    // Preview sound functionality
    window.previewSound = function() {
        let sound = document.getElementById('sound').value;
        let audio = document.getElementById('audioPreview');
        let audioContainer = document.querySelector('.audio-container');

        audio.src = sound;
        audioContainer.style.display = 'block';
        audio.play();
    };
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
    $sound_path = isset($_POST['sound']) ? trim($_POST['sound']) : '';
    if (empty($sound_path) || !preg_match('/^\/sounds\/[a-zA-Z0-9_\-\.]+\.(mp3|wav|ogg)$/i', $sound_path)) {
        echo "<p style='color: red;'>Invalid sound selection.</p>";
        renderFooter();
        exit;
    }

    $user_id = $session->getUserId();

    if ($timer->createTimer($user_id, $name, $length, $sound_path)) {
        echo "<p style='color: green;'>Timer created successfully!</p>";
    } else {
        echo "<p style='color: red;'>" . htmlspecialchars($timer->getError()) . "</p>";
    }
}

renderFooter();
?>
