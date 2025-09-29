<?php
// lib/mailer.php - zentraler Mailversand ueber PHPMailer mit sauberem Fallback

require_once __DIR__ . '/utils.php';

function mailer_bootstrap_phpmailer(): bool
{
    static $bootstrapped = null;
    if ($bootstrapped !== null) {
        return $bootstrapped;
    }

    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return $bootstrapped = true;
    }

    $autoloaders = [
        __DIR__ . '/../vendor/autoload.php',
        __DIR__ . '/phpmailer/vendor/autoload.php',
    ];

    foreach ($autoloaders as $file) {
        if (is_file($file)) {
            require_once $file;
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                return $bootstrapped = true;
            }
        }
    }

    $srcDir = __DIR__ . '/phpmailer/src';
    $required = [
        $srcDir . '/Exception.php',
        $srcDir . '/SMTP.php',
        $srcDir . '/PHPMailer.php',
    ];

    if (is_dir($srcDir)) {
        $allPresent = true;
        foreach ($required as $file) {
            if (!is_file($file)) {
                $allPresent = false;
                break;
            }
        }
        if ($allPresent) {
            require_once $required[0];
            require_once $required[1];
            require_once $required[2];
        }
    }

    return $bootstrapped = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
}

function mailer_trace_id(): string
{
    try {
        return bin2hex(random_bytes(5));
    } catch (Throwable $e) {
        return substr(sha1(uniqid('', true)), 0, 10);
    }
}

function mailer_log(string $message, array $context = []): void
{
    $config = cfg();
    $primaryDir = $config['STORAGE'] ?? dirname(__DIR__) . '/../storage';
    $logDir = $primaryDir;

    if (!is_dir($logDir) && !@mkdir($logDir, 0775, true)) {
        $fallbackDir = __DIR__ . '/../storage';
        if (!is_dir($fallbackDir) && !@mkdir($fallbackDir, 0775, true)) {
            $fallbackDir = sys_get_temp_dir();
        }
        $logDir = $fallbackDir;
    }

    $line = '[' . date('c') . '] ' . $message;
    if (!empty($context)) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $line .= PHP_EOL;

    if (@error_log($line, 3, rtrim($logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mail.log') === false) {
        @error_log($line);
    }
}

function mailer_normalize_address(array $config): array
{
    $fromEmail = $config['MAIL_FROM'] ?? '';
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL) && !empty($config['SMTP_USER']) && filter_var($config['SMTP_USER'], FILTER_VALIDATE_EMAIL)) {
        $fromEmail = $config['SMTP_USER'];
    }

    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $host = '';
        if (!empty($config['MAIL_TO']) && str_contains($config['MAIL_TO'], '@')) {
            $host = substr(strrchr($config['MAIL_TO'], '@'), 1);
        }
        if ($host === '' && !empty($_SERVER['SERVER_NAME'])) {
            $host = $_SERVER['SERVER_NAME'];
        }
        if ($host === '') {
            $host = 'localhost.localdomain';
        }
        $fromEmail = 'no-reply@' . $host;
    }

    $fromName = trim((string)($config['MAIL_FROM_NAME'] ?? ''));
    if ($fromName === '') {
        $fromName = 'Website';
    }

    return [$fromEmail, $fromName];
}

