
<?php
require_once 'includes/auth.php';
checkLogin();

$my_id = (int) $_SESSION['user_id'];
$my_username = $_SESSION['username'] ?? 'User';
$my_avatar = $_SESSION['avatar'] ?? '';

$safeUsername = htmlspecialchars($my_username, ENT_QUOTES, 'UTF-8');
$safeAvatar = htmlspecialchars($my_avatar, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#155eef">
    <title>NexTalk | Connect. Communicate. Belong.</title>

    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💬</text></svg>">

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/nextalk.css?v=2">
    <link rel="stylesheet" href="assets/css/settings.css?v=1">
</head>

<body>

<div class="app-container nextalk-app">

    <!-- =====================================
         MAIN SIDEBAR
    ====================================== -->
    <aside class="sidebar nt-sidebar">

        <a href="index.php#messages" class="logo-container nt-brand">
            <div class="logo-icon">N</div>
            <div class="logo-text">Nex<span>Talk</span></div>
        </a>

        <div class="nt-workspace-label">WORKSPACE</div>

        <nav class="menu nt-navigation" aria-label="Main navigation">

            <a href="#messages" class="menu-item active" id="tab-messages">
                <span class="icon">▣</span>
                <span>Messages</span>
            </a>

            <a href="#people" class="menu-item" id="tab-users">
                <span class="icon">♙</span>
                <span>People</span>
            </a>

            <a href="#library" class="menu-item" id="tab-library">
                <span class="icon">▤</span>
                <span>Library</span>
            </a>

            <a href="#groups" class="menu-item" id="tab-groups">
                <span class="icon">♧</span>
                <span>Groups</span>
            </a>

            <a href="#profile" class="menu-item" id="tab-profile">
                <span class="icon">◎</span>
                <span>Profile</span>
            </a>

            <a href="#settings" class="menu-item" id="tab-settings">
                <span class="icon">⚙</span>
                <span>Settings</span>
            </a>

        </nav>

        <div class="nt-sidebar-divider"></div>

        <div class="nt-profile-area">
            <div class="nt-workspace-label">YOUR ACCOUNT</div>

            <div class="my-profile-section">
                <div class="my-profile-card">

                    <img
                        src="<?= $safeAvatar ?>"
                        alt="Your profile"
                        class="my-profile-avatar"
                    >

                    <div class="nt-profile-details">
                        <h3><?= $safeUsername ?></h3>
                        <p class="status online">
                            <span class="dot"></span> Online
                        </p>
                    </div>

                    <button
                        type="button"
                        id="open-profile-modal"
                        class="edit-profile-btn"
                        title="Edit profile"
                        aria-label="Edit profile"
                    >✎</button>

                </div>
            </div>
        </div>

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
         CONVERSATION LIST SIDEBAR
    ====================================== -->
    <section class="chat-list nt-conversations">

        <div class="nt-panel-heading">
            <div>
                <span class="nt-eyebrow">YOUR WORKSPACE</span>
                <h1 id="nt-list-heading">Messages</h1>
                <p>Stay connected with your people.</p>
            </div>
            <div class="nt-heading-icon">✦</div>
        </div>

        <div class="search-bar nt-search">
            <span class="nt-search-icon">⌕</span>

            <input
                type="search"
                id="chat-search"
                placeholder="Search conversations..."
                autocomplete="off"
                aria-label="Search conversations"
            >

            <span class="nt-search-shortcut">⌘ K</span>
        </div>

        <div id="create-group-wrapper" class="nt-create-group" style="display:none;">
            <button type="button" id="create-group-btn">
                <span>＋</span> Create a group
            </button>
        </div>

        <div class="nt-list-caption">
            <span id="nt-list-caption">RECENT CONVERSATIONS</span>
            <span class="nt-list-indicator"></span>
        </div>

        <div id="messages-list-view" class="nt-list-content"></div>
        <div id="users-list-view" class="nt-list-content" style="display:none;"></div>
        <div id="groups-list-view" class="nt-list-content" style="display:none;"></div>

        <div class="nt-conversation-footer">
            <span class="nt-footer-dot"></span>
            <span>Your conversations are ready</span>
        </div>

    </section>


    <!-- =====================================
         MAIN CHAT WORKSPACE
    ====================================== -->
    <main class="chat-area nt-chat-area">

<header class="chat-header nt-chat-header">
    <div class="nt-contact-details">
        <div class="nt-avatar-wrap">
            <img
                src="https://i.pravatar.cc/150?img=12"
                alt="Contact avatar"
                id="current-chat-avatar"
                class="nt-contact-avatar"
            >
            <span class="nt-online-indicator"></span>
        </div>

        <div class="nt-contact-text">
            <h3 id="current-chat-name">Alex</h3>
            <span id="current-chat-status">Select a conversation</span>
        </div>
    </div>

    <div class="nt-header-actions">

        <!-- Voice call -->
        <button type="button" class="nt-header-btn"
                id="call-btn" title="Voice call"
                aria-label="Voice call">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2
                19.79 19.79 0 0 1-8.63-3.07
                19.5 19.5 0 0 1-6-6
                19.79 19.79 0 0 1-3.07-8.67
                A2 2 0 0 1 4.11 2h3
                a2 2 0 0 1 2 1.72
                c.12.96.36 1.9.7 2.79
                a2 2 0 0 1-.45 2.11L8.09 9.91
                a16 16 0 0 0 6 6l1.27-1.27
                a2 2 0 0 1 2.11-.45
                c.89.34 1.83.58 2.79.7
                A2 2 0 0 1 22 16.92z"/>
            </svg>
        </button>

        <!-- Contact information -->
        <button type="button" class="nt-header-btn"
                id="info-btn" title="Contact information"
                aria-label="Contact information">
            <svg viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 16v-4"/>
                <path d="M12 8h.01"/>
            </svg>
        </button>

    </div>
</header>


        <section
            class="messages-container nt-messages"
            id="messages-container"
            aria-live="polite"
            aria-label="Conversation messages"
        >
            <div class="nt-welcome">
                <div class="nt-welcome-symbol"><span>✦</span></div>

                <span class="nt-eyebrow">WELCOME TO NEXTALK</span>

                <h2>Your conversations<br>start here.</h2>

                <p>
                    Select someone from your conversations or explore
                    the people directory to start chatting.
                </p>

                <div class="nt-welcome-hint">
                    <span>✧</span> Simple. Fast. Reliable.
                </div>
            </div>
        </section>


<div class="message-input-area nt-composer">

    <div class="nt-composer-tools">

        <div class="emoji-picker-wrapper">
            <button type="button" class="icon-btn" id="emoji-btn">☺</button>

            <div id="emoji-picker" class="emoji-picker">
                <div id="emoji-categories" class="emoji-categories"></div>
                <div id="emoji-list" class="emoji-list"></div>
            </div>
        </div>

        <input type="file" id="image-input" accept="image/*" hidden>

        <button type="button" class="icon-btn" id="attach-btn">＋</button>

        <button type="button" class="icon-btn" id="voice-btn">♫</button>

    </div>

    <input
        type="text"
        id="message-input"
        placeholder="Write a message..."
        autocomplete="off"
    >

    <button type="button" class="send-btn nt-send">
        <span>➤</span>
    </button>

</div>


        <!-- IMAGE PREVIEW -->
        <div id="image-preview-modal" class="image-modal">
            <img id="preview-img" class="preview-img" alt="Image preview">
            <button type="button" id="close-preview" class="close-preview">×</button>
        </div>

    </main>


    <!-- =====================================
         ADDITIONAL WORKSPACE SECTIONS
         These stay inside the main app shell.
    ====================================== -->
    <section id="extra-workspace" class="nt-extra-workspace">


        <!-- LIBRARY -->
        <div id="section-library" class="nt-workspace-section">

            <div class="nt-section-top">
                <div>
                    <span class="nt-eyebrow">YOUR WORKSPACE</span>
                    <h1>Library</h1>
                    <p>Your shared files and saved media in one place.</p>
                </div>
                <div class="nt-section-icon">▤</div>
            </div>

            <div class="nt-library-toolbar">
                <input
                    type="search"
                    id="workspace-library-search"
                    placeholder="Search your library..."
                    aria-label="Search your library"
                >

                <select id="workspace-library-filter" aria-label="Filter library">
                    <option value="all">All files</option>
                    <option value="documents">Documents</option>
                    <option value="images">Images</option>
                    <option value="audio">Audio</option>
                    <option value="videos">Videos</option>
                </select>
            </div>

            <div class="nt-library-categories">
                <button type="button" class="active" data-library-category="all">All files</button>
                <button type="button" data-library-category="documents">Documents</button>
                <button type="button" data-library-category="images">Images</button>
                <button type="button" data-library-category="audio">Audio</button>
                <button type="button" data-library-category="videos">Videos</button>
            </div>

            <div class="nt-library-empty">
                <div class="nt-empty-icon">▤</div>
                <h2>Your library is ready</h2>
                <p>
                    Files shared in NexTalk will appear here.
                    File storage will be connected in the next stage.
                </p>
            </div>

        </div>


        <!-- PROFILE -->
        <div id="section-profile" class="nt-workspace-section">

            <div class="nt-section-top">
                <div>
                    <span class="nt-eyebrow">YOUR ACCOUNT</span>
                    <h1>My Profile</h1>
                    <p>Manage your NexTalk identity.</p>
                </div>
                <div class="nt-section-icon">◎</div>
            </div>

            <div class="nt-profile-panel">

                <img src="<?= $safeAvatar ?>" alt="Profile picture">

                <div class="nt-profile-panel-info">
                    <h2><?= $safeUsername ?></h2>
                    <p><span class="nt-online-dot"></span> Online</p>
                    <span>NexTalk member</span>
                </div>

                <button type="button" id="workspace-edit-profile">
                    Edit profile
                </button>

            </div>

            <div class="nt-information-card">
                <h3>Account information</h3>

                <div class="nt-information-row">
                    <span>Username</span>
                    <strong><?= $safeUsername ?></strong>
                </div>

                <div class="nt-information-row">
                    <span>Account ID</span>
                    <strong>#<?= $my_id ?></strong>
                </div>

                <div class="nt-information-row">
                    <span>Account status</span>
                    <strong class="nt-account-active">Active</strong>
                </div>
            </div>

        </div>



<!-- SETTINGS -->
<div id="section-settings" class="nt-workspace-section">

    <div class="nt-section-top">
        <div>
            <span class="nt-eyebrow">PREFERENCES</span>
            <h1>Settings</h1>
            <p>Personalise your NexTalk workspace.</p>
        </div>
        <div class="nt-section-icon">⚙</div>
    </div>

    <!-- APPEARANCE -->
    <div class="nt-settings-card">
        <div class="nt-settings-heading">
            <span class="nt-settings-icon">◐</span>
            <div>
                <h3>Appearance</h3>
                <p>Customise how NexTalk looks.</p>
            </div>
        </div>

        <div class="nt-settings-row">
            <div>
                <strong>Dark mode</strong>
                <p>Switch between light and dark appearance.</p>
            </div>
            <button type="button" id="workspace-theme-toggle"
                class="nt-switch" role="switch" aria-checked="false"
                aria-label="Dark mode">
                <span></span>
            </button>
        </div>

        <div class="nt-settings-row">
            <div>
                <strong>Compact layout</strong>
                <p>Reduce spacing to fit more content on screen.</p>
            </div>
            <button type="button" class="nt-switch"
                data-setting="compact" role="switch" aria-checked="false"
                aria-label="Compact layout">
                <span></span>
            </button>
        </div>

        <div class="nt-settings-row">
            <div>
                <strong>Font size</strong>
                <p>Adjust the text size across your workspace.</p>
            </div>
            <select id="setting-font-size" class="nt-settings-select">
                <option value="small">Small</option>
                <option value="medium" selected>Medium</option>
                <option value="large">Large</option>
            </select>
        </div>
    </div>

    <!-- NOTIFICATIONS -->
    <div class="nt-settings-card">
        <div class="nt-settings-heading">
            <span class="nt-settings-icon">♢</span>
            <div>
                <h3>Notifications</h3>
                <p>Choose your notification preferences.</p>
            </div>
        </div>

        <div class="nt-settings-row">
            <div>
                <strong>Browser notifications</strong>
                <p>Allow NexTalk to request browser notification permission.</p>
            </div>
            <button type="button" class="nt-switch"
                data-setting="notifications" role="switch"
                aria-checked="false" aria-label="Browser notifications">
                <span></span>
            </button>
        </div>

        <div class="nt-settings-row">
            <div>
                <strong>Notification sounds</strong>
                <p>Save your preference for message sounds.</p>
            </div>
            <button type="button" class="nt-switch enabled"
                data-setting="notificationSounds" role="switch"
                aria-checked="true" aria-label="Notification sounds">
                <span></span>
            </button>
        </div>

        <p id="notification-permission-status" class="nt-settings-note"
            aria-live="polite">Notification permission has not been requested.</p>
    </div>

    <!-- MESSAGING -->
    <div class="nt-settings-card">
        <div class="nt-settings-heading">
            <span class="nt-settings-icon">✉</span>
            <div>
                <h3>Messaging</h3>
                <p>Control how you send messages.</p>
            </div>
        </div>

        <div class="nt-settings-row">
            <div>
                <strong>Enter to send</strong>
                <p>Press Enter to send a message instead of using the button.</p>
            </div>
            <button type="button" class="nt-switch enabled"
                data-setting="enterToSend" role="switch"
                aria-checked="true" aria-label="Enter to send">
                <span></span>
            </button>
        </div>

        <div class="nt-settings-row">
            <div>
                <strong>Confirm before sending</strong>
                <p>Ask for confirmation before sending a message.</p>
            </div>
            <button type="button" class="nt-switch"
                data-setting="sendConfirmation" role="switch"
                aria-checked="false" aria-label="Confirm before sending">
                <span></span>
            </button>
        </div>
    </div>

    <!-- PRIVACY -->
    <div class="nt-settings-card">
        <div class="nt-settings-heading">
            <span class="nt-settings-icon">♙</span>
            <div>
                <h3>Privacy</h3>
                <p>Manage your personal preferences.</p>
            </div>
        </div>

        <div class="nt-settings-row">
            <div>
                <strong>Show online status</strong>
                <p>Save your preference for showing online status.</p>
            </div>
            <button type="button" class="nt-switch enabled"
                data-setting="showOnline" role="switch"
                aria-checked="true" aria-label="Show online status">
                <span></span>
            </button>
        </div>

        <div class="nt-settings-row">
            <div>
                <strong>Read receipts</strong>
                <p>Save your preference for message read receipts.</p>
            </div>
            <button type="button" class="nt-switch enabled"
                data-setting="readReceipts" role="switch"
                aria-checked="true" aria-label="Read receipts">
                <span></span>
            </button>
        </div>
    </div>

    <!-- ACCOUNT -->
    <div class="nt-settings-card">
        <div class="nt-settings-heading">
            <span class="nt-settings-icon">♙</span>
            <div>
                <h3>Account</h3>
                <p>Manage your NexTalk account.</p>
            </div>
        </div>

        <div class="nt-settings-row">
            <div>
                <strong>Account ID</strong>
                <p>Your unique NexTalk account number.</p>
            </div>
            <strong>#<?= $my_id ?></strong>
        </div>

        <div class="nt-settings-actions">
            <button type="button" id="settings-edit-profile"
                class="nt-settings-action">
                Edit profile
            </button>
            <a href="logout.php" class="nt-settings-danger">
                Sign out
            </a>
        </div>
    </div>

    <!-- ABOUT -->
    <div class="nt-settings-card">
        <div class="nt-settings-heading">
            <span class="nt-settings-icon">ⓘ</span>
            <div>
                <h3>About NexTalk</h3>
                <p>Connect. Communicate. Belong.</p>
            </div>
        </div>

        <div class="nt-settings-row">
            <span>Application version</span>
            <strong>1.0.0</strong>
        </div>

        <div class="nt-settings-row">
            <span>Application</span>
            <strong>NexTalk</strong>
        </div>
    </div>

    <!-- RESET -->
    <div class="nt-settings-bottom">
        <p>Preferences are saved in this browser on this device.</p>
        <button type="button" id="reset-settings" class="nt-reset-settings">
            Reset preferences
        </button>
    </div>

</div>


    </section>

</div>


<!-- =====================================
     PROFILE EDIT MODAL
====================================== -->
<div id="profile-modal" class="modal-overlay">

    <div class="modal-content nt-modal">

        <div class="nt-modal-heading">
            <div>
                <span class="nt-eyebrow">ACCOUNT SETTINGS</span>
                <h2>Edit your profile</h2>
                <p>Make your NexTalk profile yours.</p>
            </div>

            <button
                type="button"
                id="profile-cancel"
                class="nt-modal-x"
                aria-label="Close"
            >×</button>
        </div>

        <div class="avatar-preview-wrapper">
            <img
                id="modal-avatar-preview"
                src="<?= $safeAvatar ?>"
                class="modal-avatar"
                alt="Profile preview"
            >

            <label for="modal-avatar-input" class="camera-badge">📷</label>
            <input type="file" id="modal-avatar-input" accept="image/*" hidden>
        </div>

        <div class="avatar-tabs">
            <button type="button" class="avatar-tab active-tab" data-tab="upload">Upload</button>
            <button type="button" class="avatar-tab" data-tab="camera">Camera</button>
            <button type="button" class="avatar-tab" data-tab="avatars">Avatars</button>
        </div>

        <div id="tab-upload" class="avatar-tab-content" style="display:block;">
            <p class="nt-modal-help">Choose a picture using the camera icon above.</p>
        </div>

        <div id="tab-camera" class="avatar-tab-content" style="display:none;">
            <video id="camera-video" autoplay playsinline class="camera-video"></video>
            <button type="button" id="capture-btn" class="capture-btn">Take photo</button>
        </div>

        <div id="tab-avatars" class="avatar-tab-content" style="display:none;">
            <div class="avatars-grid">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix" class="default-avatar" alt="Felix">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Aneka" class="default-avatar" alt="Aneka">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Jack" class="default-avatar" alt="Jack">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Mia" class="default-avatar" alt="Mia">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Leo" class="default-avatar" alt="Leo">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Zoe" class="default-avatar" alt="Zoe">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Max" class="default-avatar" alt="Max">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Luna" class="default-avatar" alt="Luna">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Sam" class="default-avatar" alt="Sam">
                <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Nala" class="default-avatar" alt="Nala">
            </div>
        </div>

        <input type="hidden" id="selected-default-avatar" value="">
        <input type="hidden" id="camera-captured-data" value="">

        <label class="nt-field-label" for="modal-username-input">Username</label>

        <input
            type="text"
            id="modal-username-input"
            value="<?= $safeUsername ?>"
            placeholder="Your username"
            class="modal-username-input"
        >

        <div class="modal-actions">
            <button type="button" id="profile-save" class="btn-save">
                Save changes
            </button>
        </div>

    </div>
</div>


<!-- =====================================
     CALL MODAL
====================================== -->
<div id="call-modal" class="modal-overlay">
    <div class="modal-content nt-modal nt-small-modal">
        <h2>Audio call</h2>
        <img id="call-avatar" src="" class="modal-avatar" alt="Contact">
        <h3 id="call-username"></h3>
        <p>Calling will be available in a future update.</p>

        <div class="modal-actions">
            <button type="button" id="call-close" class="btn-cancel">Close</button>
        </div>
    </div>
</div>


<!-- =====================================
     CONTACT INFORMATION MODAL
====================================== -->
<div id="info-modal" class="modal-overlay">
    <div class="modal-content nt-modal nt-small-modal">
        <h2>Contact information</h2>
        <img id="info-avatar" src="" class="modal-avatar" alt="Contact">
        <h3 id="info-username"></h3>
        <p id="info-status"></p>
        <p class="nt-modal-help">NexTalk member</p>

        <div class="modal-actions">
            <button type="button" id="info-close" class="btn-cancel">Close</button>
        </div>
    </div>
</div>


<footer class="app-footer nt-app-footer">
    <span>© <?= date('Y') ?> NexTalk</span>
    <span>Made for meaningful conversations.</span>
</footer>


<script>
    const MY_ID = <?= $my_id ?>;

    const MY_AVATAR = <?= json_encode(
        $my_avatar,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    ) ?>;

    (function () {
        const savedTheme = localStorage.getItem('nextalk-theme');

        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }
    })();
</script>

<!-- Existing application scripts -->
<script src="assets/js/chat.js?v=<?= time() ?>"></script>
<script src="assets/js/groups.js?v=<?= time() ?>"></script>
<script src="assets/js/workspace.js?v=2"></script>
<script src="assets/js/modules/standard-emoji.js?v=1"></script>

<script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>

</body>
</html>
