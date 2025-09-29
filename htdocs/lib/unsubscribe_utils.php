<?php
declare(strict_types=1);

/**
 * Shared helpers for unsubscribe handling between endpoints.
 */

/**
 * Load unsubscribe configuration from the shared JSON file.
 *
 * @return array<string,mixed>
 */
function unsub_get_config(): array
{
    static $config;
    if (is_array($config)) {
        return $config;
    }

    $configFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app_config.json';
    if (!is_file($configFile)) {
        unsub_fatal('Konfigurationsdatei fehlt: ' . $configFile);
    }

    $raw = file_get_contents($configFile);
    if ($raw === false) {
        unsub_fatal('Konfiguration konnte nicht gelesen werden.');
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        unsub_fatal('Konfiguration ist ungueltig (kein JSON-Objekt).');
    }

    $required = [
        'base_url',
        'unsub_secret',
        'unsub_json_path',
        'unsub_mailto',
        'sender_name',
        'sender_mail',
        'api_sig_ttl_seconds',
    ];
    foreach ($required as $key) {
        if (!array_key_exists($key, $data)) {
            unsub_fatal('Konfigurationsschluessel fehlt: ' . $key);
        }
    }

    if (!is_string($data['base_url']) || stripos($data['base_url'], 'https://') !== 0) {
        unsub_fatal('BASE_URL muss mit https:// beginnen.');
    }

    $data['unsub_secret'] = (string) $data['unsub_secret'];
    $data['unsub_mailto'] = (string) $data['unsub_mailto'];
    $data['sender_name'] = (string) $data['sender_name'];
    $data['sender_mail'] = (string) $data['sender_mail'];
    $data['api_sig_ttl_seconds'] = (int) $data['api_sig_ttl_seconds'];

    $jsonPath = (string) $data['unsub_json_path'];
    if (!unsub_is_absolute_path($jsonPath)) {
        $jsonPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($jsonPath, '\\/'));
    }
    $data['unsub_json_path'] = $jsonPath;

    $config = $data;
    return $config;
}

/**
 * Detect whether the given path is absolute for Windows or POSIX.
 */
function unsub_is_absolute_path(string $path): bool
{
    if ($path === '') {
        return false;
    }
    if ($path[0] === '/' || $path[0] === '\\') {
        return true;
    }
    return (bool) preg_match('#^[A-Za-z]:[\\/]#', $path);
}

/**
 * Send strict security headers (idempotent) for unsubscribe endpoints.
 */
function unsub_send_security_headers(): void
{
    header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
    header('Referrer-Policy: same-origin');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
}

/**
 * Ensure storage folder and JSON file exist.
 */
function unsub_ensure_storage(string $jsonPath): void
{
    $directory = dirname($jsonPath);
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
            unsub_fatal('Verzeichnis konnte nicht erstellt werden: ' . $directory);
        }
    }

    if (!is_file($jsonPath)) {
        unsub_write_emails($jsonPath, []);
    }
}

/**
 * Load all unsubscribed emails as an array of lowercase strings.
 *
 * @return array<int,string>
 */
function unsub_load_emails(string $jsonPath): array
{
    unsub_ensure_storage($jsonPath);
    $raw = file_get_contents($jsonPath);
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }

    $emails = [];
    foreach ($data as $value) {
        if (is_string($value) && $value !== '') {
            $emails[] = strtolower($value);
        }
    }

    return array_values(array_unique($emails));
}

/**
 * Persist unsubscribe list atomically.
 *
 * @param array<int,string> $emails
 */
function unsub_write_emails(string $jsonPath, array $emails): void
{
    $directory = dirname($jsonPath);
    $tmpFile = tempnam($directory, 'unsub_');
    if ($tmpFile === false) {
        unsub_fatal('Temporare Datei konnte nicht erstellt werden.');
    }

    $json = json_encode(array_values(array_unique($emails)), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        @unlink($tmpFile);
        unsub_fatal('JSON konnte nicht serialisiert werden.');
    }

    if (file_put_contents($tmpFile, $json) === false) {
        @unlink($tmpFile);
        unsub_fatal('Zwischenspeichern der JSON-Datei fehlgeschlagen.');
    }

    if (!rename($tmpFile, $jsonPath)) {
        @unlink($tmpFile);
        unsub_fatal('JSON-Datei konnte nicht aktualisiert werden.');
    }
}

/**
 * Add an email to the unsubscribe list.
 */
function unsub_add_email(string $email, string $jsonPath): bool
{
    $email = strtolower($email);
    $emails = unsub_load_emails($jsonPath);
    if (in_array($email, $emails, true)) {
        return false;
    }

    $emails[] = $email;
    unsub_write_emails($jsonPath, $emails);
    return true;
}

/**
 * Compute the unsubscribe token for an email.
 */
function unsub_hmac_token(string $email, string $secret): string
{
    return hash_hmac('sha256', strtolower($email), $secret);
}

/**
 * JSON response helper.
 *
 * @param array<string,mixed> $payload
 */
function unsub_send_json(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Fatal error helper that returns HTTP 500 and terminates the script.
 */
function unsub_fatal(string $message): void
{
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}

/**
 * Normalize email input (trim and lowercase) and validate basic structure.
 */
function unsub_normalize_email(?string $email): ?string
{
    if ($email === null) {
        return null;
    }
    $email = trim($email);
    if ($email === '') {
        return null;
    }
    $email = strtolower($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }
    return $email;
}

/**
 * Constant time comparison wrapper.
 */
function unsub_hash_equals(string $known, string $user): bool
{
    return hash_equals($known, $user);
}

/**
 * Current unix timestamp.
 */
function unsub_now(): int
{
    return time();
}
