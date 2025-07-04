<?php
$filePath = __DIR__ . "/students/{$username}.html";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newCode = $_POST['code'] ?? '';
    file_put_contents($filePath, $newCode);
    header("Location: /{$username}");
    exit;
}

$existingCode = file_exists($filePath) ? file_get_contents($filePath) : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit <?php echo htmlspecialchars($displayName); ?>'s Page</title>
    <link rel="stylesheet" href="/codemirror/lib/codemirror.css">
    <script src="/codemirror/lib/codemirror.js"></script>
    <script src="/codemirror/mode/htmlmixed/htmlmixed.js"></script>
    <script src="/codemirror/mode/xml/xml.js"></script>
</head>
<body>
    <h1>Edit <?php echo htmlspecialchars($displayName); ?>'s Page</h1>
    <form method="post">
        <textarea id="editor" name="code"><?php echo htmlspecialchars($existingCode); ?></textarea>
        <br>
        <button type="submit">Save</button>
    </form>

    <script>
    var editor = CodeMirror.fromTextArea(document.getElementById("editor"), {
        lineNumbers: true,
        mode: "htmlmixed"
    });
    </script>
</body>
</html>
