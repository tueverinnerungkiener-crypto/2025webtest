<?php
// test_smtp.php - Standalone SMTP Test mit PHPMailer Debug

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

$config = include __DIR__ . '/config.php';

$to          = $config['MAIL_TO'] ?? 'you@example.com';
$from        = $config['MAIL_FROM'] ?? '';
$fromName    = $config['MAIL_FROM_NAME'] ?? 'SMTP Test';
$host        = $config['SMTP_HOST'] ?? '';
$user        = $config['SMTP_USER'] ?? '';
$pass        = $config['SMTP_PASS'] ?? '';
$port        = (int)($config['SMTP_PORT'] ?? 587);
$secure      = $config['SMTP_SECURE'] ?? 'tls';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>PHPMailer SMTP Debug Test</h2>";
echo "<pre style='background:#111;color:#0f0;padding:12px;border-radius:8px;white-space:pre-wrap;'>";

$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug  = 2;       // 0=aus, 2=Details
    $mail->Debugoutput = function($str, $level) {
        echo htmlspecialchars($str) . "\n";
    };

    $mail->isSMTP();
    $mail->Host       = $host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;
    $mail->Password   = $pass;
    $mail->SMTPSecure = ($secure === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $port;

    $mail->setFrom($from ?: $user, $fromName);
    $mail->addAddress($to);

    $mail->isHTML(true);
    $mail->Subject = 'SMTP Debug Test von ' . ($_SERVER['HTTP_HOST'] ?? 'CLI');
    $mail->Body    = '<p>Dies ist ein Test. Absender: <b>'.htmlspecialchars($mail->Username).'</b></p>';

    $ok = $mail->send();
    echo "\n---\nErgebnis: " . ($ok ? "OK ✅" : "FEHLER ❌") . "\n";
} catch (Exception $e) {
    echo "\n---\nException: " . $e->getMessage() . "\n";
    echo "Mailer ErrorInfo: " . $mail->ErrorInfo . "\n";
}

echo "</pre>";
