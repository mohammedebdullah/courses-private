<?php
/**
 * Lessons Page - Audio Player
 */

require_once __DIR__ . '/includes/auth_check.php';

// Get course ID
$courseId = intval($_GET['course'] ?? 0);

if (!$courseId) {
    redirect('courses.php');
}

$db = getDB();

// Get course details
$stmt = $db->prepare("SELECT * FROM courses WHERE id = ? AND status = 'active'");
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    redirect('courses.php');
}

$pageTitle = 'Lessons-List';

// Get lessons with audio files
$stmt = $db->prepare("
    SELECT l.*, 
           af.id as audio_id,
           af.duration,
           af.file_size,
           COALESCE(up.progress_seconds, 0) as user_progress,
           COALESCE(up.completed, 0) as user_completed
    FROM lessons l
    LEFT JOIN audio_files af ON l.id = af.lesson_id
    LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = ?
    WHERE l.course_id = ? AND l.status = 'active'
    ORDER BY l.sort_order ASC, l.id ASC
");
$stmt->execute([Session::getUserId(), $courseId]);
$allLessons = $stmt->fetchAll();

// Filter lessons based on schedule - hide not-yet-started lessons, show expired with message
$lessons = LessonSchedule::filterAvailableLessons($allLessons);

// Get other courses (exclude current)
$stmt = $db->prepare("
    SELECT c.*, COUNT(l.id) as lesson_count
    FROM courses c
    LEFT JOIN lessons l ON c.id = l.course_id AND l.status = 'active'
    WHERE c.status = 'active' AND c.id != ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT 3
");
$stmt->execute([$courseId]);
$otherCourses = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token()) ?>">

<!-- Professional Page Header -->
<div class="page-header">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-nav">
            <div class="breadcrumb-wrapper">
                <a href="courses.php" class="breadcrumb-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                     زڤرین بو لیستا وانان 
                </a>
            </div>
        </nav>
        <h1><?= htmlspecialchars($course['title']) ?></h1>
        <p class="page-description"><?= htmlspecialchars($course['description'] ?? '') ?></p>
        
        <!-- Course Stats -->
        <div class="course-stats-bar">
            <span class="stat-item">
                <i class="fas fa-microphone"></i>
                <?= count($lessons) ?> وانە
            </span>
        </div>
    </div>
    <!-- Wave Decoration -->
    <div class="header-wave">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V95.8C57.1,118.92,156.63,69.08,321.39,56.44Z"></path>
        </svg>
    </div>
</div>

<!-- Course Detail Layout -->
<div class="course-detail-wrapper">
    <div class="course-detail-section">
        <div class="container">
            <?php if (empty($lessons)): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polygon points="10 8 16 12 10 16 10 8"></polygon>
                </svg>
                <h3>چ وانە بەردەست نینن</h3>
                <p>ناڤەڕۆک دهێتە ئامادەکرن. پاشی سەرەدانێ بکەڤە.</p>
            </div>
            <?php else: ?>
            <div class="course-detail-grid">
                <!-- Main Content -->
                <div class="course-main-content">
                    <!-- Now Playing Section -->
                    <div class="now-playing-section" id="nowPlayingSection">
                        <div class="now-playing-header">
                            <div class="playing-indicator"></div>
                            <span>نوکە دهێتە خواندن</span>
                        </div>
                        <h3 class="now-playing-title" id="nowPlayingTitle">
                            <?= !empty($lessons) ? htmlspecialchars($lessons[0]['title']) : 'وانەیەکێ هەلبژێرە' ?>
                        </h3>
                        <div class="audio-player-wrapper">
                            <audio id="mainAudioPlayer" controls style="width: 100%; height: 50px;">
                                وێبگەڕێ تە دەنگی پشتگیری ناکەت
                            </audio>
                        </div>
                    </div>

                    <!-- Lessons List -->
                    <div class="lessons-section">
                        <div class="lessons-header">
                            <h3>
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="8" y1="6" x2="21" y2="6"></line>
                                    <line x1="8" y1="12" x2="21" y2="12"></line>
                                    <line x1="8" y1="18" x2="21" y2="18"></line>
                                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                                </svg>
                                لیستا وانەیا
                            </h3>
                            <span class="lessons-count-badge"><?= count($lessons) ?> وانە</span>
                        </div>
                        <div class="lessons-list">
                            <?php foreach ($lessons as $index => $lesson): 
                                $availability = $lesson['_availability'];
                                $isExpired = $availability['status'] === 'expired';
                                $isActive = $availability['status'] === 'active';
                            ?>
                            <div class="lesson-item <?= $lesson['user_completed'] ? 'completed' : '' ?> <?= $index === 0 && $isActive ? 'playing' : '' ?> <?= empty($lesson['audio_id']) || $isExpired ? 'no-audio' : '' ?> <?= $isExpired ? 'lesson-expired' : '' ?>" 
                                 data-lesson-id="<?= $lesson['id'] ?>"
                                 data-audio-id="<?= $isActive ? ($lesson['audio_id'] ?? '') : '' ?>"
                                 data-title="<?= htmlspecialchars($lesson['title']) ?>"
                                 data-expired="<?= $isExpired ? '1' : '0' ?>">
                                <span class="lesson-number"><?= $index + 1 ?></span>
                                <div class="lesson-icon">
                                    <?php if ($isExpired): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                    <?php elseif ($lesson['user_completed']): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <?php else: ?>
                                                       <i class="fas fa-microphone" style="font-size: 24px; color: #ffffffe7;"></i>

                                    <?php endif; ?>
                                </div>
                                <div class="lesson-info">
                                    <h4 class="lesson-title"><?= htmlspecialchars($lesson['title']) ?></h4>
                                    <div class="lesson-meta">
                                        <?php if ($isExpired): ?>
                                        <span style="color: #dc3545;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                                <line x1="9" y1="9" x2="15" y2="15"></line>
                                            </svg>
                                            <?= $availability['message'] ?>
                                        </span>
                                        <?php else: ?>
                                        <span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            <?= format_duration($lesson['duration'] ?? 0) ?>
                                        </span>
                                        <?php if ($lesson['user_progress'] > 0 && !$lesson['user_completed']): ?>
                                        <span style="color: var(--primary-color);">
                                            پێشڤەچوون: <?= format_duration($lesson['user_progress']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="lesson-play-btn">
                                    <?php if ($isExpired): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                    </svg>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar with Other Courses -->
                <?php if (!empty($otherCourses)): ?>
                <div class="course-sidebar">
                    <div class="sidebar-card">
                        <h4 class="sidebar-title">
                            وانێن دی
                        </h4>
                        <div class="sidebar-courses-list">
                            <?php foreach ($otherCourses as $otherCourse): ?>
                            <a href="lessons.php?course=<?= $otherCourse['id'] ?>" class="sidebar-course-item">
                                <div class="sidebar-course-icon">
                                                        <i class="fas fa-microphone" style="font-size: 24px; color: #ffffffe7;"></i>

                                </div>
                                <div class="sidebar-course-info">
                                    <h5><?= htmlspecialchars($otherCourse['title']) ?></h5>
                                    <span><?= $otherCourse['lesson_count'] ?> وانە</span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Inline Audio Player Logic
let currentLessonId = <?= !empty($lessons) ? $lessons[0]['id'] : 0 ?>;
const courseId = <?= $courseId ?>;
const csrfToken = '<?= csrf_token() ?>';

const audioPlayer = document.getElementById('mainAudioPlayer');
const nowPlayingTitle = document.getElementById('nowPlayingTitle');

// Audio error handler
audioPlayer.addEventListener('error', function(e) {
    console.error('Audio error:', e, audioPlayer.error);
    showToast('خەلەتی د لێدانا دەنگی دا', 'error');
});

// Initialize first lesson
document.addEventListener('DOMContentLoaded', function() {
    const firstLesson = document.querySelector('.lesson-item:not(.no-audio):not(.lesson-expired)');
    if (firstLesson && firstLesson.dataset.lessonId) {
        firstLesson.classList.add('playing');
        loadLesson(firstLesson.dataset.lessonId, firstLesson.dataset.title);
    }
    
    // Click handlers for lesson items
    document.querySelectorAll('.lesson-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            
            // Skip lessons without audio
            if (this.classList.contains('no-audio')) {
                return;
            }
            
            // Skip expired lessons
            if (this.dataset.expired === '1' || this.classList.contains('lesson-expired')) {
                showToast('دەمێ ڤێ وانێ ب سەرڤە چوویە', 'error');
                return;
            }
            
            const lessonId = this.dataset.lessonId;
            const title = this.dataset.title;
            
            // Update active state
            document.querySelectorAll('.lesson-item').forEach(el => el.classList.remove('playing'));
            this.classList.add('playing');
            
            loadLesson(lessonId, title);
        });
    });
    
    // Save progress periodically
    audioPlayer.addEventListener('timeupdate', function() {
        if (currentLessonId && this.currentTime > 0) {
            saveProgress(this.currentTime, this.duration);
        }
    });
});

