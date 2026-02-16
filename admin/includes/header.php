<?php
/**
 * Admin Header
 */
?>
		<!-- Header -->
		<div class="header">
			<div class="main-header">
				<!-- Logo -->
				<div class="header-left active">
					<a href="index.php" class="logo logo-normal">
						<img src="assets/img/logos/black.png" alt="Img">
					</a>
					<a href="index.php" class="logo logo-white">
						<img src="assets/img/logos/white.png" alt="Img">
					</a>
					<a href="index.php" class="logo-small">
						<img src="assets/img/logos/black.png" alt="Img">
					</a>
				</div>
				<!-- /Logo -->
				<a id="mobile_btn" class="mobile_btn" href="#sidebar">
					<span class="bar-icon">
						<span></span>
						<span></span>
						<span></span>
					</span>
				</a>

				<!-- Header Menu -->
				<ul class="nav user-menu">

					<li class="nav-item dropdown link-nav">
						<a href="javascript:void(0);" class="btn btn-primary btn-md d-inline-flex align-items-center" data-bs-toggle="dropdown">
							<i class="ti ti-circle-plus me-1"></i>زێدەکرنا نوی
						</a>
						<div class="dropdown-menu dropdown-xl dropdown-menu-center">
							<div class="row g-2">
								<div class="col-md-3">
									<a href="course-form.php" class="link-item">
										<span class="link-icon">
											<i class="ti ti-book"></i>
										</span>
										<p>کۆرسی نوی</p>
									</a>
								</div>
								<div class="col-md-3">
									<a href="lesson-form.php" class="link-item">
										<span class="link-icon">
											<i class="ti ti-music"></i>
										</span>
										<p>وانەی نوی</p>
									</a>
								</div>
								<div class="col-md-3">
									<a href="access-codes.php" class="link-item">
										<span class="link-icon">
											<i class="ti ti-key"></i>
										</span>
										<p>کۆدەکێ نوی</p>
									</a>
								</div>
								<div class="col-md-3">
									<a href="users.php" class="link-item">
										<span class="link-icon">
											<i class="ti ti-users"></i>
										</span>
										<p>بەکارهێنەر</p>
									</a>
								</div>
							</div>
						</div>
					</li>

					<li class="nav-item nav-item-box">
						<a href="javascript:void(0);" id="btnFullscreen">
							<i class="ti ti-maximize"></i>
						</a>
					</li>

					<li class="nav-item dropdown has-arrow main-drop profile-nav">
						<a href="javascript:void(0);" class="nav-link userset" data-bs-toggle="dropdown">
							<span class="user-info p-0">
								<span class="user-letter">
									<?php 
									$admin_name = $currentAdmin['full_name'] ?? 'Admin';
									$first_letter = mb_substr($admin_name, 0, 1);
									?>
									<span class="avatar avatar-md rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"><?php echo htmlspecialchars($first_letter); ?></span>
								</span>
							</span>
						</a>
						<div class="dropdown-menu menu-drop-user">
							<div class="profileset d-flex align-items-center">
								<div>
									<h6 class="fw-medium"><?php echo htmlspecialchars($admin_name); ?></h6>
									<p>ڕێڤەبەر</p>
								</div>
							</div>
							<hr class="my-2">
							<a class="dropdown-item logout pb-0" href="logout.php"><i class="ti ti-logout me-2"></i>دەرچوون</a>
						</div>
					</li>
				</ul>
				<!-- /Header Menu -->

				<!-- Mobile Menu -->
				<div class="dropdown mobile-user-menu">
					<a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
					<div class="dropdown-menu dropdown-menu-right">
						<a class="dropdown-item" href="logout.php">دەرچوون</a>
					</div>
				</div>
				<!-- /Mobile Menu -->
			</div>
		</div>
		<!-- /Header -->
