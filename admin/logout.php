<?php
/**
 * Admin Logout
 */

require_once __DIR__ . '/../includes/init.php';

Auth::adminLogout();
redirect('login.php');
