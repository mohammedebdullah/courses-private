<?php
/**
 * Courses Page
 */

require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Courses-list';

// Get all active courses - optimized query with specific columns
$db = getDB();
$stmt = $db->prepare("
    SELECT c.id, c.title, c.description, c.thumbnail, c.sort_order, c.created_at,
           COUNT(DISTINCT l.id) as lesson_count,
           COALESCE(SUM(af.duration), 0) as total_duration
    FROM courses c
    LEFT JOIN lessons l ON c.id = l.course_id AND l.status = 'active'
    LEFT JOIN audio_files af ON l.id = af.lesson_id
    WHERE c.status = 'active'
    GROUP BY c.id, c.title, c.description, c.thumbnail, c.sort_order, c.created_at
    ORDER BY c.sort_order ASC, c.created_at DESC
");
$stmt->execute();
$courses = $stmt->fetchAll();

// Cache CSRF token and close session before rendering HTML
$cachedCsrfToken = csrf_token();
session_write_close();

include __DIR__ . '/includes/header.php';
?>

<meta name="csrf-token" content="<?= htmlspecialchars($cachedCsrfToken) ?>">

<!-- Professional Page Header -->
<div class="page-header">
    <div class="container">
        <h1>وانێن بەردەست</h1>
        <p class="page-description">وانە  بۆ دەمەکێ دیارکری بەردەستن، هیڤیدکەم ل دەما دیارکری دا گوهداریا وانان بکە.</p>
        <!-- <p>کۆرسەکێ هەلبژێرە بۆ دەستپێکرنا فێربوونێ</p> -->
    </div>
    <!-- Wave Decoration -->
    <div class="header-wave">
        <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V95.8C57.1,118.92,156.63,69.08,321.39,56.44Z"></path>
        </svg>
    </div>
</div>

<!-- Courses Section -->
<div class="courses-section">
    <div class="container">
        <?php if (empty($courses)): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 18V5l12-2v13"></path>
                <circle cx="6" cy="18" r="3"></circle>
                <circle cx="18" cy="16" r="3"></circle>
            </svg>
            <h3>چ کۆرس بەردەست نینن</h3>
            <p>پاشی سەرەدانێ بکەڤە بۆ ناڤەڕۆکێن نوی.</p>
        </div>
        <?php else: ?>
        <div class="courses-grid">
            <?php foreach ($courses as $course): ?>
            <a href="lessons.php?course=<?= $course['id'] ?>" class="course-card">
                <div class="course-thumbnail">
                    <?php if ($course['thumbnail']): ?>
                    <img src="uploads/thumbnails/<?= htmlspecialchars($course['thumbnail']) ?>" alt="<?= htmlspecialchars($course['title']) ?>">
                    <?php else: ?>
                    <i class="fas fa-microphone" style="font-size: 64px; color: #ffffffe7;"></i>
                    <?php endif; ?>
                </div>
                <div class="course-content">
                    <h3 class="course-title"><?= htmlspecialchars($course['title']) ?></h3>
                    <!-- <p class="course-description"><?= htmlspecialchars($course['description'] ?? '') ?></p> -->
                    <div class="course-meta">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polygon points="10 8 16 12 10 16 10 8"></polygon>
                            </svg>
                            <?= $course['lesson_count'] ?> وانە
                        </span>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?= format_duration($course['total_duration']) ?>
                        </span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
