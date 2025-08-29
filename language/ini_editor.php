<?php
    // File: public/config-ini-editor.php
    // Purpose: Secure, single-file PHP editor for a specific .ini file with backups and basic validation.

    declare(strict_types=1);

    /**
     * =========================
     * Configuration (EDIT ME)
     * =========================
     * SECURITY NOTE: This page should be protected (IP allowlist, VPN, or HTTP Auth).
     */
    //define("INI_FILE_PATH", $module->getUrl('language/notifications.ini'));            // <-- set your .ini path
    const INI_FILE_PATH = __DIR__ . '/notifications.ini'; // <-- set your .ini path
    const BACKUP_DIR    = __DIR__ . '/ini_backups';        // backups directory (ensure writable)
    const MAX_BYTES     = 1024 * 1024;                     // 1 MB safeguard
    const VALIDATE_SYNTAX_BEFORE_SAVE = true;              // parse .ini for basic syntax check
    const REQUIRE_CREATE_BACKUP       = true;              // always create backup before saving

    // Optional built-in HTTP Basic Auth (set to true and change creds). Prefer server-level auth.
    const BASIC_AUTH_ENABLED = false;
    const BASIC_AUTH_USER    = 'admin';
    const BASIC_AUTH_PASS    = 'change-me';

    // ---------------------
    // Bootstrap & Helpers
    // ---------------------
    @ini_set('display_errors', '0');
    @ini_set('log_errors', '1');
    @ini_set('error_log', __DIR__ . '/config-ini-editor.error.log');

    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    mb_internal_encoding('UTF-8');

    if (BASIC_AUTH_ENABLED) {
        $u = $_SERVER['PHP_AUTH_USER'] ?? '';
        $p = $_SERVER['PHP_AUTH_PW']   ?? '';
        if ($u !== BASIC_AUTH_USER || !hash_equals(BASIC_AUTH_PASS, $p)) {
            header('WWW-Authenticate: Basic realm="Config Editor"');
            http_response_code(401);
            echo 'Authentication required';
            exit;
        }
    }

    // Ensure target file exists (create empty if missing)
    if (!file_exists(INI_FILE_PATH)) {
        $dir = dirname(INI_FILE_PATH);
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        @touch(INI_FILE_PATH);
    }

    if (!is_readable(INI_FILE_PATH)) {
        http_response_code(500);
        echo 'INI file is not readable: ' . htmlspecialchars(INI_FILE_PATH, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        exit;
    }

    if (!is_dir(BACKUP_DIR)) {
        @mkdir(BACKUP_DIR, 0770, true);
    }

    function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

    function csrf_token(): string {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    function verify_csrf(string $token): void {
        $ok = isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
        if (!$ok) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            exit;
        }
    }

    function bytes_to_human(int $bytes): string {
        $units = ['B','KB','MB','GB'];
        $i = 0;
        $n = $bytes;
        while ($n >= 1024 && $i < count($units)-1) { $n /= 1024; $i++; }
        return sprintf('%.2f %s', $n, $units[$i]);
    }

    function list_backups(): array {
        $pattern = BACKUP_DIR . '/' . basename(INI_FILE_PATH) . '.bak-*';
        $files = glob($pattern) ?: [];
        rsort($files, SORT_NATURAL);
        return array_slice($files, 0, 10); // show latest 10
    }

    function create_backup(string $current): ?string {
        if (!is_dir(BACKUP_DIR)) { return null; }
        $stamp = date('Ymd-His');
        $name  = basename(INI_FILE_PATH) . '.bak-' . $stamp;
        $path  = BACKUP_DIR . '/' . $name;
        $ok = @copy(INI_FILE_PATH, $path);
        return $ok ? $path : null;
    }

    function atomic_write(string $target, string $data): bool {
        $dir = dirname($target);
        $tmp = tempnam($dir, 'ini-');
        if ($tmp === false) { return false; }

        $perms = @fileperms($target) ?: 0640;
        $owner = @fileowner($target) ?: null;
        $group = @filegroup($target) ?: null;

        $fp = @fopen($tmp, 'wb');
        if (!$fp) { @unlink($tmp); return false; }

        // Ensure trailing newline for POSIX friendliness
        if ($data !== '' && !str_ends_with($data, "\n")) { $data .= "\n"; }

        if (flock($fp, LOCK_EX)) {
            $bytes = fwrite($fp, $data);
            fflush($fp);
            flock($fp, LOCK_UN);
        } else {
            fclose($fp);
            @unlink($tmp);
            return false;
        }
        fclose($fp);

        @chmod($tmp, $perms & 0777);
        if ($owner !== null && function_exists('posix_geteuid') && posix_geteuid() === 0) {
            @chown($tmp, $owner);
            @chgrp($tmp, $group ?: $owner);
        }

        return @rename($tmp, $target);
    }

    // ---------------------
    // Actions
    // ---------------------
    $errors = [];
    $notices = [];

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $action = $_POST['action'] ?? '';
        $token  = $_POST['csrf']   ?? '';
        verify_csrf($token);

        if ($action === 'save') {
            $content = $_POST['content'] ?? '';
            if (!is_string($content)) { $content = ''; }
            if (strlen($content) > MAX_BYTES) {
                $errors[] = 'Content too large: limit is ' . bytes_to_human(MAX_BYTES);
            }

            if (VALIDATE_SYNTAX_BEFORE_SAVE) {
                // validate basic syntax without sections requirement
                $parsed = @parse_ini_string($content, true, INI_SCANNER_TYPED);
                if ($parsed === false) {
                    $errors[] = 'Syntax check failed: please verify INI structure (sections, keys, quotes).';
                }
            }

            if (!$errors) {
                $backupPath = null;
                if (REQUIRE_CREATE_BACKUP) {
                    $backupPath = create_backup(file_get_contents(INI_FILE_PATH));
                    if (!$backupPath) { $errors[] = 'Failed to create backup before saving.'; }
                }
            }

            if (!$errors) {
                $ok = atomic_write(INI_FILE_PATH, $content);
                if ($ok) {
                    $notices[] = 'Saved successfully' . ($backupPath ? ' (backup: ' . h(basename($backupPath)) . ')' : '') . '.';
                } else {
                    $errors[] = 'Failed to write changes. Check file permissions and ownership.';
                }
            }
        } elseif ($action === 'restore' && isset($_POST['backup'])) {
            $backup = (string)$_POST['backup'];
            $allowed = list_backups();
            $full = null;
            foreach ($allowed as $b) { if (basename($b) === basename($backup)) { $full = $b; break; } }
            if ($full && is_readable($full)) {
                $data = (string)file_get_contents($full);
                if (atomic_write(INI_FILE_PATH, $data)) {
                    $notices[] = 'Restored from backup: ' . h(basename($full));
                } else {
                    $errors[] = 'Failed to restore backup.';
                }
            } else {
                $errors[] = 'Invalid backup selection.';
            }
        }
    }

    // ---------------------
    // Load current file
    // ---------------------
    $current = (string)file_get_contents(INI_FILE_PATH);
    $filesize = strlen($current);
    $mtime = filemtime(INI_FILE_PATH) ?: time();
    $backups = list_backups();
    $token = csrf_token();

    // ---------------------
    // Render
    // ---------------------
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>INI Editor</title>
    <style>
        :root { --gap: 12px; --font: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
        body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Arial, "Apple Color Emoji", "Segoe UI Emoji"; background:#f7f7f7; }
        header { padding:16px 20px; background:#fff; border-bottom:1px solid #e5e5e5; position:sticky; top:0; z-index:10; }
        main { padding: 20px; max-width: 1100px; margin: 0 auto; }
        .wrap { display:grid; gap: var(--gap); }
        .row { display:flex; flex-wrap:wrap; gap: var(--gap); align-items:center; }
        textarea { width:100%; min-height: 60vh; font-family: var(--font); font-size:14px; line-height:1.45; padding:12px; border:1px solid #ddd; border-radius:10px; background:#fff; }
        .card { background:#fff; border:1px solid #e5e5e5; border-radius:12px; padding:16px; }
        .btn { appearance: none; border:1px solid #ccc; background:#fff; padding:10px 14px; border-radius:10px; cursor:pointer; }
        .btn.primary { border-color:#333; background:#111; color:#fff; }
        .btn.danger { border-color:#b30000; color:#b30000; background:#fff; }
        .chips { display:flex; gap:8px; flex-wrap:wrap; }
        .chip { background:#111; color:#fff; padding:6px 10px; border-radius:999px; font-size:12px; }
        .msg { padding: 12px 14px; border-radius: 10px; }
        .msg.error { background:#ffecec; border:1px solid #ffb3b3; }
        .msg.notice { background:#ecfff1; border:1px solid #b3ffd0; }
        footer { color:#666; font-size:12px; padding:16px 20px; }
        select { padding:8px; border-radius:8px; border:1px solid #ddd; background:#fff; }
        code { background:#f2f2f2; padding:2px 6px; border-radius:6px; }
    </style>
</head>
<body>
<header>
    <div class="row">
        <h2 style="margin:0">INI Editor</h2>
        <div class="chips">
            <span class="chip">File: <?= h(INI_FILE_PATH) ?></span>
            <span class="chip">Size: <?= h(bytes_to_human($filesize)) ?></span>
            <span class="chip">Modified: <?= h(date('Y-m-d H:i:s', $mtime)) ?></span>
        </div>
    </div>
</header>
<main class="wrap">
    <?php foreach ($errors as $e): ?>
        <div class="msg error">❌ <?= h($e) ?></div>
    <?php endforeach; ?>
    <?php foreach ($notices as $n): ?>
        <div class="msg notice">✅ <?= h($n) ?></div>
    <?php endforeach; ?>

    <form method="post" class="card" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= h($token) ?>">
        <input type="hidden" name="action" value="save">
        <label for="content"><strong>Edit contents of <?= h(basename(INI_FILE_PATH)) ?></strong></label>
        <textarea id="content" name="content" spellcheck="false" wrap="off"><?= h($current) ?></textarea>
        <div class="row" style="justify-content: space-between; margin-top: 10px;">
            <div>
                <?php if (VALIDATE_SYNTAX_BEFORE_SAVE): ?>
                    <span title=".ini syntax will be checked before saving">🔎 Syntax check enabled</span>
                <?php else: ?>
                    <span>⚠️ Syntax check disabled</span>
                <?php endif; ?>
                <?php if (REQUIRE_CREATE_BACKUP): ?>
                    <span style="margin-left:10px;" title="A backup will be created before saving">💾 Backup on save</span>
                <?php endif; ?>
            </div>
            <div class="row">
                <button class="btn" type="reset">Reset</button>
                <button class="btn primary" type="submit">Save Changes</button>
            </div>
        </div>
    </form>

    <section class="card">
        <h3 style="margin-top:0">Recent backups</h3>
        <?php if ($backups): ?>
            <form method="post" class="row" onsubmit="return confirm('Restore selected backup? This will overwrite the current file.');">
                <input type="hidden" name="csrf" value="<?= h($token) ?>">
                <input type="hidden" name="action" value="restore">
                <select name="backup" required>
                    <?php foreach ($backups as $b): ?>
                        <option value="<?= h(basename($b)) ?>"><?= h(basename($b)) ?> (<?= h(bytes_to_human((int)@filesize($b))) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button class="btn danger" type="submit">Restore</button>
                <a class="btn" href="<?= h('ini_backups/' . h(basename(INI_FILE_PATH))) ?>" download style="display:none"></a>
            </form>
            <ul>
                <?php foreach ($backups as $b): ?>
                    <li><code><?= h(basename($b)) ?></code> — <a href="<?= h('ini_backups/' . rawurlencode(basename($b))) ?>" download>Download</a></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No backups yet.</p>
        <?php endif; ?>
    </section>

    <section class="card">
        <h3 style="margin-top:0">Tips</h3>
        <ul>
            <li>Protect this page with HTTP Basic Auth or behind a VPN.</li>
            <li>Ensure the PHP process user can read/write <code><?= h(INI_FILE_PATH) ?></code> and <code><?= h(BACKUP_DIR) ?></code>.</li>
            <li>Syntax check uses PHP's <code>parse_ini_string()</code>; it won't catch all edge cases.</li>
            <li>Edits are saved atomically; a timestamped backup is created first.</li>
        </ul>
    </section>
</main>
<footer>
    <div>© <?= date('Y') ?> INI Editor • Keep this file outside web root or restrict access.</div>
</footer>
</body>
</html>

