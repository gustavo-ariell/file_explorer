<?php
// index.php - Workspace Explorer con temas Dracula (oscuro/claro) y auto‑actualización
$basePath = '/var/www/html/';
$excludeDirs = ['.', '..', '.git', '.svn', 'node_modules', 'vendor', 'cache', 'tmp'];
$excludeFiles = ['index.php', 'index.html', '.htaccess', 'README.md'];

// ================== FUNCIONES AUXILIARES ==================
function formatSize($bytes) {
    if ($bytes === 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

function formatSizeMB($bytes) {
    if ($bytes === 0) return '0 MB';
    $mb = $bytes / (1024 * 1024);
    return round($mb, 2) . ' MB';
}

function getIcon($filename, $isDir = false) {
    if ($isDir) return '📁';
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'html' => '🌐', 'htm' => '🌐', 'php' => '🐘', 'js' => '⚡', 'css' => '🎨',
        'json' => '📋', 'xml' => '📋', 'txt' => '📝', 'md' => '📖', 'pdf' => '📕',
        'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'svg' => '🎨',
        'mp4' => '🎬', 'mp3' => '🎵', 'zip' => '📦', 'rar' => '📦', 'tar' => '📦',
        'gz' => '📦', 'sql' => '🗄️', 'py' => '🐍', 'rb' => '💎', 'java' => '☕',
        'c' => '⚙️', 'cpp' => '⚙️', 'sh' => '🐚', 'yml' => '⚙️', 'yaml' => '⚙️'
    ];
    return $icons[$ext] ?? '📄';
}

function countRecursive($node) {
    $totalFiles = count($node['files']);
    $totalDirs = count($node['dirs']);
    $totalSize = array_sum(array_column($node['files'], 'size'));
    foreach ($node['dirs'] as $dir) {
        list($subFiles, $subDirs, $subSize) = countRecursive($dir['children']);
        $totalFiles += $subFiles;
        $totalDirs += $subDirs;
        $totalSize += $subSize;
    }
    return [$totalFiles, $totalDirs, $totalSize];
}

function scanDirectory($dir, $basePath, $depth = 0) {
    if ($depth > 10) return [];
    $result = ['dirs' => [], 'files' => []];
    if (!is_readable($dir)) return $result;
    $items = scandir($dir);
    if ($items === false) return $result;
    foreach ($items as $item) {
        if (in_array($item, ['.', '..'])) continue;
        $fullPath = $dir . '/' . $item;
        $relativePath = str_replace($basePath, '', $fullPath);
        if (is_dir($fullPath)) {
            $subContent = scanDirectory($fullPath, $basePath, $depth + 1);
            $result['dirs'][] = [
                'name' => $item,
                'path' => $relativePath,
                'fullPath' => $fullPath,
                'hasContent' => !empty($subContent['dirs']) || !empty($subContent['files']),
                'children' => $subContent
            ];
        } else {
            $size = filesize($fullPath);
            $result['files'][] = [
                'name' => $item,
                'path' => $relativePath,
                'size' => $size,
                'sizeFormatted' => formatSize($size),
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
                'extension' => strtolower(pathinfo($item, PATHINFO_EXTENSION))
            ];
        }
    }
    // Orden natural (numérico + alfabético)
    usort($result['dirs'], fn($a,$b)=> strnatcasecmp($a['name'], $b['name']));
    usort($result['files'], fn($a,$b)=> strnatcasecmp($a['name'], $b['name']));
    return $result;
}

function getProjectsData($basePath, $excludeDirs) {
    $projects = [];
    if (!is_dir($basePath) || !is_readable($basePath)) return $projects;
    $items = scandir($basePath);
    if ($items === false) return $projects;
    foreach ($items as $item) {
        if (in_array($item, $excludeDirs)) continue;
        $fullPath = $basePath . '/' . $item;
        if (is_dir($fullPath)) {
            $content = scanDirectory($fullPath, $basePath);
            list($totalFiles, $totalDirs, $totalSize) = countRecursive($content);
            $projects[] = [
                'name' => $item,
                'urlPath' => '/' . $item,
                'totalFiles' => $totalFiles,
                'totalDirs' => $totalDirs,
                'totalSize' => $totalSize,
                'totalSizeFormatted' => formatSizeMB($totalSize),
                'content' => $content,
                'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
            ];
        }
    }
    usort($projects, fn($a,$b)=> strnatcasecmp($a['name'], $b['name']));
    return $projects;
}

// ================== ENDPOINT JSON PARA AJAX ==================
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    $projects = getProjectsData($basePath, $excludeDirs);
    $totalProjects = count($projects);
    $totalFiles = array_sum(array_column($projects, 'totalFiles'));
    $totalSize = array_sum(array_column($projects, 'totalSize'));
    $totalDirs = array_sum(array_column($projects, 'totalDirs'));
    echo json_encode([
        'projects' => $projects,
        'stats' => [
            'totalProjects' => $totalProjects,
            'totalDirs' => $totalDirs,
            'totalFiles' => $totalFiles,
            'totalSize' => $totalSize,
            'totalSizeFormatted' => formatSizeMB($totalSize)
        ]
    ]);
    exit;
}

