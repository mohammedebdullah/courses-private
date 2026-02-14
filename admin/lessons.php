<?php
/**
 * Admin - Lessons Management
 */

require_once __DIR__ . '/includes/admin_auth.php';

$pageTitle = 'Lessons Management';
$db = getDB();

$courseId = intval($_GET['course'] ?? 0);
$selectedCourse = null;

// Get all courses for filter
$stmt = $db->query("SELECT id, title FROM courses ORDER BY title");
$allCourses = $stmt->fetchAll();

if ($courseId) {
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $selectedCourse = $stmt->fetch();
}

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'داخوازیا تە نە یا دروستە.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_order') {
            header('Content-Type: application/json');
            $order = json_decode($_POST['order'] ?? '[]', true);
            if (is_array($order)) {
                foreach ($order as $position => $id) {
                    $stmt = $db->prepare("UPDATE lessons SET sort_order = ? WHERE id = ?");
                    $stmt->execute([$position + 1, intval($id)]);
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
            exit;
        }
        
        if ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                // Delete associated audio files
                $stmt = $db->prepare("SELECT * FROM audio_files WHERE lesson_id = ?");
                $stmt->execute([$id]);
                $audioFiles = $stmt->fetchAll();
                
                foreach ($audioFiles as $audio) {
                    AudioStream::delete($audio['id']);
                }
                
                $stmt = $db->prepare("DELETE FROM lessons WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'وانە ب سەرکەفتیانە هاتە ژێبرن.';
                Security::logActivity('lesson_deleted', "Lesson ID: $id deleted", null, $currentAdmin['id']);
            }
        }
    }
}

// Get lessons
$sql = "
    SELECT l.*, c.title as course_title,
           af.id as audio_id, af.original_filename, af.duration, af.file_size
    FROM lessons l
    JOIN courses c ON l.course_id = c.id
    LEFT JOIN audio_files af ON l.id = af.lesson_id
";

$params = [];
if ($courseId) {
    $sql .= " WHERE l.course_id = ?";
    $params[] = $courseId;
}

$sql .= " ORDER BY l.course_id, l.sort_order ASC, l.id ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$lessons = $stmt->fetchAll();

// Add availability info to each lesson
$lessons = LessonSchedule::addAvailabilityInfo($lessons);

