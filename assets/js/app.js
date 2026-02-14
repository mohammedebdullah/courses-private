/**
 * Main Application JavaScript
 * Audio Course Platform
 */

const AudioCourse = {
    currentAudio: null,
    currentLesson: null,
    sessionToken: null,
    
    init: function() {
        this.sessionToken = document.body.dataset.sessionToken || '';
        this.bindEvents();
        this.initAudioPlayer();
    },
    
    bindEvents: function() {
        // Only bind if not already handled by inline scripts (check for mainAudioPlayer)
        if (document.getElementById('mainAudioPlayer')) {
            // Inline audio player is present, skip binding
            return;
        }
        
        // Lesson click events
        document.querySelectorAll('.lesson-item[data-lesson-id]').forEach(item => {
            item.addEventListener('click', (e) => {
                const lessonId = item.dataset.lessonId;
                this.loadLesson(lessonId);
            });
        });
    },
    
    initAudioPlayer: function() {
        const playerContainer = document.getElementById('audioPlayerContainer');
        if (!playerContainer) return;
        
        // Create hidden audio element
        if (!this.currentAudio) {
            this.currentAudio = new Audio();
            this.currentAudio.preload = 'metadata';
            
            // Security: Disable download options
            this.currentAudio.controlsList = 'nodownload noplaybackrate';
            this.currentAudio.disablePictureInPicture = true;
            
            // Event listeners
            this.currentAudio.addEventListener('timeupdate', () => this.updateProgress());
            this.currentAudio.addEventListener('loadedmetadata', () => this.onAudioLoaded());
            this.currentAudio.addEventListener('ended', () => this.onAudioEnded());
            this.currentAudio.addEventListener('error', (e) => this.onAudioError(e));
            this.currentAudio.addEventListener('play', () => this.onPlay());
            this.currentAudio.addEventListener('pause', () => this.onPause());
        }
    },
    
    loadLesson: async function(lessonId) {
        try {
            // Get audio token from server
            const response = await fetch('api/get-audio-token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    lesson_id: lessonId,
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.content
                }),
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                this.showError(data.message || 'نەشییا دەنگی بار بکەت');
                return;
            }
            
            // Update UI
            this.currentLesson = data.lesson;
            this.updateLessonUI(lessonId);
            
            // Load audio with token-based URL
            const audioUrl = `stream.php?token=${encodeURIComponent(data.token)}&t=${Date.now()}`;
            this.currentAudio.src = audioUrl;
            this.currentAudio.load();
            
            // Show player
            document.getElementById('audioPlayerContainer').classList.remove('hidden');
            document.getElementById('playerTitle').textContent = data.lesson.title;
            document.getElementById('playerCourse').textContent = data.course.title;
            
            // Auto-play
            this.play();
            
        } catch (error) {
            console.error('Failed to load lesson:', error);
            this.showError('نەشییا دەنگی بار بکەت. هیڤیە دوبارە هەوڵ بدەڤە.');
        }
    },
    
    play: function() {
        if (this.currentAudio) {
            this.currentAudio.play().catch(e => {
                console.error('Playback failed:', e);
            });
        }
    },
    
    pause: function() {
        if (this.currentAudio) {
            this.currentAudio.pause();
        }
    },
    
    togglePlay: function() {
        if (this.currentAudio.paused) {
            this.play();
        } else {
            this.pause();
        }
    },
    
    seek: function(percent) {
        if (this.currentAudio && this.currentAudio.duration) {
            this.currentAudio.currentTime = (percent / 100) * this.currentAudio.duration;
        }
    },
    
    setVolume: function(volume) {
        if (this.currentAudio) {
            this.currentAudio.volume = Math.max(0, Math.min(1, volume));
        }
    },
    
    skipForward: function(seconds = 10) {
        if (this.currentAudio) {
            this.currentAudio.currentTime = Math.min(
                this.currentAudio.duration,
                this.currentAudio.currentTime + seconds
            );
        }
    },
    
    skipBackward: function(seconds = 10) {
        if (this.currentAudio) {
            this.currentAudio.currentTime = Math.max(0, this.currentAudio.currentTime - seconds);
        }
    },
    
    updateProgress: function() {
        if (!this.currentAudio || !this.currentAudio.duration) return;
        
        const percent = (this.currentAudio.currentTime / this.currentAudio.duration) * 100;
        const progressFill = document.getElementById('progressFill');
        if (progressFill) {
            progressFill.style.width = percent + '%';
        }
        
        // Update time display
        const currentTimeEl = document.getElementById('currentTime');
        const totalTimeEl = document.getElementById('totalTime');
        
        if (currentTimeEl) {
            currentTimeEl.textContent = this.formatTime(this.currentAudio.currentTime);
        }
        if (totalTimeEl) {
            totalTimeEl.textContent = this.formatTime(this.currentAudio.duration);
        }
        
        // Save progress periodically (every 10 seconds)
        if (Math.floor(this.currentAudio.currentTime) % 10 === 0) {
            this.saveProgress();
        }
    },
    
    onAudioLoaded: function() {
        const totalTimeEl = document.getElementById('totalTime');
        if (totalTimeEl && this.currentAudio.duration) {
            totalTimeEl.textContent = this.formatTime(this.currentAudio.duration);
        }
    },
    
    onAudioEnded: function() {
        this.saveProgress(true);
        this.onPause();
        // Optionally auto-play next lesson
    },
    
    onAudioError: function(e) {
        console.error('Audio error:', e);
        const errorCode = this.currentAudio?.error?.code;
        let errorMessage = 'Audio playback error. Please try again.';
        
        switch(errorCode) {
            case 1: // MEDIA_ERR_ABORTED
                errorMessage = 'لێدان هاتە هەلوەشاندن.';
                break;
            case 2: // MEDIA_ERR_NETWORK
                errorMessage = 'خەلەتیا تۆڕێ - هیڤیە گرێدانێ بپشکنە.';
                break;
            case 3: // MEDIA_ERR_DECODE
                errorMessage = 'فایلێ دەنگی نەچالاکە یان زیانمەند بوویە.';
                break;
            case 4: // MEDIA_ERR_SRC_NOT_SUPPORTED
                errorMessage = 'فایلێ دەنگی نەهاتە دیتن یان پشتگیری ناکرێت.';
                break;
        }
        
        this.showError(errorMessage);
    },
    
    onPlay: function() {
        const playBtn = document.getElementById('playBtn');
        if (playBtn) {
            playBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect></svg>';
        }
    },
    
    onPause: function() {
        const playBtn = document.getElementById('playBtn');
        if (playBtn) {
            playBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>';
        }
    },
    
    updateLessonUI: function(activeLessonId) {
        document.querySelectorAll('.lesson-item').forEach(item => {
            item.classList.remove('active');
            if (item.dataset.lessonId == activeLessonId) {
                item.classList.add('active');
            }
        });
    },
    
    saveProgress: async function(completed = false) {
        if (!this.currentLesson || !this.currentAudio) return;
        
        try {
            await fetch('api/save-progress.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    lesson_id: this.currentLesson.id,
                    progress: Math.floor(this.currentAudio.currentTime),
                    completed: completed,
                    csrf_token: document.querySelector('meta[name="csrf-token"]')?.content
                }),
                credentials: 'same-origin'
            });
        } catch (error) {
            console.error('Failed to save progress:', error);
        }
    },
    
    formatTime: function(seconds) {
        if (!seconds || isNaN(seconds)) return '00:00';
        
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        
        if (h > 0) {
            return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        }
        return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    },
    
    showError: function(message) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'toast-notification error';
        toast.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Remove after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    AudioCourse.init();
    
    // Progress bar click handler
    const progressBar = document.getElementById('progressBar');
    if (progressBar) {
        progressBar.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const percent = ((e.clientX - rect.left) / rect.width) * 100;
            AudioCourse.seek(percent);
        });
    }
    
    // Volume slider
    const volumeSlider = document.getElementById('volumeSlider');
    if (volumeSlider) {
        volumeSlider.addEventListener('input', function() {
            AudioCourse.setVolume(this.value / 100);
        });
    }
});
