<?php
/**
 * Admin - Course Form (Add/Edit)
 */

require_once __DIR__ . '/includes/admin_auth.php';

$db = getDB();
$id = intval($_GET['id'] ?? 0);
$course = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        redirect('courses.php');
    }
    
    $pageTitle = 'Edit Course';
} else {
    $pageTitle = 'Add New Course';
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        
        if (empty($title)) {
            $error = 'ناڤنیشان پێدڤییە.';
        } else {
            if ($id) {
                // Update
                $stmt = $db->prepare("
                    UPDATE courses SET title = ?, description = ?, status = ?, sort_order = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $description, $status, $sortOrder, $id]);
                
                Security::logActivity('course_updated', "Course updated: $title", null, $currentAdmin['id']);
            } else {
                // Insert
                $stmt = $db->prepare("
                    INSERT INTO courses (title, description, status, sort_order, created_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, $status, $sortOrder, $currentAdmin['id']]);
                
                Security::logActivity('course_created', "Course created: $title", null, $currentAdmin['id']);
            }
            
            redirect('courses.php');
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
						<h1 class="mb-1"><?= $id ? 'دەستکاریا کۆرسی' : 'کۆرسەکێ زێدەبکە' ?></h1>
						<p class="fw-medium"><?= $id ? 'غوهۆڕینا زانیاریێن کۆرسی' : 'چێکرنا کۆرسەکێ نوی' ?></p>
					</div>
					<a href="courses.php" class="btn btn-secondary d-inline-flex align-items-center">
						<i class="ti ti-arrow-right me-1"></i>
						زڤرین
					</a>
				</div>

				<div class="row">
					<div class="col-lg-8">
						<div class="card">
							<div class="card-header border-bottom-0">
								<h5 class="card-title"><?= $id ? 'دەستکاری' : 'زێدەکرنا' ?> کۆرسی</h5>
							</div>
							<div class="card-body">
								<?php if ($error): ?>
								<div class="alert alert-danger alert-dismissible fade show" role="alert">
									<i class="ti ti-alert-circle me-2"></i><?= htmlspecialchars($error) ?>
									<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
								</div>
								<?php endif; ?>
								
								<form method="POST" action="">
									<?= csrf_field() ?>
									
									<div class="mb-3">
										<label class="form-label" for="title">ناڤنیشان *</label>
										<input type="text" 
											   class="form-control" 
											   id="title" 
											   name="title" 
											   value="<?= htmlspecialchars($course['title'] ?? '') ?>"
											   required>
									</div>
									
									<div class="mb-3">
										<label class="form-label" for="description">شڕۆڤە</label>
										<textarea class="form-control" 
												  id="description" 
												  name="description"
												  rows="4"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
									</div>
									
									<div class="row">
										<div class="col-md-6 mb-3">
											<label class="form-label" for="status">ڕەوش</label>
											<select class="form-control form-select" id="status" name="status">
												<option value="active" <?= ($course['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>کارا</option>
												<option value="inactive" <?= ($course['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>نە کارا</option>
												<option value="draft" <?= ($course['status'] ?? '') === 'draft' ? 'selected' : '' ?>>ڕەشنڤیس</option>
											</select>
										</div>
										
									 
									</div>
									
									<div class="d-flex gap-2 mt-4">
										<button type="submit" class="btn btn-primary">
											<i class="ti ti-device-floppy me-1"></i>
											<?= $id ? 'نویکرنا' : 'چێکرنا' ?> کۆرسی
										</button>
										<a href="courses.php" class="btn btn-secondary">زڤرین</a>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
