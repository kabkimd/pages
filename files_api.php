<?php
// files_api.php - PHP equivalent for your Node.js file API with full features

$basePath = __DIR__ . '/pages/' . sanitizeUsername();

// Simple routing based on query param 'action'
$action = $_GET['action'] ?? '';
switch ($action) {
    case 'tree':
        echo json_encode(getTree($basePath));
        break;
    case 'content':
        $path = realpath($basePath . '/' . $_GET['path']);
        if (isValidPath($path, $basePath) && is_file($path)) {
            echo file_get_contents($path);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid path']);
        }
        break;
    case 'save':
        $data = json_decode(file_get_contents('php://input'), true);
        $path = realpath($basePath . '/' . $data['path']);
        if (isValidPath($path, $basePath)) {
            file_put_contents($path, $data['content'] ?? '');
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid path']);
        }
        break;
    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        $newPath = $basePath . '/' . $data['path'] . '/' . $data['name'];
        if (!isValidPath(realpath(dirname($newPath)), $basePath)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid parent path']);
            exit;
        }
        if ($data['isDirectory']) {
            mkdir($newPath, 0755, true);
        } else {
            file_put_contents($newPath, '');
        }
        echo json_encode(['success' => true]);
        break;
    case 'rename':
        $data = json_decode(file_get_contents('php://input'), true);
        $oldPath = realpath($basePath . '/' . $data['oldPath']);
        $newPath = $basePath . '/' . $data['newPath'];
        if (isValidPath($oldPath, $basePath) && isValidPath(realpath(dirname($newPath)), $basePath)) {
            rename($oldPath, $newPath);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid path']);
        }
        break;
    case 'delete':
        $data = json_decode(file_get_contents('php://input'), true);
        $path = realpath($basePath . '/' . $data['path']);
        if (isValidPath($path, $basePath)) {
            if (is_dir($path)) {
                rmdirRecursive($path);
            } else {
                unlink($path);
            }
            echo json_encode(['success' => true]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid path']);
        }
        break;
    case 'upload':
        if (!isset($_FILES['file'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded']);
            exit;
        }
        $target = $basePath . '/' . basename($_FILES['file']['name']);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            echo json_encode(['success' => true, 'path' => basename($target)]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Upload failed']);
        }
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Unknown action']);
}

// Recursively build jsTree data
function getTree($dir, $rel = '') {
    $items = [];
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $fullPath = "$dir/$entry";
        $relPath = $rel === '' ? $entry : "$rel/$entry";
        if (is_dir($fullPath)) {
            $items[] = [
                'id' => $relPath,
                'text' => $entry,
                'type' => 'folder',
                'children' => getTree($fullPath, $relPath)
            ];
        } else {
            $items[] = [
                'id' => $relPath,
                'text' => $entry,
                'type' => 'file',
                'children' => false
            ];
        }
    }
    return $items;
}

// Sanitize username to prevent directory traversal
function sanitizeUsername() {
    $parts = explode('/', trim($_GET['username'] ?? '', '/'));
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $parts[0]);
}

// Ensure $path is within $basePath to avoid exploits
function isValidPath($path, $basePath) {
    return $path && strpos($path, realpath($basePath)) === 0;
}

// Recursively delete folder
function rmdirRecursive($dir) {
    foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
        $path = "$dir/$file";
        if (is_dir($path)) {
            rmdirRecursive($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}