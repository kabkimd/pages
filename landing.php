<?php
require 'list.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>⚠ WIP ⚠ | I/M/D Pages</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>I/M/D Index</h1>

    <?php foreach ($students as $year => $list): ?>
        <h2><?php echo htmlspecialchars($year); ?></h2>
        <ul>
            <?php foreach ($list as [$displayName, $username]): ?>
                <li>
                    <a href="/<?php echo urlencode($username); ?>">
                        <?php echo htmlspecialchars($displayName); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>