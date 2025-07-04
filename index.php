<?php
require 'list.php';

$request = trim($_SERVER['REQUEST_URI'], '/');

if ($request === '') {
    include 'landing.php';
    exit;
}

$parts = explode('/', $request);
$username = preg_replace('/[^a-zA-Z0-9_-]/', '', $parts[0]);

// Check if username is valid
$validUser = false;
foreach ($students as $yearList) {
    foreach ($yearList as [$name, $user]) {
        if ($user === $username) {
            $validUser = true;
            $displayName = $name;
            break 2;
        }
    }
}

if (!$validUser) {
    http_response_code(404);
    echo "<h1>404 - Page not found</h1>";
    exit;
}

// Check if /edit
if (isset($parts[1]) && $parts[1] === 'edit') {
    include 'editor.php';
    exit;
}

// Render their index.html
$filePath = __DIR__ . "/pages/{$username}/index.html";
if (file_exists($filePath)) {
    $content = file_get_contents($filePath);
    echo "<!DOCTYPE html><html><head><title>{$displayName}'s Page</title></head><body>{$content}</body></html>";
} else {
    echo "<h1>This student hasn't published anything yet.</h1>";
}
?>
