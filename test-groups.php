<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in. <a href='login.php'>Log in</a>");
}

$my_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Group Test</title>
    <style>
        body { font-family: sans-serif; background: #f0f4f8; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 16px; max-width: 700px; margin: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h1 { color: #0f172a; }
        button { background: #0052ff; color: white; padding: 15px 30px; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; margin: 10px 0; font-size: 16px; }
        .result { background: #f8fafc; padding: 15px; border-radius: 10px; margin: 10px 0; font-family: monospace; font-size: 13px; white-space: pre-wrap; border-left: 4px solid #cbd5e1; }
        .ok { color: #065f46; background: #ecfdf5; border-left-color: #10b981; }
        .err { color: #991b1b; background: #fef2f2; border-left-color: #ef4444; }
        .info { color: #1e40af; background: #eff6ff; border-left-color: #3b82f6; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🧪 Group API Test</h1>
        <p>Logged in as user ID: <b><?= $my_id ?></b></p>
        
        <button onclick="runTests()">▶ Run All Tests</button>
        <div id="output" style="margin-top:20px;"></div>
    </div>

    <script>
    async function runTests() {
        const out = document.getElementById('output');
        out.innerHTML = '';
        
        function log(msg, type = 'info') {
            const div = document.createElement('div');
            div.className = 'result ' + type;
            div.textContent = msg;
            out.appendChild(div);
        }
        
        log('=== HTML ELEMENT CHECK ===', 'info');
        const tabGroups = document.getElementById('tab-groups');
        const groupsView = document.getElementById('groups-list-view');
        const createBtn = document.getElementById('create-group-btn');
        
        log('tab-groups: ' + (tabGroups ? '✅ Found' : '❌ MISSING — no group tab in sidebar'), tabGroups ? 'ok' : 'err');
        log('groups-list-view: ' + (groupsView ? '✅ Found' : '❌ MISSING — no groups list div'), groupsView ? 'ok' : 'err');
        log('create-group-btn: ' + (createBtn ? '✅ Found' : '❌ MISSING — no create button'), createBtn ? 'ok' : 'err');
        
        log('=== JS FUNCTION CHECK ===', 'info');
        log('typeof loadGroups: ' + typeof window.loadGroups, typeof window.loadGroups === 'function' ? 'ok' : 'err');
        
        log('=== API: GET GROUPS ===', 'info');
        try {
            const res = await fetch('api/get-groups.php');
            const text = await res.text();
            log('Status: ' + res.status, res.ok ? 'ok' : 'err');
            log('Response: ' + (text || '(empty)'), res.ok ? 'ok' : 'err');
        } catch (e) {
            log('Fetch failed: ' + e.message, 'err');
        }
        
        log('=== API: CREATE GROUP ===', 'info');
        try {
            const res = await fetch('api/create-group.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: 'Test Group From UI', members: [] })
            });
            const text = await res.text();
            log('Status: ' + res.status, res.ok ? 'ok' : 'err');
            log('Response: ' + (text || '(empty)'), res.ok ? 'ok' : 'err');
        } catch (e) {
            log('Create failed: ' + e.message, 'err');
        }
        
        log('=== DONE ===', 'info');
    }
    </script>
</body>
</html>