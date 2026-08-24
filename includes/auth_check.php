<?php

require_once __DIR__ . '/functions.php';

if (empty($_SESSION['admin_id'])) {
    flash('error', 'Please log in to access the LibraCore admin panel.');
    redirect('auth/login.php');
}
