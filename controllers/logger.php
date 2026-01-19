<?php
function security_log_file() {
    return __DIR__ . '/../data/security.log';
}

function log_security_event(string $tag, string $details) {
    $file = security_log_file();
    $line = sprintf("[%s] %s - %s\n", date('Y-m-d H:i:s'), $tag, $details);
    file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}
