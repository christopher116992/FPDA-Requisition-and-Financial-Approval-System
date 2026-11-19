<?php
/**
 * FRFAS — Database configuration
 * Default values match a stock XAMPP install (MySQL on localhost, user "root", no password).
 * If you set a MySQL root password in XAMPP, update DB_PASS below.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'frfas_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'FRFAS - FPDA Requisition & Financial Approval System');
define('APP_URL', 'http://localhost/frfas'); // change if you place the folder somewhere else in htdocs
define('CEO_THRESHOLD', 3000); // requests above this estimated cost also require CEO approval
define('FPDA_DOMAIN', '@fpda.com.pg'); // staff may log in with username OR their FPDA email

/**
 * SECURITY SETTINGS
 * ------------------------------------------------------------------
 * APP_ENCRYPTION_KEY encrypts backup files at rest (AES-256-CBC).
 * This is a demo default — in a real deployment, generate a long random
 * value (e.g. bin2hex(random_bytes(32))) and store it outside the
 * codebase (an environment variable), never committed to source control.
 */
define('APP_ENCRYPTION_KEY', 'FRFAS-Demo-Key-Change-Me-In-Production-2026');
define('SESSION_TIMEOUT_SECONDS', 1800); // auto-logout after 30 minutes of inactivity

/**
 * ERP / Finance system integration key.
 * A real ERP (e.g. SAP, Xero, MYOB) or FPDA's finance system can pull approved
 * requisitions machine-to-machine via api_export.php using this key in an
 * "X-API-Key" header. Rotate this regularly and never expose it publicly.
 */
define('FINANCE_API_KEY', 'FRFAS-ERP-Integration-Key-Change-Me-2026');

// Harden the session cookie before it's ever issued
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,   // JS cannot read the session cookie (mitigates XSS session theft)
    'samesite' => 'Lax',  // mitigates basic CSRF via cross-site navigation
]);

date_default_timezone_set('Pacific/Port_Moresby');
session_start();

// Security response headers (sent on every request)
header('X-Content-Type-Options: nosniff');       // stop MIME-sniffing attacks
header('X-Frame-Options: DENY');                  // clickjacking protection
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; frame-src https://www.google.com; img-src 'self' data:; script-src 'self' 'unsafe-inline'; object-src 'none'; base-uri 'self'");
