const activeAnimations = {};
let audioContext;
const soundCache = {};

/**
 * Plays a sound when a timer completes
 * @param {string} soundPath - Path to the sound file
 */
function playTimerSound(soundPath) {
    // Check if user has muted sounds in settings
    const soundSettings = JSON.parse(localStorage.getItem('soundSettings') || '{"enabled":true,"volume":0.7}');
    if (!soundSettings.enabled) return;
    
    try {
        // Create audio context lazily (must be created after user interaction)
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        
        // Use cached sound if available, otherwise load it
        if (soundCache[soundPath]) {
            playSound(soundCache[soundPath], soundSettings.volume);
        } else {
            fetch(soundPath)
                .then(response => response.arrayBuffer())
                .then(arrayBuffer => audioContext.decodeAudioData(arrayBuffer))
                .then(audioBuffer => {
                    soundCache[soundPath] = audioBuffer;
                    playSound(audioBuffer, soundSettings.volume);
                })
                .catch(error => {
                    console.error('Error playing timer sound:', error);
                    // Fallback to HTML5 Audio
                    const audio = new Audio(soundPath);
                    audio.volume = soundSettings.volume;
                    audio.play().catch(err => console.error('Fallback audio failed:', err));
                });
        }
    } catch (error) {
        console.error('Error initializing audio:', error);
        // Fallback to HTML5 Audio API if Web Audio API fails
        const audio = new Audio(soundPath);
        audio.volume = soundSettings.volume;
        audio.play().catch(err => console.error('Fallback audio failed:', err));
    }
}

/**
 * Plays a decoded audio buffer with the Web Audio API
 * @param {AudioBuffer} audioBuffer - The decoded audio data
 * @param {number} volume - Volume level between 0 and 1
 */
function playSound(audioBuffer, volume) {
    const source = audioContext.createBufferSource();
    source.buffer = audioBuffer;
    
    // Add volume control
    const gainNode = audioContext.createGain();
    gainNode.gain.value = volume;
    
    source.connect(gainNode);
    gainNode.connect(audioContext.destination);
    source.start(0);
}


function convertUTCToLocal(utcTimestamp) {
    if (!utcTimestamp) return "Unknown";

    // Convert MySQL-style "YYYY-MM-DD HH:MM:SS" to ISO format "YYYY-MM-DDTHH:MM:SSZ"
    const isoTimestamp = utcTimestamp.replace(" ", "T") + "Z";

    const date = new Date(isoTimestamp);
    return date.toLocaleString(undefined, {
        timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone
    });
}



// Calculate remaining time based on the new data structure.
// For active timers, remaining = end_time - current time.
// For paused timers, use the stored remaining_time.
function calculateRemainingTime(timer) {
    const now = Math.floor(Date.now() / 1000);

    if (timer.status === 'paused') {
        return timer.remaining_time;
    } else if (timer.status === 'active') {
        // Append "Z" to force UTC interpretation if not already present.
        const utcTimestamp = timer.end_time.includes("Z") ? timer.end_time : timer.end_time + "Z";
        const endTimeUTC = new Date(utcTimestamp).getTime();
        return Math.max(0, Math.floor(endTimeUTC / 1000) - now);
    } else {
        return 0;
    }
}


// Updated animation function uses the calculated remaining time.
function startTimerAnimation(timerId, duration, remainingTime, isPaused = false) {
    // Cancel any existing animation for this timer.
    if (activeAnimations[timerId]) {
        cancelAnimationFrame(activeAnimations[timerId]);
        delete activeAnimations[timerId];
    }

    const timerCircle = document.getElementById(`timer-${timerId}`);
    const remainingTimeElement = timerCircle.querySelector('.remaining-time');
    const startTime = Date.now();
    const endTime = startTime + remainingTime * 1000;

    function updateTimer() {
        // If paused, update the timer once to show its paused state, then exit.
        if (isPaused) {
            const percentage = remainingTime / duration;
            const angle = 360 * percentage;
            timerCircle.style.background = `conic-gradient(#007bff ${angle}deg, #e9ecef ${angle}deg)`;
            remainingTimeElement.textContent = Math.ceil(remainingTime);
            return;
        }

        const now = Date.now();
        const remaining = Math.max(0, endTime - now);
        const percentage = remaining / (duration * 1000);
        const angle = 360 * percentage;

        timerCircle.style.background = `conic-gradient(#007bff ${angle}deg, #e9ecef ${angle}deg)`;
        remainingTimeElement.textContent = Math.ceil(remaining / 1000);

        if (remaining > 0) {
            activeAnimations[timerId] = requestAnimationFrame(updateTimer);
        } else {
            // Timer has completed - play sound
            const timerContainer = document.querySelector(`.timer-container[data-id='${timerId}']`);
            const soundPath = timerContainer.getAttribute('data-sound');
            const pauseResumeBtn = timerContainer.querySelector('.pause-resume-btn');
            pauseResumeBtn.textContent = 'Completed';
            
            // Update timer status to completed
            fetch(`/api/update_status.php?id=${timerId}&status=completed`, {
                method: 'POST'
            });
            
            // Play the timer completion sound
            playTimerSound(soundPath);
        }
    }

    updateTimer();
}



