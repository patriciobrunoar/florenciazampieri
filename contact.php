<?php
// Contact form handler: emails the message to info@florenciazampieri.ar
const TO_ADDRESS   = 'info@florenciazampieri.ar';
const FROM_ADDRESS = 'info@florenciazampieri.ar'; // must be an address on this domain for deliverability

$wantsJson = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
$lang = (isset($_POST['lang']) && $_POST['lang'] === 'es') ? 'es' : 'en';
$page = $lang === 'es' ? 'contact-es.html' : 'contact.html';

function respond($ok, $code, $wantsJson, $page) {
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : 400);
        echo json_encode(['ok' => $ok, 'code' => $code]);
    } else {
        header('Location: ' . $page . '?' . ($ok ? 'sent=1' : 'error=' . $code) . '#contact-form');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $page);
    exit;
}

// Honeypot: real visitors leave this empty. Pretend success to bots.
if (!empty($_POST['website'])) {
    respond(true, 'ok', $wantsJson, $page);
}

$name    = trim((string)($_POST['name'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

// Strip anything that could inject extra mail headers
$name  = preg_replace('/[\r\n]+/', ' ', $name);
$email = preg_replace('/[\r\n]+/', '', $email);

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'invalid', $wantsJson, $page);
}
if (strlen($name) > 200 || strlen($email) > 200 || strlen($message) > 5000) {
    respond(false, 'toolong', $wantsJson, $page);
}

$subject = 'Website contact: ' . $name;
$subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
$body = "Name: $name\nEmail: $email\nLanguage: $lang\n\n$message\n";

$headers = [
    'From: Florencia Zampieri Website <' . FROM_ADDRESS . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion(),
];
// Reply-To display name must not contain odd characters
$headers[1] = 'Reply-To: ' . $email;

$sent = mail(TO_ADDRESS, $subject, $body, implode("\r\n", $headers), '-f' . FROM_ADDRESS);

respond((bool)$sent, $sent ? 'ok' : 'send', $wantsJson, $page);
