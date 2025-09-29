<?php
declare(strict_types=1);

require __DIR__ . '/lib/unsubscribe_utils.php';

function unsub_notify_admin(array $config, string $email): void
{
    $to = $config['unsub_mailto'] ?? '';
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $subject = 'Newsletter-Abmeldung: ' . $email;
    $lines = [
        'Eine Adresse wurde vom Newsletter abgemeldet.',
        'E-Mail: ' . $email,
        'Zeit: ' . gmdate('c'),
        'Quelle: One-Click Unsubscribe',
    ];
    $message = implode("
", $lines) . "
";

    $fromAddress = $config['sender_mail'] ?? '';
    $fromName = $config['sender_name'] ?? 'Mailer';
    $headers = '';
    if (filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
        $headers .= 'From: ' . sprintf('%s <%s>', $fromName, $fromAddress) . "

";
        $headers .= 'Reply-To: ' . $fromAddress . "

";
    }
    $headers .= 'X-Mailer: PHP/' . PHP_VERSION;

    @mail($to, $subject, $message, $headers);
}

unsub_send_security_headers();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'GET' && $method !== 'POST') {
    header('Allow: GET, POST');
    http_response_code(405);
    exit;
}

$config = unsub_get_config();
$jsonPath = $config['unsub_json_path'];
$secret = $config['unsub_secret'];

$allParams = array_change_key_case(array_merge($_GET, $_POST), CASE_LOWER);
$email = unsub_normalize_email($allParams['email'] ?? null);
$token = isset($allParams['token']) ? trim((string) $allParams['token']) : '';

if ($method === 'POST') {
    if ($email === null) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'invalid_email']);
        exit;
    }

    $expected = unsub_hmac_token($email, $secret);
    if ($token === '' || !unsub_hash_equals($expected, $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'invalid_token']);
        exit;
    }

    if (unsub_add_email($email, $jsonPath)) {
        unsub_notify_admin($config, $email);
    }
    http_response_code(204);
    exit;
}

// GET request (RFC guidance: always return 200 + text response)
http_response_code(200);
header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><title>Newsletter-Abmeldung</title>' .
     '<meta name="viewport" content="width=device-width, initial-scale=1"></head><body style="font-family:Arial,sans-serif;' .
     'margin:2rem;background:#f5f5f5;color:#212529;">';

echo '<main style="max-width:480px;margin:0 auto;background:#ffffff;border-radius:8px;padding:2rem;' .
     'box-shadow:0 8px 20px rgba(0,0,0,0.08);">';

echo '<h1 style="margin-top:0;font-size:1.6rem;">Abmeldung</h1>';

if ($email === null) {
    echo '<p>Die Abmelde-Anfrage ist unvollstaendig. Bitte verwenden Sie den vollstaendigen Link aus der E-Mail.</p>';
    echo '</main></body></html>';
    exit;
}

$expected = unsub_hmac_token($email, $secret);
if ($token === '' || !unsub_hash_equals($expected, $token)) {
    echo '<p>Der Abmelde-Link ist ungueltig oder abgelaufen. Bitte fordern Sie einen neuen Link an.</p>';
    echo '</main></body></html>';
    exit;
}

$added = unsub_add_email($email, $jsonPath);
if ($added) {
    unsub_notify_admin($config, $email);
    echo '<p>Ihre E-Mail-Adresse <strong>' . htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</strong> wurde erfolgreich abgemeldet.</p>';
} else {
    echo '<p>Ihre E-Mail-Adresse ist bereits abgemeldet. Es sind keine weiteren Schritte erforderlich.</p>';
}

echo '<p>Wenn Sie Fragen haben, kontaktieren Sie uns bitte unter <a href="mailto:' .
     htmlspecialchars($config['unsub_mailto'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">' .
     htmlspecialchars($config['unsub_mailto'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a>.</p>';

echo '</main></body></html>';
