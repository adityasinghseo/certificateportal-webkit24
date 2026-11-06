<?php
/**
 * Admin Logout
 */

define('WEBKIT24_APP', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

admin_logout();
header('Location: login.php');
exit;

