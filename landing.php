<?php
// Sample Pokémon divided by "Year"
$students = [
    'Year 1' => ['Bulbasaur', 'Charmander', 'Squirtle', 'Pikachu', 'Eevee'],
    'Year 2' => ['Chikorita', 'Cyndaquil', 'Totodile', 'Togepi', 'Mareep'],
    'Year 3' => ['Treecko', 'Torchic', 'Mudkip', 'Ralts', 'Slakoth'],
    'Year 4' => ['Turtwig', 'Chimchar', 'Piplup', 'Shinx', 'Riolu']
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>⚠ WIP ⚠ | I/M/D Pages</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Student Index</h1>

    <?php foreach ($students as $year => $names): ?>
        <h2><?php echo htmlspecialchars($year); ?></h2>
        <ul>
            <?php foreach ($names as $name): ?>
                <li>
                    <a href="students.php?name=<?php echo urlencode($name); ?>">
                        <?php echo htmlspecialchars($name); ?>
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