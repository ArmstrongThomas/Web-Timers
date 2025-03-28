function toggleTimerForm() {
    const formContainer = document.querySelector('.create-timer-container');
    const toggleBtn = document.querySelector('.toggle-form-btn');

    if (formContainer.classList.contains('collapsed')) {
        formContainer.classList.remove('collapsed');
        toggleBtn.textContent = 'Hide Timer Creation';
    } else {
        formContainer.classList.add('collapsed');
        toggleBtn.textContent = 'Create Timer';
    }
}
function toggleSoundSettings() {
    const soundSettings = document.querySelector('.sound-settings');
    const toggleBtn = document.querySelector('.toggle-form-btn:nth-of-type(2)');

    if (soundSettings.classList.contains('collapsed')) {
        soundSettings.classList.remove('collapsed');
        toggleBtn.textContent = 'Hide Sound Settings';
    } else {
        soundSettings.classList.add('collapsed');
        toggleBtn.textContent = 'Sound Settings';
    }
}

function formatTime(seconds) {
  if (seconds <= 0) return "00:00";
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;

  return hours > 0
    ? `${hours.toString().padStart(2, "0")}:${minutes.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`
    : `${minutes.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
}

const activeAnimations = {};
// Track audio for each timer separately
const timerSounds = {};
let audioContext;
let blinkingIntervals = {};
const soundCache = {};

function playLoopingSound(soundPath, timerId) {
  const soundSettings = JSON.parse(
    localStorage.getItem("soundSettings") || '{"enabled":true,"volume":0.7}'
  );
  if (!soundSettings.enabled) return;

  // Stop this timer's sound if it's already playing
  if (timerSounds[timerId]) {
    timerSounds[timerId].pause();
    delete timerSounds[timerId];
  }

  const audio = new Audio(soundPath);
  audio.volume = soundSettings.volume;
  audio.loop = true;
  audio.play().catch((e) => console.error("Audio play failed:", e));

  // Store the audio object associated with this timer
  timerSounds[timerId] = audio;
  return audio;
}

function stopLoopingSound(timerId) {
  // If a specific timer ID is provided, only stop that timer's sound
  if (timerId !== undefined && timerSounds[timerId]) {
    timerSounds[timerId].pause();
    delete timerSounds[timerId];
  }
  // Otherwise, stop all sounds (for backward compatibility)
  else if (timerId === undefined) {
    Object.keys(timerSounds).forEach(id => {
      timerSounds[id].pause();
    });
    // Clear all timer sounds
    Object.keys(timerSounds).forEach(id => delete timerSounds[id]);
  }
}

function startBlinking(timerId) {
  const timerContainer = document.querySelector(`.timer-container[data-id='${timerId}']`);
  if (!timerContainer) return;

  const remainingTimeElement = timerContainer.querySelector('.remaining-time');
  if (!remainingTimeElement) return;

  // Clear any existing interval for this timer
  stopBlinking(timerId);

  let visible = true;
  blinkingIntervals[timerId] = setInterval(() => {
    visible = !visible;
    remainingTimeElement.style.visibility = visible ? "visible" : "hidden";
  }, 500);
}

function stopBlinking(timerId) {
  // If timerId is provided, only stop that specific timer's blinking
  if (timerId && blinkingIntervals[timerId]) {
    clearInterval(blinkingIntervals[timerId]);
    delete blinkingIntervals[timerId];

    // Make sure the timer element is visible
    const timerContainer = document.querySelector(`.timer-container[data-id='${timerId}']`);
    if (timerContainer) {
      const remainingTimeElement = timerContainer.querySelector('.remaining-time');
      if (remainingTimeElement) {
        remainingTimeElement.style.visibility = "visible";
      }
    }
  }
  // If no timerId is provided, stop all blinking (legacy support)
  else if (!timerId) {
    Object.keys(blinkingIntervals).forEach(id => {
      clearInterval(blinkingIntervals[id]);

      // Make sure all timer elements are visible
      const timerContainer = document.querySelector(`.timer-container[data-id='${id}']`);
      if (timerContainer) {
        const remainingTimeElement = timerContainer.querySelector('.remaining-time');
        if (remainingTimeElement) {
          remainingTimeElement.style.visibility = "visible";
        }
      }
    });
    blinkingIntervals = {};
  }
}


/**
 * Plays a sound when a timer completes or when testing a sound
 * @param {string} soundPath - Path to the sound file
 * @param {string|null} timerId - Optional timer ID (for associating sounds with specific timers)
 */
function playTimerSound(soundPath, timerId = null) {
  // Check if user has muted sounds in settings
  const soundSettings = JSON.parse(
    localStorage.getItem("soundSettings") || '{"enabled":true,"volume":0.7}',
  );
  if (!soundSettings.enabled) return;

  // If this is for a specific timer and we're already playing a sound for this timer,
  // we should stop that sound first
  if (timerId && timerSounds[timerId]) {
    timerSounds[timerId].pause();
    delete timerSounds[timerId];
  }

  try {
    // Create audio context lazily (must be created after user interaction)
    if (!audioContext) {
      audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }

    // Use cached sound if available, otherwise load it
    if (soundCache[soundPath]) {
      playSound(soundCache[soundPath], soundSettings.volume, timerId);
    } else {
      fetch(soundPath)
        .then((response) => response.arrayBuffer())
        .then((arrayBuffer) => audioContext.decodeAudioData(arrayBuffer))
        .then((audioBuffer) => {
          soundCache[soundPath] = audioBuffer;
          playSound(audioBuffer, soundSettings.volume, timerId);
        })
        .catch((error) => {
          console.error("Error playing timer sound:", error);
          // Fallback to HTML5 Audio
          const audio = new Audio(soundPath);
          audio.volume = soundSettings.volume;

          // If this is for a specific timer, associate this audio with that timer
          if (timerId) {
            timerSounds[timerId] = audio;
          }

          audio
            .play()
            .catch((err) => console.error("Fallback audio failed:", err));
        });
    }
  } catch (error) {
    console.error("Error initializing audio:", error);
    // Fallback to HTML5 Audio API if Web Audio API fails
    const audio = new Audio(soundPath);
    audio.volume = soundSettings.volume;

    // If this is for a specific timer, associate this audio with that timer
    if (timerId) {
      timerSounds[timerId] = audio;
    }

    audio.play().catch((err) => console.error("Fallback audio failed:", err));
  }
}

/**
 * Plays a decoded audio buffer with the Web Audio API
 * @param {AudioBuffer} audioBuffer - The decoded audio data
 * @param {number} volume - Volume level between 0 and 1
 * @param {string|null} timerId - Optional timer ID for tracking
 */
function playSound(audioBuffer, volume, timerId = null) {
  const source = audioContext.createBufferSource();
  source.buffer = audioBuffer;

  // Add volume control
  const gainNode = audioContext.createGain();
  gainNode.gain.value = volume;

  source.connect(gainNode);
  gainNode.connect(audioContext.destination);
  source.start(0);

  // If this is associated with a specific timer, we need to track it
  // This is a simplified implementation since Web Audio API nodes can't be paused directly
  if (timerId) {
    // Create a dummy Audio object just for tracking purposes
    const tracker = new Audio();
    tracker.volume = volume;
    // Use this to track that a sound is playing for this timer
    timerSounds[timerId] = tracker;

    // When the buffer finishes playing, remove from tracking
    source.onended = () => {
      if (timerSounds[timerId] === tracker) {
        delete timerSounds[timerId];
      }
    };
  }
}

function convertUTCToLocal(utcTimestamp) {
  if (!utcTimestamp) return "Unknown";

  // Convert MySQL-style "YYYY-MM-DD HH:MM:SS" to ISO format "YYYY-MM-DDTHH:MM:SSZ"
  const isoTimestamp = utcTimestamp.replace(" ", "T") + "Z";

  const date = new Date(isoTimestamp);
  return date.toLocaleString(undefined, {
    timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone,
  });
}

// Calculate remaining time based on the new data structure.
// For active timers, remaining = end_time - current time.
// For paused timers, use the stored remaining_time.
function calculateRemainingTime(timer) {
  const now = Math.floor(Date.now() / 1000);

  if (timer.status === "paused") {
    return timer.remaining_time;
  } else if (timer.status === "active") {
    // Append "Z" to force UTC interpretation if not already present.
    const utcTimestamp = timer.end_time.includes("Z")
      ? timer.end_time
      : timer.end_time + "Z";
    const endTimeUTC = new Date(utcTimestamp).getTime();
    return Math.max(0, Math.floor(endTimeUTC / 1000) - now);
  } else {
    return 0;
  }
}

// Updated animation function uses the calculated remaining time.
function startTimerAnimation(timerId, duration, remainingTime, isPaused = false) {
  // Cancel any existing animation for this timer
  if (activeAnimations[timerId]) {
    cancelAnimationFrame(activeAnimations[timerId]);
    delete activeAnimations[timerId];
  }

  const timerCircle = document.getElementById(`timer-${timerId}`);
  if (!timerCircle) return;

  const remainingTimeElement = timerCircle.querySelector('.remaining-time');
  const pauseResumeBtn = document.querySelector(
    `.timer-container[data-id='${timerId}'] .pause-resume-btn`
  );

  // Ensure visibility is reset
  remainingTimeElement.style.visibility = "visible";

  const startTime = Date.now();
  const endTime = startTime + remainingTime * 1000;

  function updateTimer() {
    if (isPaused) {
      const percentage = remainingTime / duration;
      const angle = 360 * percentage;
      timerCircle.style.background = `conic-gradient(#007bff ${angle}deg, #e9ecef ${angle}deg)`;
      remainingTimeElement.textContent = formatTime(Math.ceil(remainingTime));
      return;
    }

    const now = Date.now();
    const remaining = Math.max(0, endTime - now);
    const remainingSeconds = Math.ceil(remaining / 1000);
    const percentage = remaining / (duration * 1000);
    const angle = 360 * percentage;

    timerCircle.style.background = `conic-gradient(#007bff ${angle}deg, #e9ecef ${angle}deg)`;
    remainingTimeElement.textContent = formatTime(remainingSeconds);

    if (remaining > 0) {
      activeAnimations[timerId] = requestAnimationFrame(updateTimer);
    } else {
      // Timer completed
      remainingTimeElement.textContent = "00:00";
      startBlinking(timerId);

      const timerContainer = document.querySelector(
        `.timer-container[data-id='${timerId}']`
      );
      const soundPath = timerContainer.getAttribute("data-sound");

      if (pauseResumeBtn) {
        pauseResumeBtn.textContent = "Dismiss";
        pauseResumeBtn.onclick = () => dismissTimer(timerId);
      }

      // Play sound for this timer (each timer can have its own sound)
      playLoopingSound(soundPath, timerId);
    }
  }

  updateTimer();
}

