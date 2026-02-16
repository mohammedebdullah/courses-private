<?php
/**
 * Admin Sidebar
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
	<div class="sidebar" id="sidebar">
		<!-- Logo -->
		<div class="sidebar-logo">
			<a href="index.php" class="logo logo-normal">
				<img src="assets/img/logos/dark.png" alt="Logo">
			</a>
			<a href="index.php" class="logo logo-white">
				<img src="assets/img/logos/light.png" alt="Logo">
			</a>
			<a href="index.php" class="logo-small">
				<img src="assets/img/logos/dark.png" alt="Img">
			</a>
			<a id="toggle_btn" href="javascript:void(0);">
				<i data-feather="chevrons-left" class="feather-16"></i>
			</a>
		</div>
		<!-- /Logo -->

		<div class="sidebar-inner slimscroll">
			<div id="sidebar-menu" class="sidebar-menu">
				<ul>
					<li class="submenu-open">
						<h6 class="submenu-hdr">سەرەکی</h6>
						<ul>
							<li class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
								<a href="index.php"><i class="ti ti-layout-grid fs-16 me-2"></i><span>داشبۆرد</span></a>
							</li>
						</ul>
					</li>

					<li class="submenu-open">
						<h6 class="submenu-hdr">ناڤەڕۆک</h6>
						<ul>
							<li class="<?= ($currentPage === 'courses.php' || $currentPage === 'course-form.php') ? 'active' : '' ?>">
								<a href="courses.php"><i class="ti ti-book fs-16 me-2"></i><span>کۆرس</span></a>
							</li>
							<li class="<?= ($currentPage === 'lessons.php' || $currentPage === 'lesson-form.php') ? 'active' : '' ?>">
								<a href="lessons.php"><i class="ti ti-music fs-16 me-2"></i><span>وانە</span></a>
							</li>
						</ul>
					</li>

					<li class="submenu-open">
						<h6 class="submenu-hdr">کۆنترۆڵا دەستپێگەهشتنێ</h6>
						<ul>
							<li class="<?= $currentPage === 'access-codes.php' ? 'active' : '' ?>">
								<a href="access-codes.php"><i class="ti ti-key fs-16 me-2"></i><span>کۆدێن دەستپێگەهشتنێ</span></a>
							</li>
							<li class="<?= $currentPage === 'users.php' ? 'active' : '' ?>">
								<a href="users.php"><i class="ti ti-users fs-16 me-2"></i><span>بەکارهێنەر</span></a>
							</li>
						</ul>
					</li>

					<li class="submenu-open">
						<h6 class="submenu-hdr">سیستەم</h6>
						<ul>
							<li class="<?= $currentPage === 'activity-logs.php' ? 'active' : '' ?>">
								<a href="activity-logs.php"><i class="ti ti-file-text fs-16 me-2"></i><span>تۆمارێن چالاکیان</span></a>
							</li>
							<li>
								<a href="logout.php"><i class="ti ti-logout fs-16 me-2"></i><span>دەرچوون</span></a>
							</li>
						</ul>
					</li>
				</ul>
			</div>
		</div>
		
		<!-- Auto-activate sidebar items based on current URL -->
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			try {
				var current = location.pathname.split('/').pop() || 'index.php';
				var links = document.querySelectorAll('#sidebar-menu a[href]');
				links.forEach(function(a) {
					var href = a.getAttribute('href').split('/').pop();
					if (!href) return;
					if (href === current) {
						var li = a.closest('li');
						if (li) li.classList.add('active');
						a.classList.add('active');
					}
				});
			} catch (e) { /* fail silently */ }
		});
		</script>
	</div>
