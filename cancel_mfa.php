<?php
require_once __DIR__ . '/includes/auth.php';
clear_mfa_session();
header('Location: login.php');
exit;
