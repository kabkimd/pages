<?php
$request = trim($_SERVER['REQUEST_URI'], '/');

// If homepage
if ($request == '') {
    // Show landing page with list of Pokémon
    include 'landing.php';
    exit;
}

// Sanitize the request (allow only letters/numbers/-/_)
$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $request);

$filePath = __DIR__ . "/students/{$safeName}.html";

if (file_exists($filePath)) {
    $content = file_get_contents($filePath);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo htmlspecialchars($safeName); ?>'s Page</title>
    </head>
    <body>
        <?php echo $content; ?>
    </body>
    </html>
    <?php
} else {
    http_response_code(404);
    echo "<h1>404 - Page not found</h1>";
}
?>
