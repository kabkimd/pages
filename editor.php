<?php
// editor.php — frontend for editing a user’s /pages/<username> contents
// Assumes $username, $displayName, $currentUserFullName are defined before including this file
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit <?= htmlspecialchars($displayName) ?>'s Files</title>

  <!-- Shared site CSS -->
  <link rel="stylesheet" href="/css/style.css">
  <!-- jsTree & CodeMirror CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css">
  <link rel="stylesheet" href="/codemirror/lib/codemirror.css">

  <style>
    /* Layout */
    #page { display:flex; height:calc(100vh - 70px); }
    #sidebar {
      width:280px; display:flex; flex-direction:column;
      border-right:1px solid #ccc;
    }
    #jstree { flex:1; overflow-y:auto; padding:0.5rem; }
    #upload-section {
      flex:0 0 10%; padding:0.5rem; background:#f9f9f9;
      border-top:1px solid #ccc;
    }
    #editorArea {
      flex:1; display:flex; flex-direction:column;
    }
    #controls {
      background:#f0f0f0; padding:0.5rem;
      display:flex; align-items:center; gap:0.5rem;
      border-bottom:1px solid #ccc;
    }
    #editorWrapper { flex:1; position:relative; }
    .CodeMirror { height:100%; }
  </style>
</head>
<body>
  <header>
    <div class="header-container">
      <h1>Edit <?= htmlspecialchars($displayName) ?>'s Files</h1>
      <div class="user-links">
        <span class="greeting">Hello, <strong><?= htmlspecialchars($currentUserFullName) ?></strong></span>
        <a href="/logout" class="btn">Logout</a>
      </div>
    </div>
  </header>

  <div id="page">
    <div id="sidebar">
      <div id="jstree"></div>
      <div id="upload-section">
        <h4>Upload File</h4>
        <input type="file" id="file-input">
        <button id="upload-btn">Upload</button>
      </div>
    </div>

    <div id="editorArea">
      <div id="controls">
        <button id="new-file-btn">New File</button>
        <button id="new-folder-btn">New Folder</button>
        <button id="save-btn">Save</button>
        <span id="current-file">No file loaded</span>
      </div>
      <div id="editorWrapper">
        <textarea id="editor"></textarea>
      </div>
    </div>
  </div>

  <!-- Libraries -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"></script>
  <script src="/codemirror/lib/codemirror.js"></script>
  <script src="/codemirror/mode/javascript/javascript.js"></script>
  <script src="/codemirror/mode/htmlmixed/htmlmixed.js"></script>
  <script src="/codemirror/mode/css/css.js"></script>
  <script src="/codemirror/mode/xml/xml.js"></script>
  <script src="/codemirror/mode/markdown/markdown.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/json/json.min.js"></script>

  <script>
    let cmEditor, currentRelPath = null;
    const username = <?= json_encode($username) ?>;

    $(function(){
      initTree();
      initEditor();
      initUpload();
      initNewButtons();

      // Right-click blank sidebar to open root context menu
      $('#sidebar').on('contextmenu', e => {
        e.preventDefault();
        const tree = $('#jstree').jstree(true);
        tree.show_contextmenu(tree.get_node('#'), e.pageX, e.pageY);
      });
    });

    function initTree(){
      $('#jstree').jstree({
        core: {
          data: {
            url: `/files_api.php?action=tree&username=${username}`,
            dataType: 'json'
          },
          check_callback: true
        },
        plugins: ['contextmenu','dnd','types'],
        types: {
          folder: { icon:'jstree-folder' },
          file:   { icon:'jstree-file' }
        },
        contextmenu: { items: customMenu }
      })
      .on('select_node.jstree', (e,data) => {
        if(data.node.type==='file') loadFile(data.node.id);
      })
      .on('loaded.jstree', () => {
        // optional: expand root on load
        $('#jstree').jstree(true).open_node('#');
      });
    }

    function customMenu(node){
      return {
        createFile:   { label:'New File',   action:()=>createItem(node,false) },
        createFolder: { label:'New Folder', action:()=>createItem(node,true)  },
        renameItem:   { label:'Rename',     action:()=>$('#jstree').jstree(true).edit(node) },
        deleteItem:   { label:'Delete',     action:()=>deleteItem(node) }
      };
    }

    function initNewButtons(){
      const tree = $('#jstree').jstree(true);
      $('#new-file-btn').click(() => {
        const sel = tree.get_selected()[0] || '#';
        createItem(tree.get_node(sel), false);
      });
      $('#new-folder-btn').click(() => {
        const sel = tree.get_selected()[0] || '#';
        createItem(tree.get_node(sel), true);
      });
    }

    function createItem(node, isDir){
      const tree = $('#jstree').jstree(true);
      // ensure node.id is defined
      if(!node || typeof node.id!=='string') node = { id:'#' };
      const parentPath = node.id!=='#' ? node.id : '';
      const name = prompt(`New ${isDir?'folder':'file'} name:`);
      if(!name) return;

      $.ajax({
        url: `/files_api.php?action=create&username=${username}`,
        method:'POST',
        contentType:'application/json',
        dataType:'json',
        data: JSON.stringify({ path: parentPath, name, isDirectory:isDir })
      })
      .done(resp => {
        if(!resp.success) {
          return alert('Create failed: ' + (resp.error||'Unknown'));
        }
        if(parentPath){
          tree.refresh_node(parentPath);
          tree.open_node(parentPath);
        } else {
          tree.refresh();
        }
      })
      .fail((xhr,st,err) => {
        alert('Request failed: '+err);
      });
    }

    function deleteItem(node){
      if(!confirm('Delete?')) return;
      $.ajax({
        url: `/files_api.php?action=delete&username=${username}`,
        method:'POST',
        contentType:'application/json',
        dataType:'json',
        data: JSON.stringify({ path: node.id })
      })
      .done(resp => {
        if(resp.success) $('#jstree').jstree(true).refresh();
        else alert('Delete failed');
      });
    }

    function loadFile(relPath){
      fetch(`/files_api.php?action=content&username=${username}&path=${encodeURIComponent(relPath)}`)
        .then(r=>r.text())
        .then(text=>{
          const mode = detectMode(relPath);
          cmEditor.setOption('mode', mode);
          cmEditor.setValue(text);
          cmEditor.focus();
          currentRelPath = relPath;
          $('#current-file').text(relPath);
        });
    }

    function detectMode(path){
      const ext = path.split('.').pop().toLowerCase();
      if(['js','mjs','jsx'].includes(ext)) return 'javascript';
      if(ext==='html') return 'htmlmixed';
      if(ext==='css') return 'css';
      if(ext==='json') return 'application/json';
      if(['md','markdown'].includes(ext)) return 'markdown';
      return 'plaintext';
    }

    function initEditor(){
      cmEditor = CodeMirror.fromTextArea(document.getElementById('editor'), {
        lineNumbers:true, lineWrapping:true, mode:'plaintext'
      });
      $('#save-btn').click(saveFile);
    }

    function saveFile(){
      if(!currentRelPath) return alert('No file loaded');
      $.ajax({
        url:`/files_api.php?action=save&username=${username}`,
        method:'POST',
        contentType:'application/json',
        dataType:'json',
        data: JSON.stringify({
          path: currentRelPath,
          content: cmEditor.getValue()
        })
      });
    }

    function initUpload(){
      $('#upload-btn').click(()=>{
        const f = $('#file-input')[0].files[0];
        if(!f) return alert('No file selected');
        const fd = new FormData();
        fd.append('file', f);
        fetch(`/files_api.php?action=upload&username=${username}`, {
          method:'POST', body: fd
        })
        .then(r=>r.json())
        .then(r=> r.success && $('#jstree').jstree(true).refresh());
      });
    }
  </script>
</body>
</html>
