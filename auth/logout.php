<?php
require_once __DIR__ . '/../config/config.php';
$_SESSION = [];
session_destroy();
session_start();
setFlash('success', 'You have been logged out.');
redirect('/auth/login.php');
