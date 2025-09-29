<?php
declare(strict_types=1);

require __DIR__ . '/../lib/unsubscribe_utils.php';

unsub_send_security_headers();
header('Cache-Control: no-store');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    exit;
}

$config = unsub_get_config();
$jsonPath = $config['unsub_json_path'];
$secret = $config['unsub_secret'];
$ttl = (int) $config['api_sig_ttl_seconds'];

$params = array_change_key_case($_GET, CASE_LOWER);
$email = unsub_normalize_email($params['email'] ?? null);
$tsParam = $params['ts'] ?? null;
$sig = isset($params['sig']) ? trim((string) $params['sig']) : '';

if ($email === null) {
    unsub_send_json(400, ['error' => 'invalid_email']);
}

if ($tsParam === null || !preg_match('/^\d+$/', (string) $tsParam)) {
    unsub_send_json(400, ['error' => 'invalid_timestamp']);
}

$timestamp = (int) $tsParam;
$now = unsub_now();
if ($ttl > 0 && abs($now - $timestamp) > $ttl) {
    unsub_send_json(401, ['error' => 'signature_expired']);
}

if ($sig === '') {
    unsub_send_json(401, ['error' => 'missing_signature']);
}

$expectedSig = hash_hmac('sha256', $email . '|' . $timestamp, $secret);
if (!unsub_hash_equals($expectedSig, $sig)) {
    unsub_send_json(403, ['error' => 'invalid_signature']);
}

$emails = unsub_load_emails($jsonPath);
if (in_array($email, $emails, true)) {
    unsub_send_json(200, ['unsubscribed' => true]);
}

unsub_send_json(404, ['unsubscribed' => false]);