function mailer_send(string $subject, string $html, string $text = '', string $replyToEmail = '', string $replyToName = ''): array
{
    $config = cfg();
    ensure_storage();

    $traceId = mailer_trace_id();
    [$fromEmail, $fromName] = mailer_normalize_address($config);
    $replyToEmail = filter_var($replyToEmail, FILTER_VALIDATE_EMAIL) ? $replyToEmail : '';
    $replyToName  = $replyToName !== '' ? $replyToName : $replyToEmail;

    $to = $config['MAIL_TO'] ?? '';
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        mailer_log('Kontaktformular ohne gueltige Zieladresse aufgerufen', ['trace' => $traceId, 'configTo' => $to]);
        return [false, 'E-Mail-Zieladresse ist nicht korrekt konfiguriert.', $traceId];
    }

    $useSmtp = !empty($config['SMTP_HOST']);

    if (mailer_bootstrap_phpmailer()) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($fromEmail, $fromName);
            $mail->Sender = $fromEmail; // Envelope-From fuer IONOS
            $mail->addAddress($to);
            if ($replyToEmail) {
                $mail->addReplyTo($replyToEmail, $replyToName ?: $replyToEmail);
            }

            $debugBuffer = [];
            $mail->Debugoutput = function ($str, $level) use (&$debugBuffer) {
                $debugBuffer[] = '[' . $level . '] ' . trim($str);
            };

            if ($useSmtp) {
                $mail->isSMTP();
                $mail->Host       = $config['SMTP_HOST'];
                $mail->Port       = (int)($config['SMTP_PORT'] ?: 587);
                $mail->SMTPAutoTLS = true;
                $mail->SMTPAuth   = !empty($config['SMTP_USER']);
                if ($mail->SMTPAuth) {
                    $mail->Username   = $config['SMTP_USER'];
                    $mail->Password   = $config['SMTP_PASS'] ?? '';
                }
                $secure = strtolower((string)($config['SMTP_SECURE'] ?? 'tls'));
                if ($secure === 'ssl') {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    if (empty($config['SMTP_PORT'])) {
                        $mail->Port = 465;
                    }
                } elseif ($secure === 'tls') {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    if (empty($config['SMTP_PORT'])) {
                        $mail->Port = 587;
                    }
                } else {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                }
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer'       => true,
                        'verify_peer_name'  => true,
                        'allow_self_signed' => false,
                    ],
                ];
                $mail->SMTPDebug = 2; // Debug in Log puffern, keine Ausgabe an den Client
            }

            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = $html;
            $mail->AltBody = $text !== '' ? $text : trim(strip_tags(str_replace(['<br>','<br/>','<br />'], "\n", $html)));

            $mail->send();
            mailer_log('SMTP Versand erfolgreich', ['trace' => $traceId, 'to' => $to, 'replyTo' => $replyToEmail, 'debug' => $debugBuffer]);
            return [true, null, $traceId];
        } catch (Throwable $e) {
            $errorInfo = isset($mail) ? trim((string)$mail->ErrorInfo) : '';
            $context = [
                'trace'     => $traceId,
                'exception' => $e->getMessage(),
                'errorInfo' => $errorInfo,
                'host'      => $config['SMTP_HOST'] ?? '',
                'port'      => $config['SMTP_PORT'] ?? '',
                'secure'    => $config['SMTP_SECURE'] ?? '',
                'debug'     => $debugBuffer ?? [],
            ];
            mailer_log('SMTP Versand fehlgeschlagen', $context);
            $msg = $errorInfo !== '' ? $errorInfo : $e->getMessage();
            return [false, 'E-Mail Versand fehlgeschlagen: ' . $msg, $traceId];
        }
    }

    mailer_log('PHPMailer nicht verfuegbar, nutze mail()-Fallback', ['trace' => $traceId]);

    // Fallback auf native mail()-Funktion
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $encodedName = function_exists('mb_encode_mimeheader')
        ? mb_encode_mimeheader($fromName, 'UTF-8', 'B', "\r\n")
        : $fromName;
    $headers[] = 'From: ' . $encodedName . ' <' . $fromEmail . '>';
    if ($replyToEmail) {
        $headers[] = 'Reply-To: ' . $replyToEmail;
    }

    $additionalParameters = '-f ' . escapeshellarg($fromEmail);
    $result = @mail($to, $subject, $html, implode("\r\n", $headers), $additionalParameters);
    if ($result) {
        mailer_log('mail()-Fallback erfolgreich', ['trace' => $traceId, 'to' => $to]);
        return [true, null, $traceId];
    }

    $lastError = error_get_last();
    mailer_log('PHP mail()-Fallback fehlgeschlagen', ['trace' => $traceId, 'to' => $to, 'from' => $fromEmail, 'error' => $lastError]);
    return [false, 'mail()-Fallback konnte nicht senden', $traceId];
}

?>
