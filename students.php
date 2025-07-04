<?php
$name = $_GET['name'] ?? 'Unknown Student';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($name); ?>'s Page</title>
</head>
<body>
    <h1><?php echo htmlspecialchars($name); ?>'s Page</h1>
    <p>This is where the editor or public render page will go.</p>
    <p><a href="index.php">← Back to Index</a></p>
</body>
</html>
