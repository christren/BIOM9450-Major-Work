<?php
// Optional security router for PHP's built-in development server.
// Use with: php -S 127.0.0.1:8000 router.php (from project directory).
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (str_contains($path,'..') ||
    preg_match('#^/(?:app|config|database|docs|tools)(?:/|$)#i',$path) ||
    preg_match('/\.(?:sql|md|ini|htaccess|log|bak|env)$/i',$path) ||
    (str_starts_with($path,'/assets/uploads/') && !preg_match('/\.(?:jpg|jpeg|png|webp)$/i',$path))) {
    http_response_code(403);exit('403 — Forbidden');
}
if ($path === '/') {require __DIR__.'/index.php';return true;}
return false; // Let the built-in server serve PHP, stylesheets and JavaScript normally.
