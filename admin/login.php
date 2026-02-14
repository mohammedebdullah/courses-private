<?php
/**
 * Admin Login Page
 */

require_once __DIR__ . '/../includes/init.php';

// Redirect if already logged in
if (Auth::isAdminLoggedIn()) {
    redirect('index.php');
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'داواکاری نادروست. تکایە دووبارە هەوڵ بدەرەوە.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'تکایە ناوی بەکارهێنەر و وشەی نهێنی بنووسە.';
        } else {
            $result = Auth::adminLogin($username, $password);
            
            if ($result['success']) {
                redirect('index.php');
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
	<!-- Meta Tags -->
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="Admin Panel for  Course">
	<meta name="author" content=" Courses">
	<title>Admin Login</title>

	<!-- Favicon -->
	<link rel="shortcut icon" type="image/x-icon" href="assets/img/logos/light.png">

	<!-- Apple Touch Icon -->
	<link rel="apple-touch-icon" sizes="180x180" href="assets/img/logos/light.png">
	
	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
	
	<!-- Fontawesome CSS -->
	<link rel="stylesheet" href="assets/plugins/fontawesome/css/fontawesome.min.css">
	<link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">

	<!-- Tabler Icon CSS -->
	<link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">

	<!-- Main CSS -->
	<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="layout-mode-rtl account-page bg-white">

	<div id="global-loader">
		<div class="whirly-loader"> </div>
	</div>

	<!-- Main Wrapper -->
	<div class="main-wrapper">
		<div class="account-content">
			<div class="login-wrapper login-new">
				<div class="row w-100">
					<div class="col-lg-5 mx-auto">
						<div class="login-content user-login">
							<div class="login-logo">
								<img src="assets/img/logos/dark.png" alt="img" width="200">
								<a href="index.php" class="login-logo logo-white">
									<img src="assets/img/logos/dark.png" alt="Img">
								</a>
							</div>
							<form action="" method="POST">
								<?= csrf_field() ?>
								<div class="card">
									<div class="card-body p-5">
										<?php if ($error): ?>
										<div class="alert alert-danger alert-dismissible fade show" role="alert">
											<i class="ti ti-alert-circle me-2"></i>
											<?php echo htmlspecialchars($error); ?>
											<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
										</div>
										<?php endif; ?>
										
										<div class="login-userheading">
											<h3>چوونەژوورەوە</h3>
											<p>پانێلی بەڕێوەبەری کۆرسی دەنگی</p>
										</div>
										<div class="mb-3">
											<label class="form-label">ناوی بەکارهێنەر یان ئیمەیڵ <span class="text-danger">*</span></label>
											<div class="input-group">
												<input type="text" name="username" class="form-control border-end-0" required autofocus>
												<span class="input-group-text border-start-0">
													<i class="ti ti-user"></i>
												</span>
											</div>
										</div>
										<div class="mb-3">
											<label class="form-label">وشەی نهێنی <span class="text-danger">*</span></label>
											<div class="pass-group">
												<input type="password" name="password" class="pass-input form-control" required>
												<span class="ti toggle-password ti-eye-off text-gray-9"></span>
											</div>
										</div>
										<div class="form-login">
											<button type="submit" class="btn btn-primary w-100">چوونەژوورەوە</button>
										</div>
										<p class="text-center mt-3 text-muted small">
											بنەڕەت: admin / admin123
										</p>
									</div>
								</div>
							</form>
						</div>
						<div class="d-flex justify-content-center align-items-center copyright-text text-center">
							<div>
								<p>هەموو مافەکان پارێزراون &copy; <?php echo date('Y'); ?> کۆرسی دەنگی</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- /Main Wrapper -->

	<!-- jQuery -->
	<script src="assets/js/jquery-3.7.1.min.js"></script>

	<!-- Feather Icon JS -->
	<script src="assets/js/feather.min.js"></script>
	
	<!-- Bootstrap Core JS -->
	<script src="assets/js/bootstrap.bundle.min.js"></script>
	
	<!-- Custom JS -->
	<script src="assets/js/script.js"></script>

</body>
</html>
