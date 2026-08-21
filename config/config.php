<?php

declare(strict_types=1);

const APP_NAME = 'Laboratorio Leucaena';
const APP_BRAND = 'Laboratorio de Análisis de Semillas';
const APP_SUBTITLE = 'LEUCAENA';
const DB_HOST = 'localhost';
const DB_NAME = 'laboratorio';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function require_auth(): void
{
    if (!is_logged_in()) {
        header('Location: /index.php');
        exit;
    }
}

function current_empresa_id(): int
{
    return (int) ($_SESSION['user']['id_empresa'] ?? 0);
}

function is_superadmin(): bool
{
    return is_logged_in() && (bool) ($_SESSION['user']['es_superadmin'] ?? false);
}

function require_superadmin(): void
{
    if (!is_superadmin()) {
        header('Location: /index.php');
        exit;
    }
}

const STORAGE_BASE_DIR = __DIR__ . '/../storage';

function empresa_storage_dir(int $idEmpresa): string
{
    $dir = STORAGE_BASE_DIR . '/empresa_' . $idEmpresa;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return $dir;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

const SETTINGS_FILE = __DIR__ . '/settings.json';

function get_app_settings(): array
{
    if (!is_file(SETTINGS_FILE)) {
        return [];
    }

    $contents = file_get_contents(SETTINGS_FILE);
    $decoded = json_decode((string) $contents, true);

    return is_array($decoded) ? $decoded : [];
}

function get_app_setting(string $key, string $default = ''): string
{
    $settings = get_app_settings();

    return isset($settings[$key]) ? (string) $settings[$key] : $default;
}

function save_app_setting(string $key, string $value): void
{
    $settings = get_app_settings();
    $settings[$key] = $value;
    file_put_contents(SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
