<?php
// files_api.php - PHP backend for file management with jsTree compatibility

$basePath = __DIR__ . '/pages/' . sanitizeUsername();

$action = $_GET['action'] ?? '';
switch ($action) {
    case 'tree':
        echo json_encode(buildFlatTree($basePath));
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

function buildFlatTree($dir, $parent = '#', $rel = '') {
    $items = [];
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $fullPath = "$dir/$entry";
        $relPath = $rel === '' ? $entry : "$rel/$entry";
        $id = $relPath;
        if (is_dir($fullPath)) {
            $items[] = [ 'id' => $id, 'parent' => $parent, 'text' => $entry, 'type' => 'folder' ];
            $items = array_merge($items, buildFlatTree($fullPath, $id, $relPath));
        } else {
            $items[] = [ 'id' => $id, 'parent' => $parent, 'text' => $entry, 'type' => 'file' ];
        }
    }
    return $items;
}

function sanitizeUsername() {
    $parts = explode('/', trim($_GET['username'] ?? '', '/'));
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $parts[0]);
}

function isValidPath($path, $basePath) {
    return $path && strpos($path, realpath($basePath)) === 0;
}

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