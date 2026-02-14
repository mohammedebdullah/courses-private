<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/includes/admin_auth.php';

$pageTitle = 'Admin Dashboard';

$db = getDB();

// Get statistics
$stats = [];

// Total courses
$stmt = $db->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
$stats['courses'] = $stmt->fetch()['count'];

// Total lessons
$stmt = $db->query("SELECT COUNT(*) as count FROM lessons WHERE status = 'active'");
$stats['lessons'] = $stmt->fetch()['count'];

// Active users
$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$stats['users'] = $stmt->fetch()['count'];

// Access codes stats
$codeStats = AccessCode::getStats();

// Recent activity
$stmt = $db->query("
    SELECT al.*, u.name as user_name, a.username as admin_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN admins a ON al.admin_id = a.id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$recentActivity = $stmt->fetchAll();

include __DIR__ . '/includes/header_top.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

		<div class="page-wrapper">
			<div class="content">
				<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
					<div class="mb-3">
						<h1 class="mb-1">بخێرهاتی ڕێڤەبەر</h1>
						<p class="fw-medium">پانێلا بەرێڤەبرنا کۆرسێن دەنگی</p>
					</div>
				</div>

				<!-- Stats Cards -->
				<div class="row mb-3">
					<div class="col-xl-3 col-sm-6 col-12 d-flex">
						<div class="card bg-primary sale-widget flex-fill">
							<div class="card-body d-flex align-items-center">
								<span class="sale-icon bg-white text-primary">
									<i class="ti ti-book fs-24"></i>
								</span>
								<div class="ms-2 text-white">
									<p class="fw-medium mb-1 text-white">کۆرسێن کارا</p>
									<div>
										<h3 class="text-white"><?php echo number_format($stats['courses']); ?></h3>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-sm-6 col-12 d-flex">
						<div class="card bg-success sale-widget flex-fill">
							<div class="card-body d-flex align-items-center">
								<span class="sale-icon bg-white text-success">
									<i class="ti ti-music fs-24"></i>
								</span>
								<div class="ms-2 text-white">
									<p class="fw-medium mb-1 text-white">هەمی وانە</p>
									<div>
										<h3 class="text-white"><?php echo number_format($stats['lessons']); ?></h3>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-sm-6 col-12 d-flex">
						<div class="card bg-info sale-widget flex-fill">
							<div class="card-body d-flex align-items-center">
								<span class="sale-icon bg-white text-info">
									<i class="ti ti-users fs-24"></i>
								</span>
								<div class="ms-2 text-white">
									<p class="fw-medium mb-1 text-white">بەکارهێنەرێن کارا</p>
									<div>
										<h3 class="text-white"><?php echo number_format($stats['users']); ?></h3>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xl-3 col-sm-6 col-12 d-flex">
						<div class="card bg-warning sale-widget flex-fill">
							<div class="card-body d-flex align-items-center">
								<span class="sale-icon bg-white text-warning">
									<i class="ti ti-key fs-24"></i>
								</span>
								<div class="ms-2 text-white">
									<p class="fw-medium mb-1 text-white">کۆدێن بەردەست</p>
									<div>
										<h3 class="text-white"><?php echo number_format($codeStats['available'] ?? 0); ?></h3>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<!-- /Stats Cards -->

				<div class="row">
					<!-- Access Code Stats -->
					<div class="col-lg-6">
						<div class="card">
							<div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
								<h5 class="card-title">کۆدێن دەستپێگەهشتنێ</h5>
								<a href="access-codes.php" class="btn btn-primary btn-sm">بەرێڤەبرن</a>
							</div>
							<div class="card-body p-0">
								<div class="table-responsive">
									<table class="table table-borderless table-hover mb-0">
										<tbody>
											<tr>
												<td>کۆدێن بەردەست</td>
												<td><span class="badge bg-success"><?= $codeStats['available'] ?? 0 ?></span></td>
											</tr>
											<tr>
												<td>کۆدێن بکارئینای</td>
												<td><span class="badge bg-secondary"><?= $codeStats['used'] ?? 0 ?></span></td>
											</tr>
											<tr>
												<td>کۆدێن بەسەرچووی</td>
												<td><span class="badge bg-warning"><?= $codeStats['expired'] ?? 0 ?></span></td>
											</tr>
											<tr>
												<td>کۆدێن هەلوەشاندی</td>
												<td><span class="badge bg-danger"><?= $codeStats['revoked'] ?? 0 ?></span></td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>

					<!-- Recent Activity -->
					<div class="col-lg-6">
						<div class="card">
							<div class="card-header border-bottom-0 d-flex justify-content-between align-items-center">
								<h5 class="card-title">چالاکیێن ڤێ دوماهیێ</h5>
								<a href="activity-logs.php" class="btn btn-secondary btn-sm">هەمیان ببینە</a>
							</div>
							<div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
								<?php if (empty($recentActivity)): ?>
								<p class="text-muted text-center p-3">هیچ چالاکیەکا نوی نینە</p>
								<?php else: ?>
								<div class="table-responsive">
									<table class="table table-borderless table-hover mb-0">
										<thead class="thead-light">
											<tr>
												<th>کریار</th>
												<th>بەکارهێنەر</th>
												<th>دەم</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($recentActivity as $activity): ?>
											<tr>
												<td><?= htmlspecialchars($activity['action']) ?></td>
												<td><?= htmlspecialchars($activity['user_name'] ?? $activity['admin_name'] ?? 'System') ?></td>
												<td><?= date('M j, H:i', strtotime($activity['created_at'])) ?></td>
											</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>


<?php include __DIR__ . '/includes/footer.php'; ?>
