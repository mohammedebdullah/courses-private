<?php
/**
 * Admin - Lesson Form (Add/Edit with Audio Upload)
 */

require_once __DIR__ . '/includes/admin_auth.php';

$db = getDB();
$id = intval($_GET['id'] ?? 0);
$courseId = intval($_GET['course'] ?? 0);
$lesson = null;
$audioFile = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$id]);
    $lesson = $stmt->fetch();
    
    if (!$lesson) {
        redirect('lessons.php');
    }
    
    $courseId = $lesson['course_id'];
    
    // Get audio file
    $stmt = $db->prepare("SELECT * FROM audio_files WHERE lesson_id = ?");
    $stmt->execute([$id]);
    $audioFile = $stmt->fetch();
    
    $pageTitle = 'Edit Lesson';
} else {
    $pageTitle = 'Add New Lesson';
}

// Get all courses
$stmt = $db->query("SELECT id, title FROM courses WHERE status != 'draft' ORDER BY title");
$courses = $stmt->fetchAll();

$error = '';
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? 'save';
        
        if ($action === 'delete_audio' && $id) {
            // Delete audio file
            $stmt = $db->prepare("SELECT id FROM audio_files WHERE lesson_id = ?");
            $stmt->execute([$id]);
            $audio = $stmt->fetch();
            
            if ($audio) {
                AudioStream::delete($audio['id']);
                $message = 'Audio file deleted.';
                $audioFile = null;
            }
        } else {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $selectedCourseId = intval($_POST['course_id'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            $sortOrder = intval($_POST['sort_order'] ?? 0);
            $startDatetime = !empty($_POST['start_datetime']) ? $_POST['start_datetime'] : null;
            $endDatetime = !empty($_POST['end_datetime']) ? $_POST['end_datetime'] : null;
            
            if (empty($title)) {
                $error = 'ناڤنیشان پێتڤیە.';
            } elseif (!$selectedCourseId) {
                $error = 'هیڤیە کۆرسەکێ هەلبژێرە.';
            } else {
                // Validate schedule
                $scheduleValidation = LessonSchedule::validateSchedule($startDatetime, $endDatetime);
                if (!$scheduleValidation['valid']) {
                    $error = $scheduleValidation['error'];
                }
                
                if (!$error) {
                    if ($id) {
                        // Update
                        $stmt = $db->prepare("
                            UPDATE lessons SET title = ?, description = ?, course_id = ?, status = ?, sort_order = ?,
                                   start_datetime = ?, end_datetime = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$title, $description, $selectedCourseId, $status, $sortOrder, $startDatetime, $endDatetime, $id]);
                        $lessonId = $id;
                        
                        Security::logActivity('lesson_updated', "Lesson updated: $title", null, $currentAdmin['id']);
                    } else {
                        // Insert
                        $stmt = $db->prepare("
                            INSERT INTO lessons (title, description, course_id, status, sort_order, start_datetime, end_datetime)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$title, $description, $selectedCourseId, $status, $sortOrder, $startDatetime, $endDatetime]);
                        $lessonId = $db->lastInsertId();
                        
                        Security::logActivity('lesson_created', "Lesson created: $title", null, $currentAdmin['id']);
                    }
                    
                    // Handle audio upload
                    if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
                        // Delete existing audio if any
                        if ($audioFile) {
                            AudioStream::delete($audioFile['id']);
                        }
                        
                        $result = AudioStream::upload($_FILES['audio'], $lessonId);
                        
                        if (!$result['success']) {
                            $error = $result['message'];
                        } else {
                            $message = 'وانە ب پەڕگێ دەنگی ڤە هاتە تۆمارکرن.';
                        }
                    }
                    
                    if (!$error) {
                        redirect('lessons.php?course=' . $selectedCourseId);
                    }
                }
            }
        }
    }
}

