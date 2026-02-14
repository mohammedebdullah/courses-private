<?php
/**
 * Admin - Access Codes Management
 */

require_once __DIR__ . '/includes/admin_auth.php';

$pageTitle = 'Access Codes Management';
$db = getDB();

$message = '';
$error = '';
$generatedCodes = [];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'داخوازیا نه‌دروسته‌.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'generate') {
            $count = intval($_POST['count'] ?? 1);
            $count = max(1, min(50, $count)); // 1-50 codes at a time
            $durationHours = intval($_POST['duration_hours'] ?? 8760); // Default to 1 year if not specified
            $notes = trim($_POST['notes'] ?? '');
            
            $generatedCodes = AccessCode::generateBatch($currentAdmin['id'], $count, $durationHours, $notes);
            $message = "$count کۆدێن دەستپێگەهشتنێ ب سەرکەفتیانە هاتنە دروستکرن.";
        }
        
        if ($action === 'revoke') {
            $codeId = intval($_POST['id'] ?? 0);
            if ($codeId) {
                AccessCode::revoke($codeId, $currentAdmin['id']);
                $message = 'کۆدێ دەستپێگەهشتنێ هاتە هەلوەشاندن.';
            }
        }

        if ($action === 'reactivate') {
            $codeId = intval($_POST['id'] ?? 0);
            if ($codeId) {
                AccessCode::reactivate($codeId, $currentAdmin['id']);
                $message = 'کۆدێ دەستپێگەهشتنێ دووبارە هاتە کاراکرن.';
            }
        }        
        if ($action === 'reactivate') {
            $codeId = intval($_POST['id'] ?? 0);
            $extendHours = intval($_POST['extend_hours'] ?? 0);
            if ($codeId) {
                if (AccessCode::reactivate($codeId, $currentAdmin['id'], $extendHours ?: null)) {
                    $message = 'کۆدێ دەستپێگەهشتنێ هاتە چالاککردنەوە. ئێستا بەکارهێنەر دەتوانێت دووبارە بیبەکاربێنێت.';
                } else {
                    $error = 'هەڵەیەک ڕوویدا لە کاتی چالاککردنەوەی کۆد.';
                }
            }
        }    }
}

// Get filter
$statusFilter = $_GET['status'] ?? '';

// Get access codes
$codes = AccessCode::getAll($statusFilter ?: null);

// Get stats
$stats = AccessCode::getStats();

