function calculateRemainingTime(timer) {
    const now = new Date().getTime();
    const startTime = new Date(timer.start_time).getTime();
    const pausedAt = timer.paused_at ? new Date(timer.paused_at).getTime() : null;

    if (timer.status === 'paused' && pausedAt) {
        return timer.remaining_time;
    } else {
        const elapsed = (now - startTime) / 1000;
        return Math.max(0, timer.length - elapsed);
    }
}

function startTimerAnimation(timerId, duration, remainingTime, isPaused = false) {
    const timerCircle = document.getElementById(`timer-${timerId}`);
    const remainingTimeElement = timerCircle.querySelector('.remaining-time');
    const startTime = Date.now();
    const endTime = startTime + remainingTime * 1000;

    function updateTimer() {
        if (isPaused) return;

        const now = Date.now();
        const remaining = Math.max(0, endTime - now);
        const percentage = remaining / (duration * 1000);
        const angle = 360 * percentage;

        timerCircle.style.background = `conic-gradient(#007bff ${angle}deg, #e9ecef ${angle}deg)`;
        remainingTimeElement.textContent = Math.ceil(remaining / 1000);

        if (remaining > 0) {
            requestAnimationFrame(updateTimer);
        }
    }

    updateTimer();
}

document.addEventListener('DOMContentLoaded', () => {
    const timers = JSON.parse(document.getElementById('timers-data').textContent);
    timers.forEach(timer => {
        const timerContainer = document.querySelector(`.timer-container[data-id='${timer.id}']`);
        if (timerContainer) {
            const remainingTime = parseInt(timerContainer.getAttribute('data-remaining'), 10);
            startTimerAnimation(timer.id, timer.length, remainingTime);
        }
    });

    document.querySelector('form').addEventListener('submit', function (event) {
        event.preventDefault();
        createTimer();
    });
});

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
    const remainingTime = calculateRemainingTime(timer);
    timerContainer.setAttribute('data-remaining', remainingTime);
    timerContainer.innerHTML = `
        <button class='delete-btn' onclick='deleteTimer(${timer.id})'>X</button>
        <div class='timer-circle' id='timer-${timer.id}'>
            <div class='inner-circle'>
                <span class='remaining-time'>${remainingTime}</span>
                <span class='original-length'>${timer.length}</span>
            </div>
        </div>
        <div class='timer-title'>${timer.name}</div>
        <button class='pause-resume-btn' onclick='pauseResumeTimer(${timer.id})'>${timer.status === 'paused' ? 'Resume' : 'Pause'}</button>
        <button class='reset-btn' onclick='resetTimer(${timer.id})'>Reset</button>
    `;
    timersGrid.appendChild(timerContainer);
    startTimerAnimation(timer.id, timer.length, remainingTime, timer.status === 'paused');
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
            if (data.success) {
                const timerContainer = document.querySelector(`.timer-container[data-id='${timerId}']`);
                const remainingTimeElement = timerContainer.querySelector('.remaining-time');
                const originalLength = parseInt(timerContainer.querySelector('.original-length').textContent, 10);
                timerContainer.setAttribute('data-remaining', originalLength);
                remainingTimeElement.textContent = originalLength;
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
            if (data.success) {
                const timerContainer = document.querySelector(`.timer-container[data-id='${timerId}']`);
                const pauseResumeBtn = timerContainer.querySelector('.pause-resume-btn');
                const remainingTime = parseInt(timerContainer.getAttribute('data-remaining'), 10);
                if (data.status === 'paused') {
                    pauseResumeBtn.textContent = 'Resume';
                    startTimerAnimation(timerId, remainingTime, remainingTime, true);
                } else {
                    pauseResumeBtn.textContent = 'Pause';
                    startTimerAnimation(timerId, remainingTime, remainingTime, false);
                }
            } else {
                alert(data.error);
            }
        });
}