function createTimer() {
  const formData = new FormData(document.querySelector("form"));
  fetch("/api/create_timer.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        addTimerToDOM(data.timer);
      } else {
        alert(data.error);
      }
    });
}

function dismissTimer(timerId) {
  // Get the timer container
  const timerContainer = document.querySelector(`.timer-container[data-id='${timerId}']`);

  // Stop this specific timer's sound
  stopLoopingSound(timerId);

  // Stop blinking for this specific timer
  stopBlinking(timerId);

  fetch(`/api/update_status.php?id=${timerId}&status=completed`, {
    method: "POST",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const pauseResumeBtn = timerContainer.querySelector(".pause-resume-btn");
        pauseResumeBtn.textContent = "Completed";
        pauseResumeBtn.onclick = null;

        // Reset the timer display
        const remainingTimeElement = timerContainer.querySelector('.remaining-time');
        if (remainingTimeElement) {
          remainingTimeElement.style.visibility = "visible";
        }
      }
    });
}

function addTimerToDOM(timer) {
  const timersGrid = document.querySelector(".timers-grid");
  const timerContainer = document.createElement("div");
  timerContainer.classList.add("timer-container");
  timerContainer.setAttribute("data-id", timer.id);
  timerContainer.setAttribute("data-sound", timer.sound);

  const remainingTime = calculateRemainingTime(timer);
  const localizedEndTime = convertUTCToLocal(timer.end_time);

  timerContainer.innerHTML = `
        <button class='delete-btn' onclick='deleteTimer(${timer.id})'>X</button>
        <div class='timer-circle' id='timer-${timer.id}'>
            <div class='inner-circle'>
                <span class='remaining-time'>${formatTime(remainingTime)}</span>
                <span class='original-length'>/${formatTime(timer.length)}</span>
            </div>
        </div>
        <div class='timer-title'>${timer.name}</div>
        <div class='timer-end-time'>Ends: ${localizedEndTime}</div>
        <button class='pause-resume-btn' onclick='${timer.status === "completed" ? "" : `pauseResumeTimer(${timer.id})`}'>${timer.status === "paused" ? "Resume" : timer.status === "completed" ? "Completed" : "Pause"}</button>
        <button class='reset-btn' onclick='resetTimer(${timer.id})'>Reset</button>
    `;
  timersGrid.appendChild(timerContainer);

  if (timer.status === "active") {
    startTimerAnimation(timer.id, timer.length, remainingTime, false);
  } else if (timer.status === "paused") {
    startTimerAnimation(timer.id, timer.length, remainingTime, true);
  }
}

