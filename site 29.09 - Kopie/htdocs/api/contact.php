<?php
require __DIR__ . '/../lib/utils.php';
require __DIR__ . '/../lib/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'POST erforderlich'], 405);
}

function input_value(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

$name    = input_value('name');
$email   = input_value('email');
$phone   = input_value('phone');
$message = input_value('message');
$privacy = array_key_exists('privacy', $_POST)
    ? filter_var($_POST['privacy'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
    : true;

$errors = [];
if ($name === '') {
    $errors['name'] = 'Bitte einen Namen eintragen.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Bitte eine gueltige E-Mail-Adresse verwenden.';
}
if ($message === '' || mb_strlen($message) < 10) {
    $errors['message'] = 'Bitte eine Nachricht mit mindestens 10 Zeichen senden.';
}
if ($privacy !== true) {
    $errors['privacy'] = 'Bitte die Datenschutzerklaerung akzeptieren.';
}

if (!empty($errors)) {
    json_response(['ok' => false, 'errors' => $errors], 422);
}

$subject = 'Neue Kontaktanfrage ueber die Website';
$html = '<h3>Neue Kontaktanfrage</h3>'
      . '<p><strong>Name:</strong> ' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<br>'
      . '<strong>E-Mail:</strong> ' . htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<br>'
      . '<strong>Telefon:</strong> ' . htmlspecialchars($phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
      . '<p><strong>Nachricht:</strong><br>' . nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>'
      . '<hr><p style="font-size:12px;color:#666">'
      . 'Seite: ' . htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<br>'
      . 'IP: ' . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '<br>'
      . 'User-Agent: ' . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
$text = "Neue Kontaktanfrage\n\n"
      . "Name: $name\n"
      . "E-Mail: $email\n"
      . "Telefon: $phone\n\n"
      . "Nachricht:\n$message\n";

[$success, $error, $trace] = mailer_send($subject, $html, $text, $email, $name);

if ($success) {
    $payload = ['ok' => true, 'message' => 'Vielen Dank! Ihre Nachricht wurde gesendet.'];
    if ($trace) {
        $payload['trace'] = $trace;
    }
    json_response($payload);
}

$detail = $error ?? 'E-Mail Versand fehlgeschlagen. Bitte spaeter erneut versuchen.';
$response = ['ok' => false, 'error' => $detail];
if ($trace) {
    $response['trace'] = $trace;
}
json_response($response, 502);

?>