function createTimer() {
    const formData = new FormData(document.querySelector('form'));
    fetch('/api/create_timer.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addTimerToDOM(data.timer);
            } else {
                alert(data.error);
            }
        });
}

function addTimerToDOM(timer) {
    const timersGrid = document.querySelector('.timers-grid');
    const timerContainer = document.createElement('div');
    timerContainer.classList.add('timer-container');
    timerContainer.setAttribute('data-id', timer.id);
    timerContainer.setAttribute('data-sound', timer.sound);

    const remainingTime = calculateRemainingTime(timer);
    const localizedEndTime = convertUTCToLocal(timer.end_time);

    timerContainer.setAttribute('data-remaining', remainingTime);

    timerContainer.innerHTML = `
        <button class='delete-btn' onclick='deleteTimer(${timer.id})'>X</button>
        <div class='timer-circle' id='timer-${timer.id}'>
            <div class='inner-circle'>
                <span class='remaining-time'>${remainingTime}</span>
                <span class='original-length'>/${timer.length}</span>
            </div>
        </div>
        <div class='timer-title'>${timer.name}</div>
        <div class='timer-end-time'>Ends: ${localizedEndTime}</div>
        <button class='pause-resume-btn' onclick='pauseResumeTimer(${timer.id})'>${timer.status === 'paused' ? 'Resume' : timer.status === 'completed' ? 'Completed' : 'Pause'}</button>
        <button class='reset-btn' onclick='resetTimer(${timer.id})'>Reset</button>
    `;
    timersGrid.appendChild(timerContainer);

    if (timer.status === 'active') {
        startTimerAnimation(timer.id, timer.length, remainingTime, false);
    } else if (timer.status === 'paused') {
        startTimerAnimation(timer.id, timer.length, remainingTime, true);
    }
}




function deleteTimer(timerId) {
    fetch(`/api/delete_timer.php?id=${timerId}`, {
        method: 'POST'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const timerContainer = document.querySelector(`.timer-container[data-id='${timerId}']`);
                if (timerContainer) {
                    timerContainer.remove();
                }
            } else {
                alert(data.error);
            }
        });
}

function resetTimer(timerId) {
    fetch(`/api/reset_timer.php?id=${timerId}`, {
        method: 'POST'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.timer) {
                const timerContainer = document.querySelector(`.timer-container[data-id='${timerId}']`);
                const remainingTimeElement = timerContainer.querySelector('.remaining-time');
                const pauseResumeBtn = timerContainer.querySelector('.pause-resume-btn');

                // Use the API's returned length
                const originalLength = data.timer.length;
                timerContainer.setAttribute('data-remaining', originalLength);
                remainingTimeElement.textContent = originalLength;
                pauseResumeBtn.textContent = 'Pause';

                // Restart the timer animation using the new original length.
                startTimerAnimation(timerId, originalLength, originalLength, false);
            } else {
                alert(data.error);
            }
        });
}



function pauseResumeTimer(timerId) {
    fetch(`/api/pause_resume_timer.php?id=${timerId}`, {
        method: 'POST'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.timer) {
                const timerContainer = document.querySelector(`.timer-container[data-id='${timerId}']`);
                const pauseResumeBtn = timerContainer.querySelector('.pause-resume-btn');
                const updatedTimer = data.timer;
                const remainingTime = calculateRemainingTime(updatedTimer);
                timerContainer.setAttribute('data-remaining', remainingTime);

                if (data.status === 'paused') {
                    pauseResumeBtn.textContent = 'Resume';
                    // For paused timers, we use remainingTime as both the duration and remaining
                    startTimerAnimation(timerId, updatedTimer.length, remainingTime, true);
                } else if (data.status === 'active') {
                    pauseResumeBtn.textContent = 'Pause';
                    // For resumed timers, the timer's length is used for the animation duration
                    startTimerAnimation(timerId, updatedTimer.length, remainingTime, false);
                }
            } else {
                alert(data.error);
            }
        });
}


document.addEventListener('DOMContentLoaded', () => {
    const timers = JSON.parse(document.getElementById('timers-data').textContent);
    timers.forEach(timer => {
        // Directly add each timer to the DOM.
        addTimerToDOM(timer);
    });

    document.querySelector('form').addEventListener('submit', function (event) {
        event.preventDefault();
        createTimer();
    });
});
