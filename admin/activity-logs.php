<?php
/**
 * Admin - Activity Logs
 */

require_once __DIR__ . '/includes/admin_auth.php';

$pageTitle = 'Activity Logs';
$db = getDB();

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Get total count
$stmt = $db->query("SELECT COUNT(*) as count FROM activity_logs");
$totalLogs = $stmt->fetch()['count'];
$totalPages = ceil($totalLogs / $perPage);

// Get logs
$stmt = $db->prepare("
    SELECT al.*, 
           u.name as user_name,
           a.username as admin_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN admins a ON al.admin_id = a.id
    ORDER BY al.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
$logs = $stmt->fetchAll();

include __DIR__ . '/includes/header_top.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

		<div class="page-wrapper">
			<div class="content">
				<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
					<div class="mb-3">
						<h1 class="mb-1">تۆمارێن چالاکیان</h1>
						<p class="fw-medium"><?= number_format($totalLogs) ?> تۆمار</p>
					</div>
				</div>

				<div class="card">
					<div class="card-header border-bottom-0">
						<h5 class="card-title">تۆمارێن چالاکیان</h5>
					</div>
					<div class="card-body p-0">
						<?php if (empty($logs)): ?>
						<p class="text-center text-muted p-5">هێشتا هیچ تۆمار نینە.</p>
						<?php else: ?>
						<div class="table-responsive">
							<table class="table table-borderless table-hover mb-0">
								<thead class="thead-light">
									<tr>
										<th>کات</th>
										<th>کریار</th>
										<th>بەکارهێنەر</th>
										<th>وەسف</th>
										<th>ناڤنیشانێ IP</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($logs as $log): ?>
									<tr>
										<td>
											<small>
												<?= date('M j, Y', strtotime($log['created_at'])) ?>
												<br><?= date('H:i:s', strtotime($log['created_at'])) ?>
											</small>
										</td>
										<td>
											<?php
											$actionClass = 'bg-secondary';
											if (strpos($log['action'], 'login') !== false) $actionClass = 'bg-success';
											if (strpos($log['action'], 'logout') !== false) $actionClass = 'bg-warning';
											if (strpos($log['action'], 'failed') !== false) $actionClass = 'bg-danger';
											if (strpos($log['action'], 'blocked') !== false) $actionClass = 'bg-danger';
											?>
											<span class="badge <?= $actionClass ?>"><?= htmlspecialchars($log['action']) ?></span>
										</td>
										<td>
											<?php if ($log['user_name']): ?>
											<span class="text-info"><?= htmlspecialchars($log['user_name']) ?></span>
											<?php elseif ($log['admin_name']): ?>
											<span class="text-warning"><?= htmlspecialchars($log['admin_name']) ?> (ڕێڤەبەر)</span>
											<?php else: ?>
											<span class="text-muted">سیستەم</span>
											<?php endif; ?>
										</td>
										<td><?= htmlspecialchars($log['description']) ?></td>
										<td><code class="small"><?= htmlspecialchars($log['ip_address']) ?></code></td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
						
						<!-- Pagination -->
						<?php if ($totalPages > 1): ?>
						<div class="d-flex justify-content-center gap-2 p-3">
							<?php if ($page > 1): ?>
							<a href="?page=<?= $page - 1 ?>" class="btn btn-sm btn-secondary">
								<i class="ti ti-chevron-right me-1"></i>بەرێ
							</a>
							<?php endif; ?>
							
							<span class="px-3 py-2 text-muted">
								لاپەر <?= $page ?> ژ <?= $totalPages ?>
							</span>
							
							<?php if ($page < $totalPages): ?>
							<a href="?page=<?= $page + 1 ?>" class="btn btn-sm btn-secondary">
								پاشتر<i class="ti ti-chevron-left ms-1"></i>
							</a>
							<?php endif; ?>
						</div>
						<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