function loadLesson(lessonId, title) {
    currentLessonId = parseInt(lessonId);
    
    // Update title
    nowPlayingTitle.textContent = title;
    
    // Get audio token and load
    fetch('api/get-audio-token.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ lesson_id: parseInt(lessonId), csrf_token: csrfToken })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.token) {
            const streamUrl = 'stream.php?token=' + data.token;
            audioPlayer.src = streamUrl;
            audioPlayer.load();
            audioPlayer.play().catch(e => console.log('Autoplay prevented'));
            
            // Scroll to player on mobile
            if (window.innerWidth <= 768) {
                document.getElementById('nowPlayingSection').scrollIntoView({ behavior: 'smooth' });
            }
        } else {
            showToast(data.message || 'هەڵەیەک ڕوویدا', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('هەڵەی پەیوەندی', 'error');
    });
}

let saveProgressTimeout;
function saveProgress(currentTime, duration) {
    clearTimeout(saveProgressTimeout);
    saveProgressTimeout = setTimeout(() => {
        const completed = duration > 0 && (currentTime / duration) >= 0.9;
        
        fetch('api/save-progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                lesson_id: currentLessonId,
                progress_seconds: Math.floor(currentTime),
                completed: completed
            })
        }).catch(e => console.log('Progress save failed'));
    }, 2000);
}

function showToast(message, type = 'info') {
    const existing = document.querySelector('.toast-notification');
    if (existing) existing.remove();
    
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            ${type === 'error' ? '<circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line>' : '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>'}
        </svg>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
