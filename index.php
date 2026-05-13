<?php
// Root redirector for AppServ installations.
if (!file_exists(__DIR__ . '/storage/installed.lock') && !file_exists(__DIR__ . '/config/installed.php')) {
    header('Location: /install/');
    exit;
}

header('Location: /public/');
exit;
