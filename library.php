
<?php
require_once 'includes/auth.php';
checkLogin();

$my_id = (int) $_SESSION['user_id'];
$my_username = $_SESSION['username'] ?? 'User';
$my_avatar = $_SESSION['avatar'] ?? 'assets/images/default-avatar.png';

// File records will be connected after confirming
// the messages database attachment schema.
$libraryFiles = [];

$categories = [
    'all' => 'All files',
    'documents' => 'Documents',
    'images' => 'Images',
    'videos' => 'Videos',
    'audio' => 'Audio',
    'favorites' => 'Favorites'
];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#155eef">

    <title>Library | NexTalk</title>

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📚</text></svg>">

    <!-- ONE CENTRAL STYLESHEET FOR ALL NEXTALK PAGES -->
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>

<body class="library-body">

<div class="app-container library-app">

    <!-- =====================================
         SIDEBAR
    ====================================== -->
    <aside class="sidebar library-sidebar">

        <a href="index.php" class="logo-container nt-brand">
            <div class="logo-icon">N</div>
            <div class="logo-text">Nex<span>Talk</span></div>
        </a>

        <div class="nt-workspace-label">WORKSPACE</div>

        <nav class="menu nt-navigation" aria-label="Main navigation">

            <a href="index.php" class="menu-item">
                <span class="icon">▣</span>
                <span>Messages</span>
            </a>

            <a href="index.php#users" class="menu-item">
                <span class="icon">♙</span>
                <span>People</span>
            </a>

            <a href="library.php" class="menu-item active">
                <span class="icon">▤</span>
                <span>Library</span>
            </a>

            <a href="index.php#groups" class="menu-item">
                <span class="icon">♧</span>
                <span>Groups</span>
            </a>

        </nav>

        <div class="nt-sidebar-divider"></div>

        <div class="nt-workspace-label">YOUR LIBRARY</div>

        <nav class="menu nt-navigation library-side-categories">

            <a href="#all" class="menu-item library-side-link active" data-category="all">
                <span class="icon">📁</span>
                <span>All files</span>
            </a>

            <a href="#documents" class="menu-item library-side-link" data-category="documents">
                <span class="icon">📄</span>
                <span>Documents</span>
            </a>

            <a href="#images" class="menu-item library-side-link" data-category="images">
                <span class="icon">🖼</span>
                <span>Images</span>
            </a>

            <a href="#videos" class="menu-item library-side-link" data-category="videos">
                <span class="icon">🎬</span>
                <span>Videos</span>
            </a>

            <a href="#audio" class="menu-item library-side-link" data-category="audio">
                <span class="icon">🎵</span>
                <span>Audio</span>
            </a>

            <a href="#favorites" class="menu-item library-side-link" data-category="favorites">
                <span class="icon">⭐</span>
                <span>Favorites</span>
            </a>

        </nav>

        <div class="sidebar-spacer"></div>

        <!-- USER PROFILE -->
        <div class="my-profile-section">
            <div class="my-profile-card">

                <img
                    src="<?= e($my_avatar) ?>"
                    alt="Profile"
                    class="my-profile-avatar"
                >

                <div class="nt-profile-details">
                    <h3><?= e($my_username) ?></h3>

                    <p class="status online">
                        <span class="dot"></span> Online
                    </p>
                </div>

                <a href="index.php" class="edit-profile-btn" title="Edit profile">
                    ✎
                </a>

            </div>
        </div>

        <!-- THEME -->
        <div class="nt-sidebar-bottom">

            <div class="theme-toggle-wrapper">
                <button type="button" id="theme-toggle" class="theme-toggle-btn">
                    <span>◐</span> Dark mode
                </button>
            </div>

            <a href="logout.php" class="logout-link">
                <button type="button" class="logout-btn">
                    <span>↪</span> Sign out
                </button>
            </a>

            <div class="nt-sidebar-version">
                NexTalk · Personal workspace
            </div>

        </div>

    </aside>


    <!-- =====================================
         LIBRARY MAIN CONTENT
    ====================================== -->
    <main class="library-main">

        <!-- TOPBAR -->
        <header class="library-topbar">

            <div class="library-breadcrumb">
                <a href="index.php">NexTalk</a>
                <span>/</span>
                <strong>Library</strong>
            </div>

            <div class="library-topbar-right">

                <div class="library-secure-label">
                    <span class="secure-dot"></span>
                    Your personal library
                </div>

                <img
                    src="<?= e($my_avatar) ?>"
                    alt="Profile"
                    class="topbar-avatar"
                >

            </div>

        </header>


        <div class="library-content">

            <!-- INTRODUCTION -->
            <section class="library-intro">

                <div>

                    <div class="library-eyebrow">
                        <span>✦</span> FILE MANAGEMENT
                    </div>

                    <h1>My Library<span>.</span></h1>

                    <p>
                        Everything shared with you, organized in one place.
                        Find, explore and manage your files.
                    </p>

                </div>

                <div class="library-intro-icon">
                    <span>📚</span>
                </div>

            </section>


            <!-- SEARCH AND TOOLBAR -->
            <section class="library-toolbar">

                <div class="library-search-wrapper">

                    <span>⌕</span>

                    <input
                        type="search"
                        id="library-search"
                        placeholder="Search files, documents, images..."
                        autocomplete="off"
                        aria-label="Search library"
                    >

                    <kbd>Search</kbd>

                </div>

                <div class="library-toolbar-actions">

                    <select id="library-sort" aria-label="Sort files">
                        <option value="newest">Newest first</option>
                        <option value="oldest">Oldest first</option>
                        <option value="name">Name A–Z</option>
                    </select>

                    <div class="library-view-switch">

                        <button
                            type="button"
                            class="view-btn active"
                            id="grid-view"
                            aria-label="Grid view"
                            title="Grid view"
                        >
                            ▦
                        </button>

                        <button
                            type="button"
                            class="view-btn"
                            id="list-view"
                            aria-label="List view"
                            title="List view"
                        >
                            ☰
                        </button>

                    </div>

                </div>

            </section>


            <!-- OVERVIEW -->
            <section class="library-overview">

                <div class="overview-card overview-primary">

                    <div class="overview-card-top">
                        <div class="overview-icon blue-icon">📁</div>
                        <span class="overview-label">YOUR COLLECTION</span>
                    </div>

                    <h2><?= count($libraryFiles) ?></h2>
                    <p>Total available files</p>

                </div>


                <div class="overview-card">

                    <div class="overview-card-top">
                        <div class="overview-icon purple-icon">📄</div>
                        <span class="overview-label">DOCUMENTS</span>
                    </div>

                    <h2 id="document-count">0</h2>
                    <p>Shared documents</p>

                </div>


                <div class="overview-card">

                    <div class="overview-card-top">
                        <div class="overview-icon orange-icon">🖼️</div>
                        <span class="overview-label">MEDIA</span>
                    </div>

                    <h2 id="media-count">0</h2>
                    <p>Images and videos</p>

                </div>


                <div class="overview-card">

                    <div class="overview-card-top">
                        <div class="overview-icon green-icon">⭐</div>
                        <span class="overview-label">FAVORITES</span>
                    </div>

                    <h2 id="favorite-count">0</h2>
                    <p>Saved files</p>

                </div>

            </section>


            <!-- FILES -->
            <section class="library-files-section">

                <div class="library-section-heading">

                    <div>
                        <h2>Your files</h2>
                        <p>Browse your shared content.</p>
                    </div>

                    <span id="visible-file-count" class="file-count">
                        0 files
                    </span>

                </div>


                <!-- CATEGORY FILTERS -->
                <div class="library-filter-tabs">

                    <?php foreach ($categories as $key => $label): ?>

                        <button
                            type="button"
                            class="filter-tab <?= $key === 'all' ? 'active' : '' ?>"
                            data-category="<?= e($key) ?>"
                        >
                            <?= e($label) ?>
                        </button>

                    <?php endforeach; ?>

                </div>


                <!-- FILE GRID -->
                <div id="library-files" class="library-files-grid">

                    <?php foreach ($libraryFiles as $file): ?>

                        <article
                            class="library-file-card"
                            data-name="<?= e($file['name'] ?? '') ?>"
                            data-type="<?= e($file['type'] ?? '') ?>"
                            data-date="<?= e($file['date'] ?? '') ?>"
                            data-favorite="<?= !empty($file['favorite']) ? '1' : '0' ?>"
                        >

                            <div class="file-card-preview">

                                <span class="file-card-icon">
                                    <?= e($file['icon'] ?? '📄') ?>
                                </span>

                                <button
                                    type="button"
                                    class="favorite-file-btn"
                                    aria-label="Toggle favorite"
                                >☆</button>

                            </div>

                            <div class="file-card-details">

                                <h3><?= e($file['name'] ?? 'Untitled file') ?></h3>

                                <p>
                                    <?= e($file['size'] ?? 'File') ?>
                                    ·
                                    <?= e($file['source'] ?? 'Shared') ?>
                                </p>

                                <div class="file-card-actions">

                                    <?php if (!empty($file['url'])): ?>

                                        <a
                                            href="<?= e($file['url']) ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            Open file
                                        </a>

                                    <?php endif; ?>

                                    <span><?= e($file['date'] ?? '') ?></span>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>


                <!-- EMPTY STATE -->
                <div id="library-empty" class="library-empty-state">

                    <div class="empty-illustration">
                        <div class="empty-circle">📂</div>
                        <span class="empty-sparkle sparkle-one">✦</span>
                        <span class="empty-sparkle sparkle-two">✧</span>
                    </div>

                    <h3>Your library is waiting</h3>

                    <p>
                        Files shared through your NexTalk conversations
                        will appear here. Once you start sharing,
                        everything will be organized for easy access.
                    </p>

                    <a href="index.php" class="empty-action-btn">
                        Go to Messages <span>→</span>
                    </a>

                </div>


                <!-- NO SEARCH RESULTS -->
                <div id="library-no-results" class="library-no-results" hidden>

                    <div>🔎</div>

                    <h3>No files found</h3>

                    <p>
                        Try another search or choose a different category.
                    </p>

                    <button type="button" id="clear-library-search">
                        Clear search
                    </button>

                </div>

            </section>

        </div>


        <!-- FOOTER -->
        <footer class="library-footer">

            <span>© <?= date('Y') ?> NexTalk. All rights reserved.</span>

            <span>💙 Simple. Fast. Reliable.</span>

        </footer>

    </main>

</div>


<!-- FILE PREVIEW MODAL -->
<div id="library-preview-modal" class="library-modal">

    <div class="library-modal-content">

        <button
            type="button"
            id="close-library-preview"
            class="preview-close"
            aria-label="Close preview"
        >
            ×
        </button>

        <div id="library-preview-content"></div>

    </div>

</div>


<!-- SHARED THEME AND LIBRARY DATA -->
<script>
    window.NEXTALK_LIBRARY = {
        userId: <?= json_encode($my_id) ?>,
        username: <?= json_encode($my_username) ?>,
        files: <?= json_encode($libraryFiles) ?>
    };

    (function () {
        const saved = localStorage.getItem('nextalk-theme');

        if (saved === 'dark') {
            document.body.classList.add('dark-mode');
        }

        const themeButton = document.getElementById('theme-toggle');

        if (themeButton) {
            themeButton.addEventListener('click', function () {
                document.body.classList.toggle('dark-mode');

                const isDark = document.body.classList.contains('dark-mode');

                localStorage.setItem(
                    'nextalk-theme',
                    isDark ? 'dark' : 'light'
                );
            });
        }
    })();
</script>

<script src="assets/js/library.js?v=2"></script>

</body>
</html>