// ================== HTML PRINCIPAL ==================
$initialProjects = getProjectsData($basePath, $excludeDirs);
$totalProjects = count($initialProjects);
$totalFiles = array_sum(array_column($initialProjects, 'totalFiles'));
$totalSize = array_sum(array_column($initialProjects, 'totalSize'));
$totalDirs = array_sum(array_column($initialProjects, 'totalDirs'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📂 Workspace Explorer · Dracula</title>
    <style>
        /* ========== DRACULA DARK (por defecto) ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #282a36;
            font-family: 'Segoe UI', 'Inter', system-ui, sans-serif;
            padding: 24px;
            color: #f8f8f2;
            transition: background 0.2s, color 0.2s;
        }
        .container { max-width: 1600px; margin: 0 auto; }
        .header {
            background: #44475a;
            border-radius: 28px;
            padding: 28px;
            margin-bottom: 32px;
            border: 1px solid #6272a4;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3);
        }
        h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #bd93f9;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .badge, .auto-update {
            background: rgba(189, 147, 249, 0.2);
            border: 1px solid #bd93f9;
            border-radius: 40px;
            padding: 4px 14px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #bd93f9;
        }
        .auto-update::before { content: "🔄 "; }
        .subtitle {
            color: #f8f8f2cc;
            margin: 16px 0 24px 0;
            border-left: 3px solid #bd93f9;
            padding-left: 18px;
            font-size: 0.9rem;
        }
        .subtitle code {
            background: #1e1f29;
            padding: 2px 8px;
            border-radius: 12px;
            font-family: monospace;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        .stat-card {
            background: #282a36;
            border-radius: 24px;
            padding: 18px 12px;
            text-align: center;
            border: 1px solid #6272a4;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #50fa7b;
            letter-spacing: -0.5px;
        }
        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #bd93f9;
            margin-top: 6px;
        }
        .refresh-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .info-text {
            background: #44475a;
            padding: 6px 18px;
            border-radius: 32px;
            font-size: 0.85rem;
            border: 1px solid #6272a4;
            color: #f8f8f2;
        }
        .refresh-btn, .theme-toggle {
            background: #44475a;
            border: 1px solid #6272a4;
            padding: 8px 20px;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 500;
            color: #f8f8f2;
            transition: 0.2s;
        }
        .refresh-btn:hover, .theme-toggle:hover {
            background: #6272a4;
            border-color: #bd93f9;
        }
        .auto-toggle {
            background: #282a36;
            border: 1px solid #6272a4;
            padding: 8px 20px;
            border-radius: 40px;
            cursor: pointer;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #f8f8f2;
        }
        .auto-toggle.active {
            background: #44475a;
            border-color: #50fa7b;
            color: #50fa7b;
        }
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 24px;
        }
        .project-card {
            background: #44475a;
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid #6272a4;
            transition: transform 0.2s;
        }
        .project-card:hover {
            transform: translateY(-3px);
            border-color: #bd93f9;
        }
        .project-header {
            background: #282a36;
            padding: 20px;
            cursor: pointer;
            border-bottom: 1px solid #6272a4;
        }
        .project-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 1.2rem;
        }
        .project-stats {
            display: flex;
            gap: 20px;
            font-size: 0.7rem;
            margin-top: 12px;
            color: #bd93f9;
        }
        .toggle-icon {
            font-size: 1.2rem;
            transition: transform 0.2s;
            color: #ff79c6;
        }
        .project-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .project-content.expanded {
            max-height: 2000px;
        }
        .project-body {
            padding: 20px;
        }
        .dir-item { margin-bottom: 12px; }
        .dir-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #282a36;
            border-radius: 16px;
            cursor: pointer;
            font-weight: 500;
            border: 1px solid #6272a4;
        }
        .dir-header:hover { background: #6272a4; }
        .dir-toggle { font-size: 0.7rem; width: 20px; color: #8be9fd; }
        .dir-children { margin-left: 28px; padding-left: 12px; border-left: 1px solid #6272a4; display: none; }
        .dir-children.open { display: block; }
        .file-list { list-style: none; margin-top: 8px; }
        .file-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-radius: 14px;
            transition: background 0.15s;
        }
        .file-item:hover { background: #6272a4; }
        .file-link {
            color: #f8f8f2;
            text-decoration: none;
            flex: 1;
            word-break: break-all;
        }
        .file-link:hover { color: #8be9fd; text-decoration: underline; }
        .file-meta { color: #6272a4; font-size: 0.7rem; font-family: monospace; }
        .empty-state {
            text-align: center;
            padding: 60px;
            background: #44475a;
            border-radius: 32px;
            border: 1px solid #6272a4;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #6272a4;
            font-size: 0.7rem;
        }

        /* ========== DRACULA LIGHT ========== */
        body.theme-light {
            background: #f8f8f2;
            color: #282a36;
        }
        body.theme-light .header {
            background: #ffffff;
            border-color: #e0e0e0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        body.theme-light h1 {
            color: #6c3cb0;
            text-shadow: none;
        }
        body.theme-light .subtitle {
            color: #282a36cc;
        }
        body.theme-light .subtitle code {
            background: #e0e0e0;
            color: #282a36;
        }
        body.theme-light .stat-card {
            background: #ffffff;
            border-color: #e0e0e0;
        }
        body.theme-light .info-text,
        body.theme-light .refresh-btn,
        body.theme-light .theme-toggle,
        body.theme-light .auto-toggle {
            background: #ffffff;
            border-color: #d0d0d0;
            color: #282a36;
        }
        body.theme-light .auto-toggle.active {
            background: #e8f0fe;
            border-color: #50fa7b;
            color: #50fa7b;
        }
        body.theme-light .project-card {
            background: #ffffff;
            border-color: #e0e0e0;
        }
        body.theme-light .project-header {
            background: #f1f1f8;
            border-color: #e0e0e0;
        }
        body.theme-light .project-stats {
            color: #bd93f9;
        }
        body.theme-light .dir-header {
            background: #f1f1f8;
            border-color: #e0e0e0;
        }
        body.theme-light .dir-header:hover {
            background: #e6e6ef;
        }
        body.theme-light .file-item:hover {
            background: #f0f0f5;
        }
        body.theme-light .file-link {
            color: #282a36;
        }
        body.theme-light .file-link:hover {
            color: #bd93f9;
        }
        body.theme-light .file-meta {
            color: #9090a0;
        }
        body.theme-light .empty-state {
            background: #ffffff;
            border-color: #e0e0e0;
        }
        body.theme-light .footer {
            color: #9090a0;
        }
        body.theme-light .stat-number {
            color: #50fa7b;
        }
        body.theme-light .toggle-icon {
            color: #ff79c6;
        }
        body.theme-light .dir-toggle {
            color: #8be9fd;
        }
    </style>
</head>
<body class="theme-dark">
<div class="container">
    <div class="header">
        <h1>
            📂 Workspace Explorer
            <span class="badge">Auto‑sync</span>
            <span class="auto-update">Polling cada 10s</span>
        </h1>
        <div class="subtitle">
            Escaneo automático de <code><?= htmlspecialchars($basePath) ?></code><br>
            Los cambios se reflejan en tiempo real sin recargar manualmente.
        </div>
        <div class="stats" id="globalStats">
            <div class="stat-card"><div class="stat-number" id="statProjects"><?= $totalProjects ?></div><div class="stat-label">Proyectos</div></div>
            <div class="stat-card"><div class="stat-number" id="statDirs"><?= $totalDirs ?></div><div class="stat-label">Carpetas</div></div>
            <div class="stat-card"><div class="stat-number" id="statFiles"><?= $totalFiles ?></div><div class="stat-label">Archivos</div></div>
            <div class="stat-card"><div class="stat-number" id="statSize"><?= formatSizeMB($totalSize) ?></div><div class="stat-label">Tamaño total</div></div>
        </div>
    </div>

    <div class="refresh-bar">
        <div class="info-text">🔍 Escaneando: <?= htmlspecialchars($basePath) ?></div>
        <div style="display: flex; gap: 12px;">
            <button class="refresh-btn" id="manualRefresh">⟳ Refrescar ahora</button>
            <button class="theme-toggle" id="themeToggleBtn">🌓 Tema Dracula</button>
            <button class="auto-toggle active" id="autoToggleBtn">⏵ Auto‑actualización activa</button>
        </div>
    </div>

    <div id="projectsGrid" class="projects-grid"></div>
    <div class="footer">
        ⚡ Actualización automática cada 10s · Estados de carpetas preservados
    </div>
</div>

<script>
    let expandedProjects = new Map();
    let expandedDirs = new Map();
    let autoRefreshEnabled = true;
    let refreshInterval = null;
    let isUpdating = false;

    let currentTheme = localStorage.getItem('dracula_theme') || 'dark';
    function applyTheme(theme) {
        if (theme === 'light') {
            document.body.classList.add('theme-light');
            document.body.classList.remove('theme-dark');
        } else {
            document.body.classList.add('theme-dark');
            document.body.classList.remove('theme-light');
        }
        localStorage.setItem('dracula_theme', theme);
        currentTheme = theme;
    }
    applyTheme(currentTheme);
    document.getElementById('themeToggleBtn').addEventListener('click', () => {
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
    });

    function naturalCompare(a, b) {
        return a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' });
    }

    function renderFileTree(content, baseUrl, currentPath = "") {
        if ((!content.dirs || content.dirs.length === 0) && (!content.files || content.files.length === 0)) {
            return '<div style="color:#999; padding: 12px;">📭 Carpeta vacía</div>';
        }
        let dirs = content.dirs ? [...content.dirs] : [];
        let files = content.files ? [...content.files] : [];
        dirs.sort(naturalCompare);
        files.sort(naturalCompare);
        let html = '';
        for (let dir of dirs) {
            const dirKey = currentPath + '/' + dir.name;
            const isOpen = expandedDirs.get(dirKey) === true;
            const hasChildren = (dir.children && (dir.children.dirs?.length || dir.children.files?.length));
            const toggleSymbol = hasChildren ? (isOpen ? '▼' : '▶') : '📁';
            const childrenClass = isOpen ? 'open' : '';
            const dirId = 'dir_' + Math.random().toString(36).substr(2, 10);
            html += `<div class="dir-item" data-dir-key="${escapeHtml(dirKey)}">
                        <div class="dir-header" onclick="toggleDir(this, '${escapeHtml(dirKey)}')">
                            <span class="dir-toggle">${toggleSymbol}</span>
                            <span>📁 ${escapeHtml(dir.name)}</span>
                        </div>
                        <div class="dir-children ${childrenClass}" id="${dirId}">
                            ${hasChildren ? renderFileTree(dir.children, baseUrl + '/' + dir.name, dirKey) : ''}
                        </div>
                     </div>`;
        }
        if (files.length) {
            html += '<ul class="file-list">';
            for (let file of files) {
                const icon = getFileIcon(file.name);
                const fullUrl = baseUrl + '/' + encodeURIComponent(file.name);
                html += `<li class="file-item">
                            <span class="file-icon">${icon}</span>
                            <a href="${escapeHtml(fullUrl)}" class="file-link" target="_blank">${escapeHtml(file.name)}</a>
                            <span class="file-meta">${file.sizeFormatted}</span>
                         </li>`;
            }
            html += '</ul>';
        }
        return html;
    }

    function getFileIcon(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        const icons = { html:'🌐', htm:'🌐', php:'🐘', js:'⚡', css:'🎨', json:'📋', txt:'📝', md:'📖', pdf:'📕', jpg:'🖼️', jpeg:'🖼️', png:'🖼️', gif:'🖼️', svg:'🎨', mp4:'🎬', mp3:'🎵', zip:'📦', sql:'🗄️', py:'🐍', sh:'🐚' };
        return icons[ext] || '📄';
    }

    function escapeHtml(str) {
        return String(str).replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    function renderDashboard(data) {
        const projects = data.projects;
        const stats = data.stats;
        document.getElementById('statProjects').innerText = stats.totalProjects;
        document.getElementById('statDirs').innerText = stats.totalDirs;
        document.getElementById('statFiles').innerText = stats.totalFiles;
        document.getElementById('statSize').innerText = stats.totalSizeFormatted;

        const grid = document.getElementById('projectsGrid');
        if (!projects.length) {
            grid.innerHTML = `<div class="empty-state"><div style="font-size:3rem;">📭</div><h3>No hay proyectos</h3><p>Crea carpetas dentro de <?= addslashes($basePath) ?></p></div>`;
            return;
        }

        let html = '';
        projects.forEach((project, idx) => {
            const isExpanded = expandedProjects.has(idx) ? expandedProjects.get(idx) : false;
            const expandedClass = isExpanded ? 'expanded' : '';
            const iconRotate = isExpanded ? 'rotate(180deg)' : 'rotate(0deg)';
            html += `<div class="project-card" data-project-index="${idx}">
                        <div class="project-header" onclick="toggleProjectCard(${idx})">
                            <div class="project-title">
                                <span>${escapeHtml(project.name)}</span>
                                <span class="toggle-icon" style="transform:${iconRotate}">▼</span>
                            </div>
                            <div class="project-stats">
                                <span>📁 ${project.totalDirs} carpetas</span>
                                <span>📄 ${project.totalFiles} archivos</span>
                                <span>💾 ${project.totalSizeFormatted}</span>
                            </div>
                        </div>
                        <div class="project-content ${expandedClass}" id="proj-content-${idx}">
                            <div class="project-body">
                                ${renderFileTree(project.content, project.urlPath, '')}
                                <div style="margin-top:20px; padding-top:12px; border-top:1px solid #6272a4;">
                                    <a href="${escapeHtml(project.urlPath)}" style="color:#8be9fd; text-decoration:none; font-size:0.85rem;">📂 Ver en navegador →</a>
                                </div>
                            </div>
                        </div>
                    </div>`;
        });
        grid.innerHTML = html;
        for (let [idx, expanded] of expandedProjects.entries()) {
            const contentDiv = document.getElementById(`proj-content-${idx}`);
            const iconSpan = document.querySelector(`.project-card[data-project-index="${idx}"] .toggle-icon`);
            if (contentDiv) {
                if (expanded) {
                    contentDiv.classList.add('expanded');
                    if(iconSpan) iconSpan.style.transform = 'rotate(180deg)';
                } else {
                    contentDiv.classList.remove('expanded');
                    if(iconSpan) iconSpan.style.transform = 'rotate(0deg)';
                }
            }
        }
    }

    window.toggleProjectCard = function(idx) {
        const content = document.getElementById(`proj-content-${idx}`);
        const card = document.querySelector(`.project-card[data-project-index="${idx}"]`);
        const iconSpan = card?.querySelector('.toggle-icon');
        if (!content) return;
        const isExpanded = content.classList.contains('expanded');
        if (isExpanded) {
            content.classList.remove('expanded');
            if(iconSpan) iconSpan.style.transform = 'rotate(0deg)';
            expandedProjects.set(idx, false);
        } else {
            content.classList.add('expanded');
            if(iconSpan) iconSpan.style.transform = 'rotate(180deg)';
            expandedProjects.set(idx, true);
        }
    };

    window.toggleDir = function(element, dirKey) {
        const childrenDiv = element.nextElementSibling;
        const toggleSpan = element.querySelector('.dir-toggle');
        if (!childrenDiv) return;
        const isOpen = childrenDiv.classList.contains('open');
        if (isOpen) {
            childrenDiv.classList.remove('open');
            if (toggleSpan) toggleSpan.textContent = '▶';
            expandedDirs.set(dirKey, false);
        } else {
            childrenDiv.classList.add('open');
            if (toggleSpan) toggleSpan.textContent = '▼';
            expandedDirs.set(dirKey, true);
        }
    };

    async function fetchAndUpdate() {
        if (isUpdating) return;
        isUpdating = true;
        try {
            const response = await fetch('?ajax=1');
            const data = await response.json();
            renderDashboard(data);
        } catch (err) {
            console.warn('Error al actualizar:', err);
        } finally {
            isUpdating = false;
        }
    }

    function startAutoRefresh() {
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            if (autoRefreshEnabled) fetchAndUpdate();
        }, 10000);
    }

    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
        }
    }

    document.getElementById('manualRefresh').addEventListener('click', () => fetchAndUpdate());
    const toggleBtn = document.getElementById('autoToggleBtn');
    toggleBtn.addEventListener('click', () => {
        autoRefreshEnabled = !autoRefreshEnabled;
        if (autoRefreshEnabled) {
            toggleBtn.classList.add('active');
            toggleBtn.innerHTML = '⏵ Auto‑actualización activa';
            startAutoRefresh();
        } else {
            toggleBtn.classList.remove('active');
            toggleBtn.innerHTML = '⏸ Auto‑actualización pausada';
            stopAutoRefresh();
        }
    });

    fetchAndUpdate().then(() => startAutoRefresh());
</script>
</body>
</html>
