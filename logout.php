<?php
/**
 * Logout
 */

require_once __DIR__ . '/includes/init.php';

Auth::logout();
redirect('index.php');