include __DIR__ . '/includes/header_top.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

		<div class="page-wrapper">
			<div class="content">
				<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
					<div class="mb-3">
						<h1 class="mb-1"><?= $selectedCourse ? htmlspecialchars($selectedCourse['title']) . ' - وانە' : 'هەمی وانە' ?></h1>
						<div class="d-flex align-items-center gap-2">
							<select class="form-control form-select" style="width: 250px;" onchange="window.location.href='lessons.php' + (this.value ? '?course=' + this.value : '')">
								<option value="">هەمی کۆرس</option>
								<?php foreach ($allCourses as $c): ?>
								<option value="<?= $c['id'] ?>" <?= $courseId == $c['id'] ? 'selected' : '' ?>>
									<?= htmlspecialchars($c['title']) ?>
								</option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<a href="lesson-form.php<?= $courseId ? '?course=' . $courseId : '' ?>" class="btn btn-primary d-inline-flex align-items-center">
						<i class="ti ti-plus me-1"></i>
						زێدەکرنا وانەکێ
					</a>
				</div>

				<?php if ($message): ?>
				<div class="alert alert-success alert-dismissible fade show" role="alert" style="display: none;">
					<i class="ti ti-check-circle me-2"></i><?= htmlspecialchars($message) ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>
				<?php endif; ?>

				<?php if ($error): ?>
				<div class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;">
					<i class="ti ti-alert-circle me-2"></i><?= htmlspecialchars($error) ?>
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>
				<?php endif; ?>

				<div class="card">
					<div class="card-header border-bottom-0">
						<h5 class="card-title"><?= $selectedCourse ? htmlspecialchars($selectedCourse['title']) : 'هەمی وانە' ?></h5>
					</div>
					<div class="card-body p-0">
						<?php if (empty($lessons)): ?>
						<p class="text-center text-muted p-5">هیچ وانە نینن. ئێکەم وانەیا خۆ چێبکە!</p>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-borderless table-hover mb-0">
								<thead class="thead-light">
									<tr>
										<?php if ($courseId): ?><th style="width: 40px;"><i class="ti ti-grip-vertical"></i></th><?php endif; ?>
										<th>#</th>
										<th>ناڤنیشان</th>
										<th>کۆرس</th>
										<th>پەڕگێ دەنگی</th>
										<th>ماوە</th>
										<th>خشتە</th>
										<th>ڕەوش</th>
										<th>کریار</th>
									</tr>
								</thead>
								<tbody<?= $courseId ? ' id="sortableLessons"' : '' ?>>
									<?php foreach ($lessons as $index => $lesson): ?>
									<tr<?= $courseId ? ' data-id="' . $lesson['id'] . '"' : '' ?>>
										<?php if ($courseId): ?><td class="drag-handle" style="cursor: grab;"><i class="ti ti-grip-vertical text-muted"></i></td><?php endif; ?>
										<td><?= $lesson['sort_order'] ?: ($index + 1) ?></td>
										<td><h6 class="fw-medium mb-0"><?= htmlspecialchars($lesson['title']) ?></h6></td>
										<td>
											<a href="lessons.php?course=<?= $lesson['course_id'] ?>" class="text-primary">
												<?= htmlspecialchars($lesson['course_title']) ?>
											</a>
										</td>
										<td>
											<?php if ($lesson['audio_id']): ?>
											<span class="badge bg-success">Barkirî</span>
											<br><small class="text-muted"><?= htmlspecialchars($lesson['original_filename']) ?></small>
											<?php else: ?>
											<span class="badge bg-warning">بێ دەنگ</span>
											<?php endif; ?>
										</td>
										<td><?= $lesson['duration'] ? format_duration($lesson['duration']) : '-' ?></td>
										<td>
											<?php 
											$availability = $lesson['_availability'];
											if ($availability['status'] === 'scheduled'): 
											?>
												<span class="badge bg-info">خشتەکری</span>
												<br><small class="text-muted">دەستپێک: <?= LessonSchedule::formatDateTime($lesson['start_datetime'], 'd/m H:i') ?></small>
											<?php elseif ($availability['status'] === 'expired'): ?>
												<span class="badge bg-danger"> دوماهیک هاتیە</span>
												<br><small class="text-muted">دوماهی: <?= LessonSchedule::formatDateTime($lesson['end_datetime'], 'd/m H:i') ?></small>
											<?php elseif (!empty($lesson['start_datetime']) || !empty($lesson['end_datetime'])): ?>
												<span class="badge bg-success">کارا</span>
												<?php if (!empty($lesson['end_datetime'])): ?>
												<br><small class="text-muted">دوماهی: <?= LessonSchedule::formatDateTime($lesson['end_datetime'], 'd/m H:i') ?></small>
												<?php endif; ?>
											<?php else: ?>
												<span class="badge bg-secondary">بێ خشتە</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($lesson['status'] === 'active'): ?>
											<span class="badge bg-success">کارا</span>
											<?php else: ?>
											<span class="badge bg-secondary">نە کارا</span>
											<?php endif; ?>
										</td>
										<td>
											<div class="d-flex gap-1">
												<a href="lesson-form.php?id=<?= $lesson['id'] ?>" class="btn btn-sm btn-secondary">
													<i class="ti ti-edit"></i>
												</a>
											<form method="POST" style="display: inline;" class="delete-form" data-lesson-id="<?= $lesson['id'] ?>" data-lesson-title="<?= htmlspecialchars($lesson['title']) ?>">
												<?= csrf_field() ?>
												<input type="hidden" name="action" value="delete">
												<input type="hidden" name="id" value="<?= $lesson['id'] ?>">
												<button type="button" class="btn btn-sm btn-danger delete-btn">
														<i class="ti ti-trash"></i>
													</button>
												</form>
											</div>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						<?php endif; ?>
					</div>
				</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	<?php if ($message): ?>
	Swal.fire({
		icon: 'success',
		title: 'سەرکەفتی!',
		text: '<?= addslashes(htmlspecialchars($message)) ?>',
		confirmButtonText: 'باشە',
		timer: 3000
	});
	<?php endif; ?>
	
	<?php if ($error): ?>
	Swal.fire({
		icon: 'error',
		title: 'هەڵە!',
		text: '<?= addslashes(htmlspecialchars($error)) ?>',
		confirmButtonText: 'باشە'
	});
	<?php endif; ?>
	
	// Delete lesson confirmation
	document.querySelectorAll('.delete-btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const form = this.closest('.delete-form');
			const lessonTitle = form.dataset.lessonTitle;
			
			Swal.fire({
				title: 'دڵنیایی؟',
				html: `تە دڤێت وانەیا <strong>"${lessonTitle}"</strong> ژێببەیت؟<br><small class="text-muted">پەڕگێ دەنگی جی دێ ژێببێت!</small>`,
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
				confirmButtonText: 'بەلێ، ژێببە!',
				cancelButtonText: 'لێڤەبوون'
			}).then((result) => {
				if (result.isConfirmed) {
					form.submit();
				}
			});
		});
	});

	// Initialize SortableJS for drag-and-drop reordering (only when viewing a specific course)
	<?php if ($courseId): ?>
	if (document.getElementById('sortableLessons')) {
		const sortable = new Sortable(document.getElementById('sortableLessons'), {
			handle: '.drag-handle',
			animation: 150,
			ghostClass: 'sortable-ghost',
			dragClass: 'sortable-drag',
			onEnd: function(evt) {
				const order = [];
				document.querySelectorAll('#sortableLessons tr').forEach(row => {
					order.push(row.dataset.id);
				});
				
				// Save new order via AJAX
				fetch('lessons.php?course=<?= $courseId ?>', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: 'action=update_order&order=' + encodeURIComponent(JSON.stringify(order)) + '&csrf_token=' + encodeURIComponent(document.querySelector('input[name="csrf_token"]').value)
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						Swal.fire({
							icon: 'success',
							title: 'سەرکەفتی!',
							text: 'ڕێزبەندی وانان هاتە پاشەکەوتکرن.',
							confirmButtonText: 'باشە',
							timer: 2000,
							showConfirmButton: false
						});
						// Update the # column to reflect new order
						document.querySelectorAll('#sortableLessons tr').forEach((row, index) => {
							const firstTd = row.querySelector('td:nth-child(2)');
							if (firstTd) firstTd.textContent = index + 1;
						});
					}
				})
				.catch(error => {
					Swal.fire({
						icon: 'error',
						title: 'هەڵە!',
						text: 'هەڵەیەک ڕوویدا',
						confirmButtonText: 'باشە'
					});
				});
			}
		});
	}
	<?php endif; ?>
});
</script>

<style>
.sortable-ghost {
	opacity: 0.4;
	background-color: #f0f0f0;
}
.sortable-drag {
	opacity: 1;
	background-color: #fff;
	box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
.drag-handle:active {
	cursor: grabbing !important;
}
</style>

<?php 
// Add hidden CSRF token for AJAX requests
if ($courseId && (!isset($_POST['action']) || $_POST['action'] !== 'update_order')) {
	echo '<form style="display:none;">' . csrf_field() . '</form>';
}
include __DIR__ . '/includes/footer.php'; 
?>
