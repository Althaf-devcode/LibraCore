<?php

require_once __DIR__ . '/includes/functions.php';

redirect(empty($_SESSION['admin_id']) ? 'auth/login.php' : 'dashboard/index.php');
