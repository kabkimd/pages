<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit: <?php echo htmlspecialchars($displayName); ?></title>
  <link rel="stylesheet" href="/codemirror/lib/codemirror.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css">
  <style>
    html, body { margin: 0; height: 100%; display: flex; flex-direction: column; }
    header { background: #2c3e50; color: white; padding: 0.5rem 1rem; }
    #page { flex: 1; display: flex; overflow: hidden; }
    #sidebar { width: 250px; border-right: 1px solid #ccc; overflow-y: auto; }
    #editorArea { flex: 1; display: flex; flex-direction: column; }
    #tabs { background: #f0f0f0; padding: 0.3rem; display: flex; }
    .tab { margin-right: 0.5rem; padding: 0.2rem 0.5rem; cursor: pointer; background: #ddd; }
    .tab.active { background: #bbb; font-weight: bold; }
    #editorWrapper { flex: 1; position: relative; }
    .CodeMirror { height: 100%; }
  </style>
</head>
<body>
<header><h1>Edit <?php echo htmlspecialchars($displayName); ?>'s Files</h1></header>
<div id="page">
  <div id="sidebar">
    <div id="jstree"></div>
    <div id="uploadArea" style="padding: 0.5rem; border-top: 1px solid #ccc;">Drag files here to upload</div>
  </div>
  <div id="editorArea">
    <div id="tabs"></div>
    <div id="editorWrapper"></div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"></script>
<script src="/codemirror/lib/codemirror.js"></script>
<script src="/codemirror/mode/javascript/javascript.js"></script>
<script src="/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="/codemirror/mode/css/css.js"></script>
<script src="/codemirror/mode/xml/xml.js"></script>
<script src="/codemirror/mode/markdown/markdown.js"></script>
<script src="/codemirror/mode/json/json.js"></script>
<script>
let editors = {};
let currentTab = null;
const username = <?php echo json_encode($username); ?>;

$(function() {
  initTree();
  initUpload();
});

function initTree() {
  $('#jstree').jstree({
    core: { data: { url: `/files_api.php?action=tree&username=${username}` }, check_callback: true },
    plugins: ['contextmenu', 'dnd', 'types'],
    types: { folder: { icon: 'jstree-folder' }, file: { icon: 'jstree-file' } },
    contextmenu: { items: customMenu }
  }).on('select_node.jstree', (e, data) => {
    if (data.node.type === 'file') loadFile(data.node.id);
  });
}

function customMenu(node) {
  return {
    create: { label: 'New File', action: () => createItem(node, false) },
    folder: { label: 'New Folder', action: () => createItem(node, true) },
    rename: { label: 'Rename', action: () => $('#jstree').jstree(true).edit(node) },
    delete: { label: 'Delete', action: () => deleteItem(node) }
  };
}

function createItem(node, isDir) {
  const name = prompt(`New ${isDir ? 'folder' : 'file'} name:`);
  if (!name) return;
  $.post('/files_api.php?action=create&username='+username, JSON.stringify({
    path: node.id,
    name,
    isDirectory: isDir
  }), () => $('#jstree').jstree(true).refresh()).fail(alert);
}

function deleteItem(node) {
  if (!confirm('Delete?')) return;
  $.post('/files_api.php?action=delete&username='+username, JSON.stringify({ path: node.id }), () => $('#jstree').jstree(true).refresh()).fail(alert);
}

function loadFile(relPath) {
  if (editors[relPath]) return switchTab(relPath);
  $.get(`/files_api.php?action=content&username=${username}&path=${encodeURIComponent(relPath)}`, content => {
    const mode = detectMode(relPath);
    const wrapper = $('<div style="height:100%"></div>').appendTo('#editorWrapper').hide();
    const cm = CodeMirror(wrapper[0], { value: content, mode, lineNumbers: true });
    editors[relPath] = { cm, wrapper };
    const tab = $('<div class="tab"></div>').text(relPath).appendTo('#tabs').click(() => switchTab(relPath));
    switchTab(relPath);
  });
}

function switchTab(relPath) {
  $('.tab').removeClass('active');
  $('#tabs').find(`.tab:contains(${relPath})`).addClass('active');
  for (const path in editors) editors[path].wrapper.hide();
  editors[relPath].wrapper.show();
  currentTab = relPath;
}

function detectMode(path) {
  const ext = path.split('.').pop().toLowerCase();
  if (['js', 'mjs', 'jsx'].includes(ext)) return 'javascript';
  if (ext === 'html') return 'htmlmixed';
  if (ext === 'css') return 'css';
  if (ext === 'json') return 'application/json';
  if (['md', 'markdown'].includes(ext)) return 'markdown';
  return 'plaintext';
}

function initUpload() {
  $('#uploadArea').on('dragover', e => e.preventDefault()).on('drop', e => {
    e.preventDefault();
    const file = e.originalEvent.dataTransfer.files[0];
    const form = new FormData();
    form.append('file', file);
    fetch(`/files_api.php?action=upload&username=${username}`, { method: 'POST', body: form })
      .then(r => r.json()).then(r => r.success && $('#jstree').jstree(true).refresh());
  });
}
</script>
</body>
</html>
