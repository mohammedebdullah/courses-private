<?php
/**
 * Admin - Courses Management
 */

require_once __DIR__ . '/includes/admin_auth.php';

$pageTitle = 'Courses Management';
$db = getDB();

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'کۆرس ب سەرکەفتیانە هاتە ژێبرن.';
                Security::logActivity('course_deleted', "Course ID: $id deleted", null, $currentAdmin['id']);
            }
        }
        
        if ($action === 'toggle_status') {
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $db->prepare("UPDATE courses SET status = IF(status = 'active', 'inactive', 'active') WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'ڕەوشێ کۆرسی هاتە نویکرن.';
            }
        }
        
        if ($action === 'update_order') {
            $order = json_decode($_POST['order'] ?? '[]', true);
            if (is_array($order)) {
                foreach ($order as $index => $courseId) {
                    $stmt = $db->prepare("UPDATE courses SET sort_order = ? WHERE id = ?");
                    $stmt->execute([$index + 1, intval($courseId)]);
                }
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }
}

// Get all courses
$stmt = $db->query("
    SELECT c.*, 
           COUNT(DISTINCT l.id) as lesson_count,
           a.username as created_by_name
    FROM courses c
    LEFT JOIN lessons l ON c.id = l.course_id
    LEFT JOIN admins a ON c.created_by = a.id
    GROUP BY c.id
    ORDER BY c.sort_order ASC, c.created_at DESC
");
$courses = $stmt->fetchAll();

include __DIR__ . '/includes/header_top.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

		<div class="page-wrapper">
			<div class="content">
				<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
					<div class="mb-3">
						<h1 class="mb-1">کۆرس</h1>
						<p class="fw-medium">بەرێڤەبرنا کۆرسێن دەنگی</p>
					</div>
					<a href="course-form.php" class="btn btn-primary d-inline-flex align-items-center">
						<i class="ti ti-plus me-1"></i>
						کۆرسەکێ زێدەبکە
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
						<h5 class="card-title">هەمی کۆرس</h5>
					</div>
					<div class="card-body p-0">
						<?php if (empty($courses)): ?>
						<p class="text-center text-muted p-5">هیچ کۆرس نینن. ئێکەم کۆرسێ خۆ چێبکە!</p>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-borderless table-hover mb-0" id="coursesTable">
								<thead class="thead-light">
									<tr>											<th style="width: 40px;"><i class="ti ti-grip-vertical"></i></th>										<th>ناڤنیشان</th>
										<th>وانە</th>
										<th>ڕەوش</th>
										<th>demê çêkirinê</th>
										<th>کریار</th>
									</tr>
								</thead>
								<tbody id="sortableCourses">
									<?php foreach ($courses as $course): ?>
								<tr data-id="<?= $course['id'] ?>">
									<td class="drag-handle" style="cursor: grab;"><i class="ti ti-grip-vertical text-muted"></i></td>
										<td>
											<h6 class="fw-medium mb-1"><?= htmlspecialchars($course['title']) ?></h6>
											<?php if ($course['description']): ?>
											<small class="text-muted"><?= htmlspecialchars(substr($course['description'], 0, 50)) ?>...</small>
											<?php endif; ?>
										</td>
										<td><span class="badge bg-info"><?= $course['lesson_count'] ?></span></td>
										<td>
											<?php if ($course['status'] === 'active'): ?>
											<span class="badge bg-success">کارا</span>
											<?php else: ?>
											<span class="badge bg-secondary">نە کارا</span>
											<?php endif; ?>
										</td>
										<td><?= date('Y-m-d', strtotime($course['created_at'])) ?></td>
										<td>
											<div class="d-flex gap-1">
												<a href="course-form.php?id=<?= $course['id'] ?>" class="btn btn-sm btn-secondary">
													<i class="ti ti-edit"></i>
												</a>
												<a href="lessons.php?course=<?= $course['id'] ?>" class="btn btn-sm btn-info">
													<i class="ti ti-playlist"></i>
												</a>
											<form method="POST" style="display: inline;" class="delete-form" data-course-id="<?= $course['id'] ?>" data-course-title="<?= htmlspecialchars($course['title']) ?>">
												<?= csrf_field() ?>
												<input type="hidden" name="action" value="delete">
												<input type="hidden" name="id" value="<?= $course['id'] ?>">
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
	
	// Delete course confirmation
	document.querySelectorAll('.delete-btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const form = this.closest('.delete-form');
			const courseTitle = form.dataset.courseTitle;
			
			Swal.fire({
				title: 'دڵنیایی؟',
				html: `تە دڤێت کۆرسێ <strong>"${courseTitle}"</strong> ژێببەیت؟<br><small class="text-muted">هەمی وانێن جی دێ ژێببن!</small>`,
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

	// Initialize SortableJS for drag-and-drop reordering
	if (document.getElementById('sortableCourses')) {
	const sortable = new Sortable(document.getElementById('sortableCourses'), {
		handle: '.drag-handle',
		animation: 150,
		ghostClass: 'sortable-ghost',
		dragClass: 'sortable-drag',
		onEnd: function(evt) {
			const order = [];
			document.querySelectorAll('#sortableCourses tr').forEach(row => {
				order.push(row.dataset.id);
			});
			
			// Save new order via AJAX
			fetch('courses.php', {
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
						text: 'ڕێزبەندی کۆرسان هاتە پاشەکەوتکرن.',
						confirmButtonText: 'باشە',
						timer: 2000,
						showConfirmButton: false
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
if (!isset($_POST['action']) || $_POST['action'] !== 'update_order') {
	echo '<form style="display:none;">' . csrf_field() . '</form>';
}
include __DIR__ . '/includes/footer.php'; 
?>
