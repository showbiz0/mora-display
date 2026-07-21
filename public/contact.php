<?php
session_start();

define('RECIPIENT_EMAIL', 'director@moradisplay.com'); // Where you want messages sent
define('DOMAIN_SENDER', 'director@moradisplay.com');        // Created in Hostinger control panel
define('COOLDOWN_SECONDS', 60);

// 1. Enforce POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /#contact');
    exit;
}

// 2. Honeypot check
if (!empty($_POST['website_hp'])) {
    header('Location: /?status=success#contact');
    exit;
}

// 3. Cooldown check
if (isset($_SESSION['last_submit_time']) && (time() - $_SESSION['last_submit_time']) < COOLDOWN_SECONDS) {
    header('Location: /?status=rate_limited#contact');
    exit;
}

// 4. Sanitize inputs
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

// Strip header-injection attempts
$name  = str_replace(["\r", "\n"], '', $name);
$email = str_replace(["\r", "\n"], '', $email);

// Limit max length
$name    = substr($name, 0, 100);
$email   = substr($email, 0, 100);
$message = substr($message, 0, 3000);

if (empty($name) || empty($email) || empty($message)) {
    header('Location: /?status=empty#contact');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /?status=invalid_email#contact');
    exit;
}

// 5. Build and send email
$subject = "New Contact Form Submission from " . $name;

$body  = "Name: " . $name . "\n";
$body .= "Email: " . $email . "\n";
$body .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n\n";
$body .= "Message:\n" . $message . "\n";

$headers = [
    'From: Website Form <' . DOMAIN_SENDER . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion()
];

if (mail(RECIPIENT_EMAIL, $subject, $body, implode("\r\n", $headers))) {
    $_SESSION['last_submit_time'] = time();
    header('Location: /?status=success#contact');
    exit;
} else {
    header('Location: /?status=error#contact');
    exit;
}