include __DIR__ . '/includes/header_top.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

		<div class="page-wrapper">
			<div class="content">
				<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
					<div class="mb-3">
						<h1 class="mb-1">کۆدێن دەستپێگەهشتنێ</h1>
						<p class="fw-medium">بەرێڤەبرنا کۆدێن دەستپێگەهشتنێ</p>
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

				<!-- Display Generated Codes -->
				<?php if (!empty($generatedCodes)): ?>
				<div class="card mb-4" style="border: 2px solid var(--bs-success);">
					<div class="card-header border-bottom-0 bg-success bg-opacity-10">
						<h5 class="card-title text-success">کۆدێن هاتینە دروستکرن</h5>
						<button class="btn btn-success btn-sm" onclick="copyAllCodes()" style="margin-top: 15px;">
							<i class="ti ti-copy me-1"></i>هەمیان کۆپیبکە
						</button>
					</div>
					<div class="card-body">
						<!-- <p class="text-muted mb-3">
							ئەڤ کۆدە تنێ ئێک جار دیار دبن. هیڤیە کۆپی بکە و پاشەکەوت بکە.
						</p> -->
						<div id="generatedCodes">
							<?php foreach ($generatedCodes as $code): ?>
							<div class="code-display p-2 bg-light rounded mb-2 font-monospace"><?= htmlspecialchars($code['code']) ?></div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<!-- Stats -->
				<div class="row mb-4">
					<div class="col-md-4 col-sm-6 mb-3">
						<div class="card">
							<div class="card-body text-center">
								<div class="mb-2"><i class="ti ti-check text-success" style="font-size: 2rem;"></i></div>
								<h3 class="mb-1"><?= $stats['available'] ?? 0 ?></h3>
								<p class="text-muted mb-0">بەردەست</p>
							</div>
						</div>
					</div>
				<div class="col-md-4 col-sm-6 mb-3">
					<div class="card">
						<div class="card-body text-center">
							<div class="mb-2"><i class="ti ti-user text-info" style="font-size: 2rem;"></i></div>
							<h3 class="mb-1"><?= $stats['used'] ?? 0 ?></h3>
							<p class="text-muted mb-0">بکارئینای</p>
						</div>
					</div>
				</div>
				<div class="col-md-4 col-sm-6 mb-3">
						<div class="card">
							<div class="card-body text-center">
								<div class="mb-2"><i class="ti ti-lock text-primary" style="font-size: 2rem;"></i></div>
								<h3 class="mb-1"><?= $stats['total'] ?? 0 ?></h3>
								<p class="text-muted mb-0">گشتی</p>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<!-- Generate New Codes -->
					<div class="col-12 mb-4">
						<div class="card">
							<div class="card-header border-bottom-0">
								<h5 class="card-title">چێکرنا کۆدان</h5>
							</div>
							<div class="card-body">
								<form method="POST" action="">
									<?= csrf_field() ?>
									<input type="hidden" name="action" value="generate">
									
									<div class="row align-items-end">
										<div class="col-md-4 mb-3">
											<label class="form-label" for="count">ژمارا کۆدان</label>
											<input type="number" 
												   class="form-control" 
												   id="count" 
												   name="count" 
												   value="1"
												   min="1" 
												   max="50">
										</div>
										
										<div class="col-md-6 mb-3">
											<label class="form-label" for="notes">تێبینی (دڵخوازە)</label>
											<input type="text" 
												   class="form-control" 
												   id="notes" 
												   name="notes"
												   placeholder="بۆ نموونە، ژبو گروپێ A یێ قوتابیان">
										</div>
										
										<div class="col-md-2 mb-3">
											<button type="submit" class="btn btn-primary w-100">
												<i class="ti ti-plus me-1"></i>چێکرن
											</button>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
					
					<!-- Codes List -->
					<div class="col-12">
						<div class="card">
							<div class="card-header border-bottom-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
								<h5 class="card-title">کۆدێن دەستپێگەهشتنێ</h5>
								<select class="form-control form-select" style="width: 150px;" onchange="window.location.href='access-codes.php' + (this.value ? '?status=' + this.value : '')">
									<option value="">هەمی ڕەوش</option>
									<option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>بەردەست</option>
									<option value="used" <?= $statusFilter === 'used' ? 'selected' : '' ?>>بکارئینای</option>

									<option value="revoked" <?= $statusFilter === 'revoked' ? 'selected' : '' ?>>هەلوەشاندی</option>
								</select>
							</div>
							<div class="card-body">
								<?php if (empty($codes)): ?>
								<p class="text-center text-muted p-5">چ کۆد نەهاتنە دیتن.</p>
								<?php else: ?>
								<div class="table-responsive">
									<table id="accessCodesTable" class="table table-hover mb-0">
										<thead class="thead-light">
											<tr>
												<th>کۆد</th>
												<th>ڕەوش</th>
												<th>بەکارهێنەر</th>
												<th>بەردەستە هەتا</th>
												<th>کریار</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($codes as $code): ?>
											<tr>
												<td>
													<span class="d-block fw-bold text-primary font-monospace" style="font-size: .9rem; letter-spacing: 2px;"><?= htmlspecialchars($code['code']) ?></span>
													<?php if ($code['notes']): ?>
													<small class="text-muted"><?= htmlspecialchars($code['notes']) ?></small>
													<?php endif; ?>
												</td>
												<td>
													<?php if ($code['status'] === 'active' && strtotime($code['valid_until']) > time()): ?>
													<span class="badge bg-success fs-7" style="font-size: .6rem;">بەردەست</span>
													<?php elseif ($code['status'] === 'used'): ?>
													<span class="badge bg-secondary fs-7">بکارئینای</span>
													<?php elseif ($code['status'] === 'revoked'): ?>
													<span class="badge bg-danger fs-7">هەلوەشاندی</span>
													<?php else: ?>
													<span class="badge bg-warning fs-7">بەسەرچووی</span>
													<?php endif; ?>
												</td>
												<td>
													<?php if ($code['user_name']): ?>
													<?= htmlspecialchars($code['user_name']) ?>
													<?php else: ?>
													<span class="text-muted">-</span>
													<?php endif; ?>
												</td>
												<td><?= date('M j, Y', strtotime($code['valid_until'])) ?></td>
												<td>
													<?php if ($code['status'] === 'active' || $code['status'] === 'used'): ?>
													<form method="POST" style="display: inline;" class="revoke-form" data-code-id="<?= $code['id'] ?>">
														<?= csrf_field() ?>
														<input type="hidden" name="action" value="revoke">
														<input type="hidden" name="id" value="<?= $code['id'] ?>">
														<button type="button" class="btn btn-sm btn-danger revoke-btn" title="هەلوەشاندن">
															<i class="ti ti-x"></i>
														</button>
													</form>
													<?php endif; ?>
													
													<?php if ($code['status'] === 'used' || $code['status'] === 'revoked' || $code['status'] === 'expired'): ?>
													<button type="button" class="btn btn-sm btn-success" title="کاراکرن" data-bs-toggle="modal" data-bs-target="#reactivateModal<?= $code['id'] ?>">
														<i class="ti ti-refresh"></i>
													</button>
													
													<!-- Reactivate Modal -->
													<div class="modal fade" id="reactivateModal<?= $code['id'] ?>" tabindex="-1" aria-hidden="true">
														<div class="modal-dialog">
															<div class="modal-content">
																<div class="modal-header">
																	<h5 class="modal-title">چالاکرنا کودی دوبارە</h5>
																	<!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
																</div>
																<form method="POST">
																	<?= csrf_field() ?>
																	<input type="hidden" name="action" value="reactivate">
																	<input type="hidden" name="id" value="<?= $code['id'] ?>">
																	<div class="modal-body">
																		<p class="mb-3">تە دڤێت ڤی کۆدی چالاک بکەیە ڤە: <strong class="font-monospace"><?= htmlspecialchars($code['code']) ?></strong>?</p>
																		<p></p>
																		<!-- <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
																			<i class="ti ti-alert-triangle me-2 fs-5"></i>
																			<p>ئەگەر بەکارهێنەرێک ئەم کۆدە بەکاردەهێنێت، ئەوان دەدەرکرێن و کۆد بۆ بەکارهێنەری نوێ ئامادە دەبێت.</p>
																		</div>
																		 -->
																		<!-- <div class="mb-3">
																			<label class="form-label">درێژکردنەوەی ماوە</label>
																			<select class="form-control form-select" name="extend_hours">
																				<option value="0">بەکارهێنانی ماوەی سەرەتایی (<?= $code['duration_hours'] ?> کاتژمێر)</option>
																				<option value="24">١ ڕۆژ</option>
																				<option value="168">١ هەفتە</option>
																				<option value="720">٣٠ ڕۆژ</option>
																				<option value="2160">٩٠ ڕۆژ</option>
																				<option value="8760">١ ساڵ</option>
																			</select>
																		</div> -->
																	</div>
																	<div class="modal-footer">
																		<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">لێڤەبوون</button>
																		<button type="submit" class="btn btn-success"><i class="ti ti-refresh me-1"></i>چالاک کرن</button>
																	</div>
																</form>
															</div>
														</div>
													</div>
													<?php endif; ?>
												</td>
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