function deleteTimer(timerId) {
  fetch(`/api/delete_timer.php?id=${timerId}`, {
    method: "POST",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const timerContainer = document.querySelector(
          `.timer-container[data-id='${timerId}']`,
        );
        if (timerContainer) {
          timerContainer.remove();
        }
      } else {
        alert(data.error);
      }
    });
}

function resetTimer(timerId) {
  // Stop any sound/blinking for this specific timer
  stopLoopingSound(timerId);
  stopBlinking(timerId);

  fetch(`/api/reset_timer.php?id=${timerId}`, {
    method: "POST",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.timer) {
        const timerContainer = document.querySelector(
          `.timer-container[data-id='${timerId}']`
        );
        if (!timerContainer) return;

        const remainingTimeElement = timerContainer.querySelector('.remaining-time');
        const pauseResumeBtn = timerContainer.querySelector('.pause-resume-btn');

        // Update UI with new timer data
        const originalLength = data.timer.length;
        remainingTimeElement.textContent = formatTime(originalLength);
        remainingTimeElement.style.visibility = "visible";
        pauseResumeBtn.textContent = "Pause";
        pauseResumeBtn.onclick = () => pauseResumeTimer(timerId);

        // Restart animation with the new length
        startTimerAnimation(timerId, originalLength, originalLength, false);
      }
    });
}

function pauseResumeTimer(timerId) {
  fetch(`/api/pause_resume_timer.php?id=${timerId}`, {
    method: "POST",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.timer) {
        const timerContainer = document.querySelector(
          `.timer-container[data-id='${timerId}']`
        );
        if (!timerContainer) return;

        const pauseResumeBtn = timerContainer.querySelector('.pause-resume-btn');
        const updatedTimer = data.timer;
        const remainingTime = calculateRemainingTime(updatedTimer);

        // Stop blinking if this was a completed timer
        stopBlinking(timerId);

        if (data.status === "paused") {
          pauseResumeBtn.textContent = "Resume";
          startTimerAnimation(timerId, updatedTimer.length, remainingTime, true);
        } else {
          pauseResumeBtn.textContent = "Pause";
          startTimerAnimation(timerId, updatedTimer.length, remainingTime, false);
        }
      }
    });
}

document.addEventListener("DOMContentLoaded", () => {
  const timers = JSON.parse(document.getElementById("timers-data").textContent);
  timers.forEach((timer) => {
    // Directly add each timer to the DOM.
    addTimerToDOM(timer);
  });

  document.querySelector("form").addEventListener("submit", function (event) {
    event.preventDefault();
    createTimer();
  });
});
