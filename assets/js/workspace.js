
document.addEventListener('DOMContentLoaded', () => {
    const app = document.querySelector('.nextalk-app');
    const extraWorkspace = document.getElementById('extra-workspace');

    if (!app || !extraWorkspace) return;

    // ----------------------------------------
    // WORKSPACE ELEMENTS
    // ----------------------------------------

    const normalTabs = {
        messages: document.getElementById('tab-messages'),
        people: document.getElementById('tab-users'),
        groups: document.getElementById('tab-groups')
    };

    const extraTabs = {
        library: document.getElementById('tab-library'),
        profile: document.getElementById('tab-profile'),
        settings: document.getElementById('tab-settings')
    };

    const sections = {
        library: document.getElementById('section-library'),
        profile: document.getElementById('section-profile'),
        settings: document.getElementById('section-settings')
    };

    const chatList = document.querySelector('.nt-conversations');
    const chatArea = document.querySelector('.nt-chat-area');

    const allTabs = [
        ...Object.values(normalTabs),
        ...Object.values(extraTabs)
    ].filter(Boolean);

    // ----------------------------------------
    // NAVIGATION
    // ----------------------------------------

    function setActiveTab(tab) {
        allTabs.forEach(item => item.classList.remove('active'));

        if (tab) {
            tab.classList.add('active');
        }
    }

    function showExtraSection(name, updateHash = true) {
        if (!sections[name]) return;

        app.classList.add('nt-extra-open');
        extraWorkspace.style.display = 'block';

        if (chatList) chatList.style.display = 'none';
        if (chatArea) chatArea.style.display = 'none';

        Object.entries(sections).forEach(([key, section]) => {
            if (section) {
                section.style.display = key === name ? 'block' : 'none';
            }
        });

        setActiveTab(extraTabs[name]);

        if (updateHash && window.location.hash !== `#${name}`) {
            history.pushState({ section: name }, '', `#${name}`);
        }
    }

    function showChatSection(name, updateHash = true) {
        extraWorkspace.style.display = 'none';
        app.classList.remove('nt-extra-open');

        if (chatList) chatList.style.display = '';
        if (chatArea) chatArea.style.display = '';

        setActiveTab(normalTabs[name] || normalTabs.messages);

        if (updateHash && window.location.hash !== `#${name}`) {
            history.pushState({ section: name }, '', `#${name}`);
        }
    }

    Object.entries(extraTabs).forEach(([name, tab]) => {
        if (!tab) return;

        tab.addEventListener('click', event => {
            event.preventDefault();
            showExtraSection(name);
        });
    });

    Object.entries(normalTabs).forEach(([name, tab]) => {
        if (!tab) return;

        tab.addEventListener('click', () => {
            showChatSection(name);
        });
    });

    function openFromHash() {
        const name = window.location.hash
            .replace('#', '')
            .toLowerCase();

        if (sections[name]) {
            showExtraSection(name, false);
            return;
        }

        if (normalTabs[name]) {
            showChatSection(name, false);

            // Let chat.js and groups.js update their own content.
            normalTabs[name].click();
            return;
        }

        showChatSection('messages', false);
        normalTabs.messages?.click();
    }

    window.addEventListener('hashchange', openFromHash);
    window.addEventListener('popstate', openFromHash);

    // ----------------------------------------
    // PROFILE SHORTCUTS
    // ----------------------------------------

    const editProfileButton =
        document.getElementById('workspace-edit-profile');

    const settingsProfileButton =
        document.getElementById('settings-edit-profile');

    const profileModalButton =
        document.getElementById('open-profile-modal');

    [editProfileButton, settingsProfileButton].forEach(button => {
        if (!button || !profileModalButton) return;

        button.addEventListener('click', () => {
            profileModalButton.click();
        });
    });

    // ----------------------------------------
    // THEME
    // ----------------------------------------

    const themeButton =
        document.getElementById('workspace-theme-toggle');

    const sidebarThemeButton =
        document.getElementById('theme-toggle');

    function updateThemeSwitch() {
        const isDark = document.body.classList.contains('dark-mode');

        if (themeButton) {
            themeButton.classList.toggle('enabled', isDark);
            themeButton.setAttribute('aria-checked', String(isDark));
        }
    }

    function saveTheme(isDark) {
        document.body.classList.toggle('dark-mode', isDark);

        try {
            localStorage.setItem(
                'nextalk-theme',
                isDark ? 'dark' : 'light'
            );
        } catch (error) {
            console.warn('Could not save theme preference.', error);
        }

        updateThemeSwitch();
    }

    if (themeButton) {
        themeButton.addEventListener('click', () => {
            const isDark =
                !document.body.classList.contains('dark-mode');

            saveTheme(isDark);
        });
    }

    if (sidebarThemeButton) {
        sidebarThemeButton.addEventListener('click', () => {
            // chat.js may also handle the sidebar theme button.
            setTimeout(updateThemeSwitch, 0);
        });
    }

    updateThemeSwitch();

    // ----------------------------------------
    // SETTINGS PREFERENCES
    // ----------------------------------------

    const STORAGE_KEY = 'nextalk-preferences';

    const defaultPreferences = {
        compact: false,
        fontSize: 'medium',
        notifications: false,
        notificationSounds: true,
        enterToSend: true,
        sendConfirmation: false,
        showOnline: true,
        readReceipts: true
    };

    let preferences = { ...defaultPreferences };

    try {
        const saved = localStorage.getItem(STORAGE_KEY);

        if (saved) {
            const parsed = JSON.parse(saved);

            if (parsed && typeof parsed === 'object') {
                preferences = {
                    ...defaultPreferences,
                    ...parsed
                };
            }
        }
    } catch (error) {
        console.warn('Could not load NexTalk preferences.', error);
    }

    function savePreferences() {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(preferences)
            );
        } catch (error) {
            console.warn('Could not save NexTalk preferences.', error);
        }
    }

    function applyPreferences() {
        document.body.classList.toggle(
            'nt-compact',
            preferences.compact
        );

        document.body.classList.remove(
            'nt-font-small',
            'nt-font-medium',
            'nt-font-large'
        );

        const allowedSizes = ['small', 'medium', 'large'];

        if (!allowedSizes.includes(preferences.fontSize)) {
            preferences.fontSize = 'medium';
        }

        document.body.classList.add(
            `nt-font-${preferences.fontSize}`
        );

        document.querySelectorAll('[data-setting]').forEach(button => {
            const key = button.dataset.setting;

            if (!(key in preferences)) return;

            const enabled = Boolean(preferences[key]);

            button.classList.toggle('enabled', enabled);
            button.setAttribute('aria-checked', String(enabled));
        });

        const fontSelect =
            document.getElementById('setting-font-size');

        if (fontSelect) {
            fontSelect.value = preferences.fontSize;
        }

        updateNotificationStatus();
    }

    // ----------------------------------------
    // NOTIFICATION PERMISSION
    // ----------------------------------------

    const notificationStatus =
        document.getElementById('notification-permission-status');

    function updateNotificationStatus() {
        if (!notificationStatus) return;

        if (!('Notification' in window)) {
            notificationStatus.textContent =
                'This browser does not support notifications.';
            return;
        }

        notificationStatus.textContent =
            `Browser permission: ${Notification.permission}.`;
    }

    async function requestNotificationPermission() {
        if (!('Notification' in window)) {
            preferences.notifications = false;
            applyPreferences();
            return;
        }

        if (Notification.permission === 'granted') {
            preferences.notifications = true;
            savePreferences();
            applyPreferences();
            return;
        }

        if (Notification.permission === 'denied') {
            preferences.notifications = false;
            savePreferences();
            applyPreferences();

            if (notificationStatus) {
                notificationStatus.textContent =
                    'Notifications are blocked in your browser settings.';
            }
            return;
        }

        try {
            const permission =
                await Notification.requestPermission();

            preferences.notifications = permission === 'granted';

            savePreferences();
            applyPreferences();
        } catch (error) {
            preferences.notifications = false;
            savePreferences();
            applyPreferences();

            console.warn('Notification permission request failed.', error);
        }
    }

    // ----------------------------------------
    // SETTING SWITCHES
    // ----------------------------------------

    document.querySelectorAll('[data-setting]').forEach(button => {
        button.addEventListener('click', async () => {
            const key = button.dataset.setting;

            if (!(key in preferences)) return;

            const nextValue = !preferences[key];

            if (key === 'notifications' && nextValue) {
                await requestNotificationPermission();
                return;
            }

            preferences[key] = nextValue;

            savePreferences();
            applyPreferences();
        });
    });

    // ----------------------------------------
    // FONT SIZE
    // ----------------------------------------

    const fontSelect =
        document.getElementById('setting-font-size');

    if (fontSelect) {
        fontSelect.addEventListener('change', () => {
            const allowedSizes = ['small', 'medium', 'large'];

            if (!allowedSizes.includes(fontSelect.value)) return;

            preferences.fontSize = fontSelect.value;

            savePreferences();
            applyPreferences();
        });
    }

    // ----------------------------------------
    // RESET SETTINGS
    // ----------------------------------------

    const resetButton =
        document.getElementById('reset-settings');

    if (resetButton) {
        resetButton.addEventListener('click', () => {
            const confirmed = window.confirm(
                'Reset all NexTalk preferences to their defaults?'
            );

            if (!confirmed) return;

            preferences = { ...defaultPreferences };

            savePreferences();
            applyPreferences();
            saveTheme(false);
        });
    }

    // ----------------------------------------
    // ENTER TO SEND / SEND CONFIRMATION
    // ----------------------------------------

    document.addEventListener('keydown', event => {
        const input = event.target;

        if (!input || input.id !== 'message-input') return;
        if (event.key !== 'Enter' || event.shiftKey) return;

        if (!preferences.enterToSend) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        if (preferences.sendConfirmation) {
            const confirmed = window.confirm('Send this message?');

            if (!confirmed) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }
    }, true);

    const sendButton = document.querySelector('.nt-send');

    if (sendButton) {
        sendButton.addEventListener('click', event => {
            if (!preferences.sendConfirmation) return;

            const confirmed = window.confirm('Send this message?');

            if (!confirmed) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        }, true);
    }

    // ----------------------------------------
    // INITIALISE
    // ----------------------------------------

    applyPreferences();
    openFromHash();
});