<script>
function copyAllCodes() {
    const codes = document.querySelectorAll('#generatedCodes .code-display');
    const text = Array.from(codes).map(el => el.textContent).join('\n');
    
    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'سەرکەفتی!',
            text: 'هەمی کود هاتنە کوپیکرن!',
            confirmButtonText: 'باشە',
            timer: 2000
        });
    });
}

// SweetAlert for revoke confirmation
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
    
    document.querySelectorAll('.revoke-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.revoke-form');
            
            Swal.fire({
                title: 'پشتراستی',
                text: 'تە دڤێت ڤی کۆدی هەلوەشینی؟ بەکارهێنەرێ وی دێ هێتە دەرخستن.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'بەلێ، هەلوەشینە!',
                cancelButtonText: 'لێڤەبوون'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    if ($('#accessCodesTable').length > 0) {
        $('#accessCodesTable').DataTable({
            "bFilter": true,
            "sDom": 'fBtlpi',  
            "ordering": true,
            "order": [[ 3, "desc" ]], // Order by expiry date default
            "language": {
                search: '',
                searchPlaceholder: "لێگەڕین...",
                sLengthMenu: '_MENU_ نیشان بدە',
                info: "نیشاندانا _START_ هەتا _END_ ژ _TOTAL_ تۆماران",
                infoEmpty: "هیچ تۆمار نینە",
                infoFiltered: "(پاڵاوتن ژ _MAX_ تۆماران)",
                zeroRecords: "هیچ تۆمار نەهاتنە دیتن",
                paginate: {
                    next: ' <i class=" fa fa-angle-left"></i>', // RTL flipped icons
                    previous: '<i class="fa fa-angle-right"></i> ' // RTL flipped icons
                },
            },
            initComplete: function(settings, json) {
                $('.dataTables_filter input').addClass('form-control');
                $('.dataTables_length select').addClass('form-select');
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