include __DIR__ . '/includes/header_top.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

		<div class="page-wrapper">
			<div class="content">
				<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
					<div class="mb-3">
						<h1 class="mb-1"><?= $id ? 'دەستکاریکرنا وانێ' : 'زێدەکرنا وانەکێ' ?></h1>
						<p class="fw-medium"><?= $id ? 'گوهۆڕینا زانیاریێن وانێ' : 'چێکرنا وانەکا نوی' ?></p>
					</div>
					<a href="lessons.php<?= $courseId ? '?course=' . $courseId : '' ?>" class="btn btn-secondary d-inline-flex align-items-center">
						<i class="ti ti-arrow-right me-1"></i>
						زڤرین
					</a>
				</div>

				<div class="row">
					<div class="col-lg-8">
						<div class="card">
							<div class="card-header border-bottom-0">
								<h5 class="card-title"><?= $id ? 'دەستکاریا وانێ' : 'زێدەکرنا وانەکێ' ?></h5>
							</div>
							<div class="card-body">
								<?php if ($error): ?>
								<div class="alert alert-danger alert-dismissible fade show" role="alert">
									<i class="ti ti-alert-circle me-2"></i><?= htmlspecialchars($error) ?>
									<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
								</div>
								<?php endif; ?>
								
								<?php if ($message): ?>
								<div class="alert alert-success alert-dismissible fade show" role="alert">
									<i class="ti ti-check-circle me-2"></i><?= htmlspecialchars($message) ?>
									<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
								</div>
								<?php endif; ?>
								
								<form method="POST" action="" enctype="multipart/form-data" id="lessonForm">
									<?= csrf_field() ?>
									
									<div class="mb-3">
										<label class="form-label" for="course_id">کۆرس *</label>
										<select class="form-control form-select" id="course_id" name="course_id" required>
											<option value="">کۆرسەکێ هەلبژێرە</option>
											<?php foreach ($courses as $course): ?>
											<option value="<?= $course['id'] ?>" <?= ($lesson['course_id'] ?? $courseId) == $course['id'] ? 'selected' : '' ?>>
												<?= htmlspecialchars($course['title']) ?>
											</option>
											<?php endforeach; ?>
										</select>
									</div>
									
									<div class="mb-3">
										<label class="form-label" for="title">ناڤنیشان *</label>
										<input type="text" 
											   class="form-control" 
											   id="title" 
											   name="title" 
											   value="<?= htmlspecialchars($lesson['title'] ?? '') ?>"
											   required>
									</div>
									
									<div class="mb-3">
										<label class="form-label" for="description">شڕۆڤە</label>
										<textarea class="form-control" 
												  id="description" 
												  name="description"
												  rows="4"><?= htmlspecialchars($lesson['description'] ?? '') ?></textarea>
									</div>
									
									<div class="row">
										<div class="col-md-6 mb-3">
											<label class="form-label" for="status">ڕەوش</label>
											<select class="form-control form-select" id="status" name="status">
												<option value="active" <?= ($lesson['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>کارا</option>
												<option value="inactive" <?= ($lesson['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>نە کارا</option>
											</select>
										</div>
										
									
									</div>
									
									<!-- Lesson Scheduling -->
									<div class="alert alert-info" role="alert">
										<i class="ti ti-clock me-2"></i>
										<strong>خشتەکرنا وانێ:</strong> دەمێ دەستپێکرن و دوماهیێ دیار بکە دا کۆنترۆلا دەستپێگەهشتنا وانێ بکەی
									</div>
									
									<div class="row">
										<div class="col-md-6 mb-3">
											<label class="form-label" for="start_datetime">
												<i class="ti ti-calendar-event me-1"></i>دەمێ دەستپێکرنێ
											</label>
											<input type="datetime-local" 
												   class="form-control" 
												   id="start_datetime" 
												   name="start_datetime" 
												   value="<?= !empty($lesson['start_datetime']) ? date('Y-m-d\TH:i', strtotime($lesson['start_datetime'])) : '' ?>">
											<small class="text-muted">وانە پشتی ڤی دەمی دێ بەردەست بیت. ڤالا بهێلە بۆ بەردەستبوونا ئێکسەر</small>
										</div>
										
										<div class="col-md-6 mb-3">
											<label class="form-label" for="end_datetime">
												<i class="ti ti-calendar-x me-1"></i>دەمێ دوماهیێ
											</label>
											<input type="datetime-local" 
												   class="form-control" 
												   id="end_datetime" 
												   name="end_datetime" 
												   value="<?= !empty($lesson['end_datetime']) ? date('Y-m-d\TH:i', strtotime($lesson['end_datetime'])) : '' ?>">
											<small class="text-muted">وانە پشتی ڤی دەمی ئێدی دەرناکەڤیت. ڤالا بهێلە دا هەر یا بەردەست بیت</small>
										</div>
									</div>
									
									<!-- Audio Upload -->
									<div class="mb-3">
										<label class="form-label">پەڕگێ دەنگی (Audio)</label>
										
										<?php if ($audioFile): ?>
										<div class="bg-light p-3 rounded mb-3">
											<div class="d-flex justify-content-between align-items-center">
												<div>
													<h6 class="fw-medium mb-1"><?= htmlspecialchars($audioFile['original_filename']) ?></h6>
													<small class="text-muted">
														<i class="ti ti-clock me-1"></i>ماوە: <?= format_duration($audioFile['duration']) ?> | 
														<i class="ti ti-file me-1"></i>قەبارە: <?= format_filesize($audioFile['file_size']) ?>
													</small>
												</div>
												<button type="submit" name="action" value="delete_audio" class="btn btn-sm btn-danger" 
														onclick="return confirm('تۆ پشتڕاستی تە دڤێت ڤی پەڕگێ دەنگی ژێ ببەی؟');">
													<i class="ti ti-trash me-1"></i>ژێبرنا دەنگی
												</button>
											</div>
										</div>
										<?php endif; ?>
										
										<input type="file" 
											   class="form-control" 
											   id="audio" 
											   name="audio" 
											   accept="audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/m4a">
										<small class="text-muted">
											<i class="ti ti-info-circle me-1"></i>
										جۆرێن پەسەندکری: MP3, WAV, OGG, M4A. مەزنترین قەبارە: 200MB.
											<?php if ($audioFile): ?>بارکرنا فایلەکێ نوی شوینا یێ کەڤن دگریت.<?php endif; ?>
										</small>
									</div>
									
									<div class="d-flex gap-2 mt-4">
										<button type="submit" name="action" value="save" class="btn btn-primary" id="submitBtn">
											<i class="ti ti-device-floppy me-1"></i>
											<?= $id ? 'نویکرنا' : 'زێدەکرنا' ?> وانێ
										</button>
										<a href="lessons.php<?= $courseId ? '?course=' . $courseId : '' ?>" class="btn btn-secondary">زڤرین</a>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>

<!-- Upload Loading Overlay -->
<div id="uploadOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; justify-content: center; align-items: center;">
	<div style="text-align: center; color: white;">
		<div class="spinner-border text-light mb-3" role="status" style="width: 3rem; height: 3rem;">
			<span class="visually-hidden">Loading...</span>
		</div>
		<h4 style="color: white; margin-bottom: 10px;">بارکرنا  وانێ...</h4>
		<p style="color: #ccc;">هیڤیە چاڤەرێ بکە، هەتا وانە زێدە ببیت  .</p>
		<div class="progress" style="width: 300px; height: 25px; margin: 20px auto;">
			<div id="uploadProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%">0%</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const form = document.getElementById('lessonForm');
	const audioInput = document.getElementById('audio');
	const submitBtn = document.getElementById('submitBtn');
	const overlay = document.getElementById('uploadOverlay');
	const progressBar = document.getElementById('uploadProgress');
	
	form.addEventListener('submit', function(e) {
		// Check if audio file is selected
		if (audioInput.files && audioInput.files.length > 0) {
			const fileSize = audioInput.files[0].size;
			const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);
			
			// Show overlay for files larger than 5MB
			if (fileSize > 5 * 1024 * 1024) {
				overlay.style.display = 'flex';
				submitBtn.disabled = true;
				
				// Simulate progress (since we can't track real upload progress easily with form submit)
				let progress = 0;
				const interval = setInterval(() => {
					progress += 1;
					if (progress <= 90) {
						progressBar.style.width = progress + '%';
						progressBar.textContent = progress + '%';
					}
				}, 100);
				
				// Clear interval after estimated time
				setTimeout(() => {
					clearInterval(interval);
				}, 10000);
			}
		}
	});
	
	// File size validation before upload
	audioInput.addEventListener('change', function() {
		if (this.files && this.files.length > 0) {
			const fileSize = this.files[0].size;
			const maxSize = 200 * 1024 * 1024; // 200MB
			
			if (fileSize > maxSize) {
				Swal.fire({
					icon: 'error',
					title: 'پەڕگ مەزنە!',
					text: 'قەبارەیا پەڕگی گەلەک مەزنە. مەزنترین قەبارە: 200MB',
					confirmButtonText: 'باشە'
				});
				this.value = '';
			} else {
				const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);
				console.log('File size: ' + fileSizeMB + ' MB');
			}
		}
	});
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
