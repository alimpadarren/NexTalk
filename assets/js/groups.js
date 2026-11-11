
document.addEventListener('DOMContentLoaded', () => {
    const tabMessages = document.getElementById('tab-messages');
    const tabUsers = document.getElementById('tab-users');
    const tabGroups = document.getElementById('tab-groups');

    const messagesView = document.getElementById('messages-list-view');
    const usersView = document.getElementById('users-list-view');
    const groupsView = document.getElementById('groups-list-view');

    const groupWrapper = document.getElementById('create-group-wrapper');
    const createGroupBtn = document.getElementById('create-group-btn');

    const chatName = document.getElementById('current-chat-name');
    const chatAvatar = document.getElementById('current-chat-avatar');
    const messagesContainer = document.getElementById('messages-container');
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.querySelector('.send-btn');

    let selectedGroupId = null;
    let refreshTimer = null;
    let loadingMessages = false;

    if (!tabGroups || !groupsView || !groupWrapper || !createGroupBtn) {
        console.error('NexTalk Groups: Required page elements are missing.');
        return;
    }

    // -----------------------------
    // HELPERS
    // -----------------------------

    async function apiRequest(url, options = {}) {
        const response = await fetch(url, options);

        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error('The server returned an invalid response.');
        }

        if (!response.ok) {
            throw new Error(data.message || 'The request failed.');
        }

        return data;
    }

    function makeElement(tag, className, text) {
        const element = document.createElement(tag);

        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;

        return element;
    }

    function getGroupAvatar(id) {
        return `https://api.dicebear.com/7.x/identicon/svg?seed=nextalk-group-${encodeURIComponent(id)}`;
    }

    function formatMessageTime(value) {
        if (!value) return '';

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) return '';

        return date.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // -----------------------------
    // GROUP NAVIGATION
    // -----------------------------

    function showGroupsTab() {
        [tabMessages, tabUsers, tabGroups].forEach(tab => {
            if (tab) tab.classList.remove('active');
        });

        tabGroups.classList.add('active');

        if (messagesView) messagesView.style.display = 'none';
        if (usersView) usersView.style.display = 'none';

        groupsView.style.display = 'block';

        // Show the wrapper, not only its child button.
        groupWrapper.style.display = 'block';
        createGroupBtn.style.display = 'block';

        loadGroups();
    }

    tabGroups.addEventListener('click', event => {
        event.preventDefault();
        event.stopImmediatePropagation();

        showGroupsTab();
    }, true);

    [tabMessages, tabUsers].forEach(tab => {
        if (!tab) return;

        tab.addEventListener('click', () => {
            selectedGroupId = null;
            groupWrapper.style.display = 'none';
            groupsView.style.display = 'none';

            if (refreshTimer) {
                clearInterval(refreshTimer);
                refreshTimer = null;
            }
        }, true);
    });

    // -----------------------------
    // GROUP LIST
    // -----------------------------

    async function loadGroups(autoSelectId = null) {
        groupsView.innerHTML = '';

        const loading = makeElement(
            'p',
            '',
            'Loading your groups...'
        );

        loading.style.cssText =
            'padding:20px;text-align:center;color:#64748b;';

        groupsView.appendChild(loading);

        try {
            const groups = await apiRequest('api/get-groups.php');

            groupsView.innerHTML = '';

            if (!Array.isArray(groups) || groups.length === 0) {
                const empty = makeElement(
                    'p',
                    '',
                    'No groups yet. Create your first group!'
                );

                empty.style.cssText =
                    'padding:25px 20px;text-align:center;color:#64748b;line-height:1.6;';

                groupsView.appendChild(empty);
                return;
            }

            groups.forEach(group => {
                const row = makeElement('div', 'contact group-contact');
                row.dataset.groupId = group.id;

                const avatar = makeElement('img');
                avatar.src = getGroupAvatar(group.id);
                avatar.alt = 'Group';

                const info = makeElement('div', 'contact-info');
                const heading = makeElement('div', 'contact-header');
                const name = makeElement('h4', '', group.name);

                const count = Number(group.member_count) || 0;
                const members = makeElement(
                    'p',
                    '',
                    `${count} member${count === 1 ? '' : 's'}`
                );

                heading.appendChild(name);
                info.appendChild(heading);
                info.appendChild(members);

                row.appendChild(avatar);
                row.appendChild(info);

                row.addEventListener('click', () => {
                    selectGroup(group, row);
                });

                groupsView.appendChild(row);

                if (String(group.id) === String(autoSelectId)) {
                    selectGroup(group, row);
                }
            });

        } catch (error) {
            groupsView.innerHTML = '';

            const errorMessage = makeElement(
                'p',
                '',
                error.message || 'Could not load groups.'
            );

            errorMessage.style.cssText =
                'padding:20px;color:#ef4444;text-align:center;';

            groupsView.appendChild(errorMessage);
        }
    }

    // -----------------------------
    // SELECT A GROUP
    // -----------------------------

    function selectGroup(group, row) {
        selectedGroupId = Number(group.id);

        groupsView.querySelectorAll('.contact').forEach(item => {
            item.classList.remove('active');
        });

        row.classList.add('active');

        chatName.textContent = group.name;
        chatAvatar.src = getGroupAvatar(group.id);

        const status = document.querySelector('.chat-header .status');

        if (status) {
            status.textContent =
                `${Number(group.member_count) || 0} group members`;
        }

        messageInput.placeholder = `Message ${group.name}...`;

        loadGroupMessages();

        if (refreshTimer) clearInterval(refreshTimer);

        refreshTimer = setInterval(() => {
            if (selectedGroupId) loadGroupMessages(true);
        }, 2500);
    }

    // -----------------------------
    // GROUP MESSAGES
    // -----------------------------

    async function loadGroupMessages(silent = false) {
        if (!selectedGroupId || loadingMessages) return;

        loadingMessages = true;

        try {
            const messages = await apiRequest(
                `api/fetch-group-messages.php?group_id=${encodeURIComponent(selectedGroupId)}`
            );

            if (!Array.isArray(messages)) {
                throw new Error('Invalid group messages response.');
            }

            // Ignore an old response if the user switched groups.
            if (!selectedGroupId) return;

            messagesContainer.innerHTML = '';

            if (messages.length === 0) {
                const empty = makeElement(
                    'p',
                    '',
                    'No messages in this group yet. Start the conversation!'
                );

                empty.style.cssText =
                    'text-align:center;color:#94a3b8;margin-top:25px;padding:10px;';

                messagesContainer.appendChild(empty);
                return;
            }

            messages.forEach(message => {
                const isMe = Number(message.sender_id) === Number(MY_ID);

                const messageRow = makeElement(
                    'div',
                    `message ${isMe ? 'sent' : 'received'}`
                );

                if (!isMe) {
                    const avatar = makeElement('img', 'msg-avatar');
                    avatar.src = message.avatar || '';
                    avatar.alt = message.username || 'Member';
                    messageRow.appendChild(avatar);
                }

                const content = makeElement('div', 'msg-content');

                if (!isMe) {
                    const sender = makeElement(
                        'small',
                        '',
                        message.username || 'Member'
                    );

                    sender.style.cssText =
                        'display:block;color:#94a3b8;font-size:11px;margin-bottom:4px;';

                    content.appendChild(sender);
                }

                if (message.image_path) {
                    const image = makeElement('img', 'chat-image');
                    image.src = message.image_path;
                    image.alt = 'Shared image';
                    image.style.cssText =
                        'max-width:250px;border-radius:12px;display:block;';

                    content.appendChild(image);

                } else if (message.audio_path) {
                    const audio = makeElement('audio');
                    audio.controls = true;
                    audio.src = message.audio_path;

                    content.appendChild(audio);

                } else {
                    const bubble = makeElement(
                        'div',
                        'msg-bubble',
                        message.message || ''
                    );

                    content.appendChild(bubble);
                }

                const time = makeElement(
                    'span',
                    'msg-time',
                    formatMessageTime(message.created_at) + (isMe ? ' ✓✓' : '')
                );

                content.appendChild(time);
                messageRow.appendChild(content);

                if (isMe) {
                    const avatar = makeElement('img', 'msg-avatar');
                    avatar.src = MY_AVATAR || '';
                    avatar.alt = 'You';
                    messageRow.appendChild(avatar);
                }

                messagesContainer.appendChild(messageRow);
            });

            messagesContainer.scrollTop = messagesContainer.scrollHeight;

        } catch (error) {
            if (!silent) {
                messagesContainer.innerHTML = '';

                const errorMessage = makeElement(
                    'p',
                    '',
                    error.message || 'Could not load messages.'
                );

                errorMessage.style.cssText =
                    'text-align:center;color:#ef4444;padding:20px;';

                messagesContainer.appendChild(errorMessage);
            }

            console.error('NexTalk group messages:', error);

        } finally {
            loadingMessages = false;
        }
    }

    // -----------------------------
    // CREATE GROUP MODAL
    // -----------------------------

    const modal = makeElement('div', 'nextalk-group-overlay');

    modal.style.cssText = `
        display:none;
        position:fixed;
        inset:0;
        z-index:99999;
        background:rgba(15,23,42,.55);
        align-items:center;
        justify-content:center;
        padding:20px;
    `;

    const panel = makeElement('div', 'nextalk-group-panel');

    panel.style.cssText = `
        width:100%;
        max-width:460px;
        max-height:85vh;
        overflow-y:auto;
        background:var(--group-modal-bg,#fff);
        color:var(--group-modal-text,#0f172a);
        border-radius:18px;
        padding:24px;
        box-shadow:0 20px 60px rgba(0,0,0,.2);
    `;

    const title = makeElement('h2', '', 'Create a group');
    title.style.marginBottom = '7px';

    const description = makeElement(
        'p',
        '',
        'Give your group a name and choose the people you want to add.'
    );

    description.style.cssText =
        'color:#64748b;font-size:13px;line-height:1.5;margin-bottom:18px;';

    const nameInput = makeElement('input');
    nameInput.type = 'text';
    nameInput.maxLength = 100;
    nameInput.placeholder = 'Enter group name';

    nameInput.style.cssText = `
        width:100%;
        padding:13px;
        border:1px solid #cbd5e1;
        border-radius:10px;
        outline:none;
        margin-bottom:18px;
        font-size:14px;
    `;

    const memberHeading = makeElement(
        'h3',
        '',
        'Select members'
    );

    memberHeading.style.cssText =
        'font-size:14px;margin-bottom:10px;';

    const memberList = makeElement('div');

    memberList.style.cssText = `
        max-height:260px;
        overflow-y:auto;
        border:1px solid #e2e8f0;
        border-radius:12px;
        padding:8px;
        margin-bottom:20px;
    `;

    const actions = makeElement('div');

    actions.style.cssText =
        'display:flex;justify-content:flex-end;gap:10px;';

    const cancelBtn = makeElement('button', '', 'Cancel');
    const saveBtn = makeElement('button', '', 'Create Group');

    cancelBtn.type = 'button';
    saveBtn.type = 'button';

    cancelBtn.style.cssText = `
        padding:11px 16px;
        border:1px solid #cbd5e1;
        border-radius:9px;
        background:transparent;
        cursor:pointer;
    `;

    saveBtn.style.cssText = `
        padding:11px 18px;
        border:0;
        border-radius:9px;
        background:#0052ff;
        color:white;
        font-weight:600;
        cursor:pointer;
    `;

    actions.append(cancelBtn, saveBtn);

    panel.append(
        title,
        description,
        nameInput,
        memberHeading,
        memberList,
        actions
    );

    modal.appendChild(panel);
    document.body.appendChild(modal);

    function closeModal() {
        modal.style.display = 'none';
        nameInput.value = '';
        memberList.innerHTML = '';
    }

    cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', event => {
        if (event.target === modal) closeModal();
    });

    async function openCreateGroupModal() {
        nameInput.value = '';
        memberList.innerHTML = '';

        const loading = makeElement('p', '', 'Loading users...');
        loading.style.cssText = 'padding:15px;color:#64748b;';
        memberList.appendChild(loading);

        modal.style.display = 'flex';

        try {
            const users = await apiRequest('api/users.php');

            memberList.innerHTML = '';

            if (!Array.isArray(users) || users.length === 0) {
                const empty = makeElement(
                    'p',
                    '',
                    'No other users are available yet.'
                );

                empty.style.cssText =
                    'padding:15px;color:#64748b;text-align:center;';

                memberList.appendChild(empty);
                return;
            }

            users.forEach(user => {
                const label = makeElement('label');

                label.style.cssText = `
                    display:flex;
                    align-items:center;
                    gap:10px;
                    padding:10px;
                    border-radius:9px;
                    cursor:pointer;
                `;

                const checkbox = makeElement('input');
                checkbox.type = 'checkbox';
                checkbox.value = user.id;
                checkbox.className = 'nextalk-group-member';

                const avatar = makeElement('img');
                avatar.src = user.avatar || '';
                avatar.alt = '';
                avatar.style.cssText =
                    'width:36px;height:36px;border-radius:50%;object-fit:cover;';

                const userInfo = makeElement('span');
                userInfo.style.cssText =
                    'display:flex;flex-direction:column;gap:3px;';

                const username = makeElement(
                    'strong',
                    '',
                    user.username || 'User'
                );

                username.style.fontSize = '13px';

                const status = makeElement(
                    'small',
                    '',
                    Number(user.is_online) === 1 ? 'Online' : 'Offline'
                );

                status.style.color = '#94a3b8';

                userInfo.append(username, status);
                label.append(checkbox, avatar, userInfo);

                label.addEventListener('mouseenter', () => {
                    label.style.background = '#f1f5f9';
                });

                label.addEventListener('mouseleave', () => {
                    label.style.background = 'transparent';
                });

                memberList.appendChild(label);
            });

        } catch (error) {
            memberList.innerHTML = '';

            const errorMessage = makeElement(
                'p',
                '',
                error.message || 'Could not load users.'
            );

            errorMessage.style.cssText =
                'padding:15px;color:#ef4444;';

            memberList.appendChild(errorMessage);
        }
    }

    createGroupBtn.addEventListener('click', event => {
        event.preventDefault();
        event.stopImmediatePropagation();

        openCreateGroupModal();
    }, true);

    saveBtn.addEventListener('click', async () => {
        const name = nameInput.value.trim();

        if (name.length < 2) {
            alert('Group name must contain at least 2 characters.');
            nameInput.focus();
            return;
        }

        const members = Array.from(
            memberList.querySelectorAll('.nextalk-group-member:checked')
        ).map(input => Number(input.value));

        saveBtn.disabled = true;
        saveBtn.textContent = 'Creating...';

        try {
            const result = await apiRequest('api/create-group.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    name,
                    members
                })
            });

            if (result.status !== 'success') {
                throw new Error(result.message || 'Group creation failed.');
            }

            closeModal();

            await loadGroups(result.group_id);

            alert('Your group has been created successfully!');

        } catch (error) {
            alert(error.message || 'Could not create group.');

        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Create Group';
        }
    });

    // -----------------------------
    // SEND GROUP MESSAGE
    // -----------------------------

    async function sendGroupMessage(event) {
        if (!selectedGroupId) return;

        event.preventDefault();
        event.stopImmediatePropagation();

        const message = messageInput.value.trim();

        if (!message) return;

        const groupId = selectedGroupId;

        try {
            const result = await apiRequest('api/send-group-message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    group_id: groupId,
                    message
                })
            });

            if (result.status !== 'success') {
                throw new Error(result.message || 'Message could not be sent.');
            }

            messageInput.value = '';

            if (selectedGroupId === groupId) {
                await loadGroupMessages();
            }

        } catch (error) {
            alert(error.message || 'Message could not be sent.');
        }
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', sendGroupMessage, true);
    }

    if (messageInput) {
        messageInput.addEventListener('keydown', event => {
            if (event.key === 'Enter' && selectedGroupId) {
                sendGroupMessage(event);
            }
        }, true);
    }

    // Prevent private-chat upload endpoints from receiving group messages.
    const attachBtn = document.getElementById('attach-btn');
    const voiceBtn = document.getElementById('voice-btn');

    [attachBtn, voiceBtn].forEach(button => {
        if (!button) return;

        button.addEventListener('click', event => {
            if (!selectedGroupId) return;

            event.preventDefault();
            event.stopImmediatePropagation();

            alert('Group image and voice sharing will be added in the next update. Text messaging is ready.');
        }, true);
    });

    // -----------------------------
    // INITIAL STATE
    // -----------------------------

    groupWrapper.style.display = 'none';

    console.log('NexTalk Groups controller loaded successfully.');
});
