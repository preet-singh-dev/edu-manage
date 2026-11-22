<?php
/**
 * index.php
 * Entry point: routes authenticated users to dashboard, others to login.
 */

require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

redirect('login.php');
