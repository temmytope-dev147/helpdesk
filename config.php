<?php
// ─────────────────────────────────────────
// APP CONFIGURATION
// ─────────────────────────────────────────
define('APP_BASE_URL', 'http://localhost/helpdesk');

// ─────────────────────────────────────────
// ERROR HANDLING
// ─────────────────────────────────────────
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
error_reporting(E_ALL);

// ─────────────────────────────────────────
// OUTPUT BUFFER
// ─────────────────────────────────────────
if (ob_get_level() === 0) {
    ob_start();
}

// ─────────────────────────────────────────
// SESSION
// ─────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─────────────────────────────────────────
// DATABASE CONFIG
// ─────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'SA_HelpDesk');
define('DB_USER', 'helpdesk_user');
define('DB_PASS', 'Sterling123?');

// ─────────────────────────────────────────
// DATABASE CONNECTION (SINGLE INSTANCE)
// ─────────────────────────────────────────
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $dsn  = "sqlsrv:Server=" . DB_HOST . ";Database=" . DB_NAME;
            $conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            error_log("DB ERROR: " . $e->getMessage());
            respond(['success' => false, 'message' => 'Database connection failed'], 500);
        }
    }

    return $conn;
}

// ─────────────────────────────────────────
// RESPONSE HELPER
// ─────────────────────────────────────────
if (!function_exists('respond')) {
    function respond($data, $status = 200) {
        ob_clean();
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// ─────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────
function sanitize($v) {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

function requireLogin() {
    if (!isset($_SESSION['user'])) {
        respond(['success' => false, 'message' => 'Not logged in'], 401);
    }
}

// ─────────────────────────────────────────
// LOAD PHPMailer
// ─────────────────────────────────────────
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ─────────────────────────────────────────
// EMAIL NOTIFICATION
// Tries port 587 (STARTTLS) first, then falls
// back to port 465 (SSL). Includes LOB and
// optional Policy Number in the email body.
// ─────────────────────────────────────────
function sendEmailNotification(
    $ticketId, $user, $subject, $desc,
    $type, $priority, $branch,
    $lob = '', $policyNo = ''
) {
    $configs = [
        ['secure' => PHPMailer::ENCRYPTION_STARTTLS, 'port' => 587, 'label' => 'STARTTLS:587'],
        ['secure' => PHPMailer::ENCRYPTION_SMTPS,    'port' => 465, 'label' => 'SSL:465'],
    ];

    $lastError = '';

    foreach ($configs as $cfg) {
        try {
            $mail = new PHPMailer(true);

            // FIX: SMTPDebug = 0 — NEVER set to 2 in production.
            // Debug output corrupts the output buffer and breaks JSON responses.
            $mail->SMTPDebug   = 0;
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP DEBUG: " . trim($str));
            };

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'quadri904@gmail.com';
            $mail->Password   = 'fdmdefifflkiiqnm';
            $mail->SMTPSecure = $cfg['secure'];
            $mail->Port       = $cfg['port'];
            $mail->Timeout    = 15;

            $mail->setFrom('quadri904@gmail.com', 'IT Helpdesk');
            $mail->addAddress('quadri904@gmail.com');
            if (!empty($user['email'])) {
                $mail->addAddress($user['email']);
            }

            $policyRow = !empty($policyNo)
                ? "<p><strong>Policy Number:</strong> " . htmlspecialchars($policyNo, ENT_QUOTES, 'UTF-8') . "</p>"
                : '';

            $mail->isHTML(true);
            $mail->Subject = "New Ticket Created - #$ticketId";
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Body    = "
                <h3>New Helpdesk Ticket Submitted</h3>
                <hr>
                <p><strong>Ticket ID:</strong> #$ticketId</p>
                <p><strong>User:</strong> {$user['fullname']} ({$user['email']})</p>
                <p><strong>Type:</strong> $type</p>
                <p><strong>Priority:</strong> $priority</p>
                <p><strong>Branch:</strong> $branch</p>
                <p><strong>Line of Business:</strong> $lob</p>
                $policyRow
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Description:</strong><br>$desc</p>
                <br>
                <p><i>Please login to the admin portal to respond.</i></p>
            ";

            $mail->send();
            error_log("MAIL SUCCESS [{$cfg['label']}]: Ticket #$ticketId sent to {$user['email']}");
            return;

        } catch (Throwable $e) {
            $lastError = $e->getMessage();
            error_log("MAIL FAILED [{$cfg['label']}]: " . $lastError . " — trying next config...");
        }
    }

    error_log("MAIL ERROR: All SMTP configs failed for Ticket #$ticketId. Last error: $lastError");
}