<?php
// files_api.php — PHP backend for jsTree + CodeMirror file editor

// Base directory for this user’s files:
$basePath = __DIR__ . '/pages/' . sanitizeUsername();

$action = $_GET['action'] ?? '';
switch ($action) {

  // ─── Build flat tree ─────────────────────────────────────────
  case 'tree':
    header('Content-Type: application/json');
    echo json_encode(buildFlatTree($basePath));
    exit;

  // ─── Load file content ───────────────────────────────────────
  case 'content':
    $path = realpath($basePath . '/' . ($_GET['path'] ?? ''));
    if (isValidPath($path, $basePath) && is_file($path)) {
      header('Content-Type: text/plain; charset=UTF-8');
      echo file_get_contents($path);
    } else {
      http_response_code(400);
      header('Content-Type: application/json');
      echo json_encode(['error' => 'Invalid path']);
    }
    exit;

  // ─── Save file ───────────────────────────────────────────────
  case 'save':
    $data = json_decode(file_get_contents('php://input'), true);
    $path = realpath($basePath . '/' . ($data['path'] ?? ''));
    if (isValidPath($path, $basePath)) {
      file_put_contents($path, $data['content'] ?? '');
      header('Content-Type: application/json');
      echo json_encode(['success' => true]);
    } else {
      http_response_code(400);
      header('Content-Type: application/json');
      echo json_encode(['error' => 'Invalid path']);
    }
    exit;

  // ─── Create file or folder ───────────────────────────────────
  case 'create':
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['name'], $data['path'], $data['isDirectory'])) {
      http_response_code(400);
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'error' => 'Invalid payload']);
      exit;
    }

    // Normalize client path
    $clientPath = trim($data['path'], '/');
    $parentDir  = $basePath . ($clientPath === '' ? '' : "/{$clientPath}");
    $realParent = realpath($parentDir);

    // Security check
    if ($realParent === false || strpos($realParent, realpath($basePath)) !== 0) {
      http_response_code(400);
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'error' => 'Invalid parent path']);
      exit;
    }

    // Create
    $newName = basename($data['name']);
    $newPath = "{$realParent}/{$newName}";
    if ($data['isDirectory']) {
      if (!file_exists($newPath)) mkdir($newPath, 0755, true);
    } else {
      if (!file_exists($newPath)) file_put_contents($newPath, '');
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;

  // ─── Rename ──────────────────────────────────────────────────
  case 'rename':
    $data    = json_decode(file_get_contents('php://input'), true);
    $oldPath = realpath($basePath . '/' . ($data['oldPath'] ?? ''));
    $newPath = $basePath . '/' . ($data['newPath'] ?? '');

    if (
      isValidPath($oldPath, $basePath) &&
      isValidPath(realpath(dirname($newPath)), $basePath)
    ) {
      rename($oldPath, $newPath);
      header('Content-Type: application/json');
      echo json_encode(['success' => true]);
    } else {
      http_response_code(400);
      header('Content-Type: application/json');
      echo json_encode(['error' => 'Invalid path']);
    }
    exit;

  // ─── Delete ──────────────────────────────────────────────────
  case 'delete':
    $data = json_decode(file_get_contents('php://input'), true);
    $path = realpath($basePath . '/' . ($data['path'] ?? ''));
    if (isValidPath($path, $basePath)) {
      if (is_dir($path)) {
        rmdirRecursive($path);
      } else {
        unlink($path);
      }
      header('Content-Type: application/json');
      echo json_encode(['success' => true]);
    } else {
      http_response_code(400);
      header('Content-Type: application/json');
      echo json_encode(['error' => 'Invalid path']);
    }
    exit;

  // ─── Upload ──────────────────────────────────────────────────
  case 'upload':
    if (!isset($_FILES['file'])) {
      http_response_code(400);
      header('Content-Type: application/json');
      echo json_encode(['error' => 'No file uploaded']);
      exit;
    }
    $name   = basename($_FILES['file']['name']);
    $target = $basePath . '/' . $name;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
      header('Content-Type: application/json');
      echo json_encode(['success' => true, 'path' => $name]);
    } else {
      http_response_code(500);
      header('Content-Type: application/json');
      echo json_encode(['error' => 'Upload failed']);
    }
    exit;

  // ─── Unknown ─────────────────────────────────────────────────
  default:
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unknown action']);
    exit;
}

// ─── Helper: build a flat array for jsTree ───────────────────
function buildFlatTree(string $dir, string $parent = '#', string $rel = ''): array {
  $items = [];
  foreach (scandir($dir) as $entry) {
    if ($entry === '.' || $entry === '..') continue;
    $full    = "$dir/$entry";
    $relPath = $rel === '' ? $entry : "$rel/$entry";
    $id      = $relPath;
    if (is_dir($full)) {
      $items[] = ['id' => $id, 'parent' => $parent, 'text' => $entry, 'type' => 'folder'];
      $items = array_merge($items, buildFlatTree($full, $id, $relPath));
    } else {
      $items[] = ['id' => $id, 'parent' => $parent, 'text' => $entry, 'type' => 'file'];
    }
  }
  return $items;
}

// ─── Sanitize username from query string ────────────────────
function sanitizeUsername(): string {
  $u = trim($_GET['username'] ?? '', '/');
  return preg_replace('/[^a-zA-Z0-9_-]/', '', explode('/', $u)[0]);
}

// ─── Ensure a path lives inside the base folder ──────────────
function isValidPath(?string $path, string $base): bool {
  if (!$path) return false;
  return strpos(realpath($path), realpath($base)) === 0;
}

// ─── Recursively remove a directory ─────────────────────────
function rmdirRecursive(string $dir): void {
  foreach (array_diff(scandir($dir), ['.', '..']) as $f) {
    $p = "$dir/$f";
    is_dir($p) ? rmdirRecursive($p) : unlink($p);
  }
  rmdir($dir);
}
