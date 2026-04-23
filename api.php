<?php
// ─────────────────────────────────────────
// PING — quick reachability check
// ─────────────────────────────────────────
if (($_GET['action'] ?? '') === 'ping') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'msg' => 'API is reachable']);
    exit;
}

// ─────────────────────────────────────────
// BOOTSTRAP
// ─────────────────────────────────────────
require_once 'config.php';

// ─────────────────────────────────────────
// ACTION ROUTER
// ─────────────────────────────────────────
$action = $_GET['action'] ?? '';

switch ($action) {

    case 'csrf_token':
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        respond(['csrf_token' => $_SESSION['csrf_token']]);
        break;

    case 'login':
        login();
        break;

    case 'me':
        if (isset($_SESSION['user'])) {
            respond(['success' => true, 'user' => $_SESSION['user']]);
        } else {
            respond(['success' => false]);
        }
        break;

    case 'create_ticket':
        requireLogin();
        createTicket();
        break;

    case 'fetch_tickets':
        requireLogin();
        fetchTickets();
        break;

    case 'logout':
        session_destroy();
        respond(['success' => true]);
        break;

    default:
        respond(['success' => false, 'message' => 'Invalid action']);
}

// ── LOGIN ─────────────────────────────────────────────────────────────────────
function login() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || $token !== ($_SESSION['csrf_token'] ?? '')) {
        respond([
            'success' => false,
            'message' => 'Invalid request. Please refresh the page and try again.'
        ], 403);
    }

    $email    = sanitize($_POST['email']    ?? '');
    $password = $_POST['password']          ?? '';

    if (!$email || !$password) {
        respond(['success' => false, 'message' => 'Please fill in all fields.']);
    }

    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM userlog WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        respond(['success' => false, 'message' => 'Invalid email or password.']);
    }

    $_SESSION['user'] = [
        'id'       => $user['id'],
        'fullname' => $user['fullname'],
        'email'    => $user['email'],
        'is_admin' => $user['is_admin']
    ];

    session_regenerate_id(true);

    respond(['success' => true, 'user' => $_SESSION['user']]);
}

// ── CREATE TICKET ─────────────────────────────────────────────────────────────
function createTicket() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$token || $token !== ($_SESSION['csrf_token'] ?? '')) {
        respond(['success' => false, 'message' => 'Invalid request token.'], 403);
    }

    $user = $_SESSION['user'];

    $subject  = sanitize($_POST['subject']     ?? '');
    $desc     = sanitize($_POST['description'] ?? '');
    $type     = sanitize($_POST['type']        ?? '');
    $branch   = sanitize($_POST['branch']      ?? '');
    $priority = sanitize($_POST['priority']    ?? '');
    $lob      = sanitize($_POST['lob']         ?? '');
    $policyNo = sanitize($_POST['policy_no']   ?? '');

    if (!$subject || !$desc) {
        respond(['success' => false, 'message' => 'Subject and description are required.']);
    }

    if (!$lob) {
        respond(['success' => false, 'message' => 'Please select a line of business.']);
    }

    // ── HANDLE SCREENSHOT UPLOAD ──────────────────────────────────────────────
    $screenshotPath = null;

    if (!empty($_FILES['screenshot']['name'])) {
        $file    = $_FILES['screenshot'];
        $maxSize = 5 * 1024 * 1024;
        $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

        if ($file['size'] > $maxSize) {
            respond(['success' => false, 'message' => 'Screenshot must be under 5 MB.']);
        }

        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, $allowed)) {
            respond(['success' => false, 'message' => 'Only PNG, JPG, GIF, and WEBP images are allowed.']);
        }

        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext          = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeFilename = 'ticket_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . strtolower($ext);
        $destination  = $uploadDir . $safeFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            error_log("UPLOAD ERROR: Could not move file to $destination");
            respond(['success' => false, 'message' => 'Failed to save screenshot. Please try again.']);
        }

        $screenshotPath = 'http://localhost/helpdesk/uploads/' . $safeFilename;
    }

    // ── INSERT TICKET ─────────────────────────────────────────────────────────
    $conn = getDBConnection();

    // OUTPUT INSERTED.id is the reliable SQL Server way to get the new row ID.
    // lastInsertId() and SCOPE_IDENTITY() are unreliable with PDO sqlsrv.
    $stmt = $conn->prepare("
        INSERT INTO tickets
            (user_id, type, subject, description, branch, priority, lob, policy_no, status, screenshot_path, created_at)
        OUTPUT INSERTED.id
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, 'Open', ?, GETDATE())
    ");

    $stmt->execute([
        $user['id'], $type, $subject, $desc, $branch, $priority,
        $lob,
        $policyNo !== '' ? $policyNo : null,
        $screenshotPath
    ]);

    $row      = $stmt->fetch(PDO::FETCH_ASSOC);
    $ticketId = $row['id'] ?? null;

    if (!$ticketId) {
        error_log("TICKET ID RETRIEVAL FAILED for user={$user['email']}");
        respond(['success' => false, 'message' => 'Ticket saved but ID could not be retrieved. Please contact IT support.'], 500);
    }

    error_log("TICKET CREATED: ID=$ticketId for user={$user['email']}");

    // Email failure must never crash ticket submission
    try {
        sendEmailNotification($ticketId, $user, $subject, $desc, $type, $priority, $branch, $lob, $policyNo);
    } catch (Throwable $e) {
        error_log("EMAIL ERROR for ticket #$ticketId: " . $e->getMessage());
    }

    respond(['success' => true, 'ticket_id' => $ticketId]);
}

// ── FETCH TICKETS ─────────────────────────────────────────────────────────────
function fetchTickets() {
    $userId = $_SESSION['user']['id'];

    $conn = getDBConnection();
    $stmt = $conn->prepare("
        SELECT * FROM tickets
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);

    respond(['success' => true, 'tickets' => $stmt->fetchAll()]);
}