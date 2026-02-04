<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'menu_digitale');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', 'http://localhost/menu_digitale');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

date_default_timezone_set('Europe/Rome');

session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
