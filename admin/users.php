<?php
/**
 * Admin - Users Management
 */

require_once __DIR__ . '/includes/admin_auth.php';

$pageTitle = 'Users Management';
$db = getDB();

$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $action = $_POST['action'] ?? '';
        $userId = intval($_POST['id'] ?? 0);
        
        if ($action === 'block' && $userId) {
            Auth::blockUser($userId);
            $message = 'بەکارهێنەر ب سەرکەفتیانە هاتە بلۆک کرن.';
        }
        
        if ($action === 'unblock' && $userId) {
            Auth::unblockUser($userId);
            $message = 'بلۆکێ بەکارهێنەری رابوو.';
        }
        
        if ($action === 'logout' && $userId) {
            Session::invalidateUserSessions($userId);
            $message = 'دانیشتنێن بەکارهێنەری هاتنە بڕین.';
            Security::logActivity('force_logout', "User forced logout", $userId, $currentAdmin['id']);
        }
    }
}

// Get users with their access code info and session status
$stmt = $db->query("
    SELECT u.*,
           ac.code as access_code,
           ac.valid_until as code_valid_until,
           (SELECT COUNT(*) FROM user_sessions WHERE user_id = u.id AND is_active = 1 AND expires_at > NOW()) as active_sessions
    FROM users u
    LEFT JOIN access_codes ac ON u.access_code_id = ac.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// Get stats
$totalUsers = count($users);
$activeUsers = count(array_filter($users, fn($u) => $u['status'] === 'active'));
$blockedUsers = count(array_filter($users, fn($u) => $u['status'] === 'blocked'));
$onlineUsers = count(array_filter($users, fn($u) => $u['active_sessions'] > 0));

include __DIR__ . '/includes/header_top.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

		<div class="page-wrapper">
			<div class="content">
				<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
					<div class="mb-3">
						<h1 class="mb-1">بەکارهێنەر</h1>
						<p class="fw-medium">بەرێڤەبرنا بەکارهێنەران</p>
					</div>
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

				<!-- Stats -->
				<div class="row mb-4">
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="card">
							<div class="card-body text-center">
								<div class="mb-2"><i class="ti ti-users text-primary" style="font-size: 2rem;"></i></div>
								<h3 class="mb-1"><?= $totalUsers ?></h3>
								<p class="text-muted mb-0">هەمی بەکارهێنەر</p>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="card">
							<div class="card-body text-center">
								<div class="mb-2"><i class="ti ti-check text-success" style="font-size: 2rem;"></i></div>
								<h3 class="mb-1"><?= $activeUsers ?></h3>
								<p class="text-muted mb-0">کارا</p>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="card">
							<div class="card-body text-center">
								<div class="mb-2"><i class="ti ti-point text-info" style="font-size: 2rem;"></i></div>
								<h3 class="mb-1"><?= $onlineUsers ?></h3>
								<p class="text-muted mb-0">نوکە ئۆنلاینن</p>
							</div>
						</div>
					</div>
					<div class="col-md-3 col-sm-6 mb-3">
						<div class="card">
							<div class="card-body text-center">
								<div class="mb-2"><i class="ti ti-ban text-danger" style="font-size: 2rem;"></i></div>
								<h3 class="mb-1"><?= $blockedUsers ?></h3>
								<p class="text-muted mb-0">بلۆککری</p>
							</div>
						</div>
					</div>
				</div>

				<div class="card">
					<div class="card-header border-bottom-0">
						<h5 class="card-title">هەمی بەکارهێنەر</h5>
					</div>
					<div class="card-body p-0">
						<?php if (empty($users)): ?>
						<p class="text-center text-muted p-5">هیچ بەکارهێنەر نینن.</p>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-borderless table-hover mb-0">
								<thead class="thead-light">
									<tr>
										<th>ناڤ</th>
										<th>ئیمەیڵ/ژمارە</th>
										<th>کۆدێ دەستپێگەهشتنێ</th>
										<th>ڕەوش</th>
										<th>سەرهێڵ</th>
										<th>بەشداربوو</th>
										<th>کریار</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($users as $user): ?>
									<tr>
										<td><h6 class="fw-medium mb-0"><?= htmlspecialchars($user['name']) ?></h6></td>
										<td>
											<?php if ($user['email']): ?>
											<?= htmlspecialchars($user['email']) ?>
											<?php endif; ?>
											<?php if ($user['phone']): ?>
											<br><small class="text-muted"><?= htmlspecialchars($user['phone']) ?></small>
											<?php endif; ?>
											<?php if (!$user['email'] && !$user['phone']): ?>
											<span class="text-muted">-</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($user['access_code']): ?>
											<code class="small"><?= htmlspecialchars(substr($user['access_code'], 0, 9)) ?>...</code>
											<br><small class="text-muted">
												بسەر دچیت: <?= date('M j, Y', strtotime($user['code_valid_until'])) ?>
											</small>
											<?php else: ?>
											<span class="text-muted">-</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($user['status'] === 'active'): ?>
											<span class="badge bg-success">کارا</span>
											<?php elseif ($user['status'] === 'blocked'): ?>
											<span class="badge bg-danger">بلۆککری</span>
											<?php else: ?>
											<span class="badge bg-warning">بەسەرچوو</span>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($user['active_sessions'] > 0): ?>
											<span class="badge bg-success">سەرهێڵ</span>
											<?php else: ?>
											<span class="text-muted">نە لسەرخێت</span>
											<?php endif; ?>
										</td>
										<td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
										<td>
											<div class="d-flex gap-1">
												<?php if ($user['status'] === 'active'): ?>
											<form method="POST" style="display: inline;" class="block-form" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>">
												<?= csrf_field() ?>
												<input type="hidden" name="action" value="block">
												<input type="hidden" name="id" value="<?= $user['id'] ?>">
												<button type="button" class="btn btn-sm btn-danger block-btn">
														<i class="ti ti-ban"></i>
													</button>
												</form>
												<?php elseif ($user['status'] === 'blocked'): ?>
												<form method="POST" style="display: inline;">
													<?= csrf_field() ?>
													<input type="hidden" name="action" value="unblock">
													<input type="hidden" name="id" value="<?= $user['id'] ?>">
													<button type="submit" class="btn btn-sm btn-success">
														<i class="ti ti-lock-open"></i>
													</button>
												</form>
												<?php endif; ?>
												
												<?php if ($user['active_sessions'] > 0): ?>
											<form method="POST" style="display: inline;" class="logout-form" data-user-id="<?= $user['id'] ?>" data-user-name="<?= htmlspecialchars($user['name']) ?>">
												<?= csrf_field() ?>
												<input type="hidden" name="action" value="logout">
												<input type="hidden" name="id" value="<?= $user['id'] ?>">
												<button type="button" class="btn btn-sm btn-secondary logout-btn">
														<i class="ti ti-logout"></i>
													</button>
												</form>
												<?php endif; ?>
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
	
	// Block user confirmation
	document.querySelectorAll('.block-btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const form = this.closest('.block-form');
			const userName = form.dataset.userName;
			
			Swal.fire({
				title: 'دڵنیایی؟',
				text: `تە دڤێت بەکارهێنەرێ "${userName}" بلۆک بکەی؟`,
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
				confirmButtonText: 'بەلێ، بلۆک بکە!',
				cancelButtonText: 'لێڤەبوون'
			}).then((result) => {
				if (result.isConfirmed) {
					form.submit();
				}
			});
		});
	});
	
	// Logout user confirmation
	document.querySelectorAll('.logout-btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const form = this.closest('.logout-form');
			const userName = form.dataset.userName;
			
			Swal.fire({
				title: 'دڵنیایی؟',
				text: `تە دڤێت بەکارهێنەرێ "${userName}" دەرێخی؟`,
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
				confirmButtonText: 'بەلێ، دەرێخە!',
				cancelButtonText: 'لێڤەبوون'
			}).then((result) => {
				if (result.isConfirmed) {
					form.submit();
				}
			});
		});
	});
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
