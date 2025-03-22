<?php
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Session.php';
require_once __DIR__ . '/../includes/Timer.php';
require_once __DIR__ . '/../includes/layout.php';

// Remove hardcoded timezone setting—PHP will use the default from php.ini (UTC).
// date_default_timezone_set('America/Chicago');

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

// Include the timer.js script.
echo "<script src='/scripts/timer.js'></script>";

echo "Welcome, " . htmlspecialchars($userData['name']) . "!<br>";
echo "<a href='/logout'>Logout</a></div>";
echo "<div class='container'>";

echo "<div class='create-timer-container'>";
echo "<h2>Create a New Timer</h2>";
echo "<form method='POST'>
        <input type='text' name='name' placeholder='Timer Name' required>
        <input type='number' name='length' placeholder='Length (seconds)' required min='1' max='157680000'>
        <br>
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
