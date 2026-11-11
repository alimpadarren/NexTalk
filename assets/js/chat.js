document.addEventListener('DOMContentLoaded', () => {
    // ============ CORE ============
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.querySelector('.send-btn');
    const messagesContainer = document.getElementById('messages-container');
    const searchInput = document.querySelector('.search-bar input');
    const tabMessages = document.getElementById('tab-messages');
    const tabUsers = document.getElementById('tab-users');
    const tabGroups = document.getElementById('tab-groups');
    const messagesView = document.getElementById('messages-list-view');
    const usersView = document.getElementById('users-list-view');
    const groupsView = document.getElementById('groups-list-view');
    const createGroupBtn = document.getElementById('create-group-btn');
    const chatName = document.getElementById('current-chat-name');
    const chatAvatar = document.getElementById('current-chat-avatar');

    let currentReceiverId = null;
    let currentGroupId = null;

    // ============ DARK MODE ============
    const themeToggle = document.getElementById('theme-toggle');
    function updateThemeButton() {
        if (themeToggle) themeToggle.innerHTML = document.body.classList.contains('dark-mode') ? '☀️ Light Mode' : '🌙 Dark Mode';
    }
    updateThemeButton();
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('nextalk-theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
            updateThemeButton();
        });
    }

    // ============ TABS ============
    function switchTab(tab) {
        [tabMessages, tabUsers, tabGroups].forEach(t => t && t.classList.remove('active'));
        messagesView.style.display = 'none';
        usersView.style.display = 'none';
        groupsView.style.display = 'none';
        if (createGroupBtn) createGroupBtn.style.display = 'none';

        if (tab === 'messages') {
            tabMessages.classList.add('active');
            messagesView.style.display = 'block';
            loadChats();
        } else if (tab === 'users') {
            tabUsers.classList.add('active');
            usersView.style.display = 'block';
            loadUsers();
        } else if (tab === 'groups') {
            tabGroups.classList.add('active');
            groupsView.style.display = 'block';
            if (createGroupBtn) createGroupBtn.style.display = 'block';
            loadGroups();
        }
    }

    if (tabMessages) tabMessages.addEventListener('click', (e) => { e.preventDefault(); switchTab('messages'); });
    if (tabUsers) tabUsers.addEventListener('click', (e) => { e.preventDefault(); switchTab('users'); });
    if (tabGroups) tabGroups.addEventListener('click', (e) => { e.preventDefault(); switchTab('groups'); });

    // ============ LOAD CHATS ============
    async function loadChats() {
        try {
            const res = await fetch('api/get-chats.php');
            const chats = await res.json();
            messagesView.innerHTML = '';
            if (!chats || chats.length === 0) {
                messagesView.innerHTML = '<p style="padding: 20px; color: #64748b; text-align:center;">No conversations yet.</p>';
                return;
            }
            chats.forEach(chat => {
                const timeStr = formatTime(chat.last_message_time);
                const unreadBadge = chat.unread_count > 0 ? `<span class="unread-badge">${chat.unread_count}</span>` : '';
                const preview = (!chat.last_message) ? '📷 Photo' : (chat.last_message.length > 30 ? chat.last_message.substring(0, 30) + '...' : chat.last_message);
                const html = `
                    <div class="contact ${chat.id == currentReceiverId ? 'active' : ''}" data-id="${chat.id}" data-name="${escapeHtml(chat.username)}" data-avatar="${chat.avatar}">
                        <img src="${chat.avatar}" alt="">
                        <div class="contact-info">
                            <div class="contact-header"><h4>${escapeHtml(chat.username)}</h4><span class="time">${timeStr}</span></div>
                            <p>${escapeHtml(preview)}</p>
                        </div>
                        ${unreadBadge}
                    </div>
                `;
                messagesView.insertAdjacentHTML('beforeend', html);
            });
            attachContactClicks();
        } catch (e) { console.error(e); }
    }

    // ============ LOAD USERS ============
    async function loadUsers() {
        try {
            const res = await fetch('api/users.php');
            const users = await res.json();
            usersView.innerHTML = '';
            if (!users || users.length === 0) {
                usersView.innerHTML = '<p style="padding: 20px; color: #64748b; text-align:center;">No other users.</p>';
                return;
            }
            users.forEach(user => {
                const dot = user.is_online == 1 ? '<span class="dot" style="background:#10b981;"></span>' : '<span class="dot" style="background:#94a3b8;"></span>';
                const txt = user.is_online == 1 ? 'Online' : formatLastSeen(user.last_seen);
                const html = `
                    <div class="contact" data-id="${user.id}" data-name="${escapeHtml(user.username)}" data-avatar="${user.avatar}">
                        <img src="${user.avatar}" alt="">
                        <div class="contact-info">
                            <div class="contact-header"><h4>${escapeHtml(user.username)}</h4></div>
                            <p style="display:flex; align-items:center; gap:5px;">${dot} ${txt}</p>
                        </div>
                    </div>
                `;
                usersView.insertAdjacentHTML('beforeend', html);
            });
            attachContactClicks();
        } catch (e) { console.error(e); }
    }

    // ============ LOAD GROUPS ============
    async function loadGroups() {
        try {
            const res = await fetch('api/get-groups.php');
            const groups = await res.json();
            groupsView.innerHTML = '';
            if (!groups || groups.length === 0) {
                groupsView.innerHTML = '<p style="padding:20px; color:#64748b; text-align:center;">No groups yet.<br>Click "Create Group" above!</p>';
                return;
            }
            groups.forEach(g => {
                const avatar = `https://api.dicebear.com/7.x/identicon/svg?seed=${g.id}`;
                const html = `
                    <div class="contact group-contact" data-id="${g.id}" data-name="${escapeHtml(g.name)}" data-avatar="${avatar}">
                        <img src="${avatar}" alt="">
                        <div class="contact-info">
                            <div class="contact-header"><h4>${escapeHtml(g.name)}</h4></div>
                            <p>${g.member_count} member${g.member_count != 1 ? 's' : ''}</p>
                        </div>
                    </div>
                `;
                groupsView.insertAdjacentHTML('beforeend', html);
            });
            groupsView.querySelectorAll('.group-contact').forEach(c => {
                c.addEventListener('click', () => {
                    currentGroupId = c.getAttribute('data-id');
                    currentReceiverId = null;
                    chatName.innerText = c.getAttribute('data-name');
                    chatAvatar.src = c.getAttribute('data-avatar');
                    loadGroupMessages();
                });
            });
        } catch (e) { console.error(e); }
    }

    // ============ CONTACT CLICKS ============
    function attachContactClicks() {
        document.querySelectorAll('#messages-list-view .contact, #users-list-view .contact').forEach(contact => {
            contact.addEventListener('click', () => {
                document.querySelectorAll('.contact').forEach(c => c.classList.remove('active'));
                contact.classList.add('active');
                currentReceiverId = contact.getAttribute('data-id');
                currentGroupId = null;
                chatName.innerText = contact.getAttribute('data-name');
                chatAvatar.src = contact.getAttribute('data-avatar');
                loadMessages();
            });
        });
    }

    // ============ LOAD MESSAGES ============
    async function loadMessages() {
        if (!currentReceiverId) return;
        try {
            const res = await fetch(`api/fetch-messages.php?user_id=${currentReceiverId}`);
            const msgs = await res.json();
            messagesContainer.innerHTML = '';
            if (!msgs || msgs.length === 0) {
                messagesContainer.innerHTML = '<p style="text-align:center; color:#94a3b8; margin-top:20px;">No messages yet. Say hello!</p>';
                return;
            }
            let lastDate = null;
            msgs.forEach(msg => {
                const isMe = msg.sender_id == MY_ID;
                const msgDate = new Date(msg.created_at).toDateString();
                if (msgDate !== lastDate) {
                    messagesContainer.insertAdjacentHTML('beforeend', `<div style="text-align:center; margin: 15px 0; color:#94a3b8; font-size:12px; font-weight:600;">${formatDateLabel(msg.created_at)}</div>`);
                    lastDate = msgDate;
                }
                const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const avatarUrl = isMe ? MY_AVATAR : msg.avatar;
                let contentHtml;
                if (msg.image_path) contentHtml = `<img src="${msg.image_path}" class="chat-image" style="max-width:250px; border-radius:12px; cursor:pointer; display:block;">`;
                else if (msg.audio_path) {
                    contentHtml = `
                        <div class="nt-voice-message">
                            <span class="nt-voice-icon">🎤</span>
                            <audio controls preload="metadata">
                                <source src="${escapeHtml(msg.audio_path)}">
                            </audio>
                            <span class="nt-voice-label">Voice note</span>
                        </div>
                    `;
                }
                else contentHtml = `<div class="msg-bubble">${escapeHtml(msg.message || '')}</div>`;
                const html = `
                    <div class="message ${isMe ? 'sent' : 'received'}">
                        ${!isMe ? `<img src="${avatarUrl}" class="msg-avatar">` : ''}
                        <div class="msg-content">
                            ${contentHtml}
                            <span class="msg-time">${time} ${isMe ? '✓✓' : ''}</span>
                        </div>
                        ${isMe ? `<img src="${avatarUrl}" class="msg-avatar">` : ''}
                    </div>
                `;
                messagesContainer.insertAdjacentHTML('beforeend', html);
            });
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        } catch (e) { console.error(e); }
    }

    // ============ LOAD GROUP MESSAGES ============
    async function loadGroupMessages() {
        if (!currentGroupId) return;
        try {
            const res = await fetch(`api/fetch-group-messages.php?group_id=${currentGroupId}`);
            const msgs = await res.json();
            messagesContainer.innerHTML = '';
            if (!msgs || msgs.length === 0) {
                messagesContainer.innerHTML = '<p style="text-align:center; color:#94a3b8; margin-top:20px;">No messages yet in this group.</p>';
                return;
            }
            msgs.forEach(msg => {
                const isMe = msg.sender_id == MY_ID;
                const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const avatarUrl = isMe ? MY_AVATAR : msg.avatar;
                const html = `
                    <div class="message ${isMe ? 'sent' : 'received'}">
                        ${!isMe ? `<img src="${avatarUrl}" class="msg-avatar">` : ''}
                        <div class="msg-content">
                            ${!isMe ? `<small style="color:#94a3b8; font-size:11px;">${escapeHtml(msg.username)}</small>` : ''}
                            <div class="msg-bubble">${escapeHtml(msg.message || '')}</div>
                            <span class="msg-time">${time} ${isMe ? '✓✓' : ''}</span>
                        </div>
                        ${isMe ? `<img src="${avatarUrl}" class="msg-avatar">` : ''}
                    </div>
                `;
                messagesContainer.insertAdjacentHTML('beforeend', html);
            });
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        } catch (e) { console.error(e); }
    }

    // ============ SEND MESSAGE ============
    async function sendMessage() {
        const text = messageInput.value.trim();
        if (text === '') return;

        if (currentGroupId) {
            messageInput.value = '';
            try {
                await fetch('api/send-group-message.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ group_id: currentGroupId, message: text })
                });
                loadGroupMessages();
            } catch (e) { console.error(e); }
            return;
        }

        if (!currentReceiverId) return alert("Select a user first!");
        messageInput.value = '';
        try {
            await fetch('api/send-message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text, receiver_id: currentReceiverId })
            });
            loadMessages();
            loadChats();
        } catch (e) { console.error(e); }
    }

    if (sendBtn) sendBtn.addEventListener('click', sendMessage);
    if (messageInput) messageInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });

    // ============ CREATE GROUP ============
    if (createGroupBtn) {
        createGroupBtn.addEventListener('click', async () => {
            const name = prompt('Enter group name:');
            if (!name || name.trim().length < 2) return;
            try {
                const res = await fetch('api/create-group.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: name.trim() })
                });
                const data = await res.json();
                if (data.status === 'success') {
                    loadGroups();
                } else {
                    alert(data.message || 'Failed');
                }
            } catch (e) { console.error(e); }
        });
    }

    // ============ EMOJI ============
    const emojiBtn = document.getElementById('emoji-btn');
    const emojiPicker = document.getElementById('emoji-picker');
    const emojiCategories = document.getElementById('emoji-categories');
    const emojiList = document.getElementById('emoji-list');

    const EMOJI_DATA = {
        "😀 Smileys": ["😀","😃","😄","😁","😆","😅","😂","🤣","🥲","☺️","😊","😇","🙂","🙃","😉","😌","😍","🥰","😘","😗","😙","😚","😋","😛","😝","😜","🤪","🤨","🧐","🤓","😎","🥸","🤩","🥳","😏","😒","😞","😔","😟","😕","🙁","☹️","😣","😖","😫","😩","🥺","😢","😭","😤","😠","😡","🤬","🤯","😳","🥵","🥶","😱","😨","😰","😥","😓","🤗","🤔","🤭","🤫","🤥","😶","😐","😑","😬","🙄","😯","😦","😧","😮","😲","🥱","😴","🤤","😪","😵","🤐","🥴","🤢","🤮","🤧","😷","🤒","🤕","🤑","🤠","😈","👿","👹","👺","🤡","💩","👻","💀","☠️","👽","👾","🤖","🎃"],
        "❤️ Hearts": ["❤️","🧡","💛","💚","💙","💜","🖤","🤍","🤎","💔","❤️‍🔥","💕","💞","💓","💗","💖","💘","💝","💟","♥️","💌","💋","🫶","🫰","🤝"],
        "👍 Hands": ["👍","👎","👌","🤌","🤏","✌️","🤞","🫰","🤟","🤘","🤙","👈","👉","👆","👇","☝️","👋","🤚","🖐️","✋","🖖","🫱","🫲","🙏","✍️","💅","🤳","💪","🦾","👂","👀","👁️","👅","👄"],
        "🐶 Animals": ["🐶","🐱","🐭","🐹","🐰","🦊","🐻","🐼","🐨","🐯","🦁","🐮","🐷","🐸","🐵","🙈","🙉","🙊","🐒","🐔","🐧","🐦","🐤","🦆","🦅","🦉","🦇","🐺","🐗","🐴","🦄","🐝","🐛","🦋","🐌","🐞","🐜","🦟","🕷️","🦂","🐢","🐍","🦎","🐙","🦑","🦐","🦞","🦀","🐡","🐠","🐟","🐬","🐳","🐋","🦈","🐊","🐅","🐆","🦓","🦍","🐘","🦏","🐪","🐫","🦒","🦘","🐃","🐂","🐄","🐎","🐖","🐏","🐑","🦙","🐐","🦌","🐕","🐩","🐈","🐓","🦃","🦚","🦜","🦢","🕊️","🐇","🦝","🦨","🦦","🦥","🐁","🐀","🐿️","🦔"],
        "🍕 Food": ["🍏","🍎","🍐","🍊","🍋","🍌","🍉","🍇","🍓","🫐","🍈","🍒","🍑","🥭","🍍","🥥","🥝","🍅","🍆","🥑","🥦","🥬","🥒","🌶️","🌽","🥕","🧄","🧅","🥔","🍠","🥐","🥯","🍞","🥖","🥨","🧀","🥚","🍳","🥞","🥓","🥩","🍗","🍖","🌭","🍔","🍟","🍕","🥪","🥙","🌮","🌯","🥗","🍝","🍜","🍲","🍛","🍣","🍱","🥟","🍤","🍙","🍚","🍘","🍥","🥮","🍢","🍡","🍧","🍨","🍦","🥧","🧁","🍰","🎂","🍮","🍭","🍬","🍫","🍿","🍩","🍪","🥜","🍯","🥛","🍼","☕","🍵","🧃","🥤","🧋","🍺","🍻","🥂","🍷","🥃","🍸","🍹","🧉","🍾"],
        "⚽ Activity": ["⚽","🏀","🏈","⚾","🥎","🎾","🏐","🏉","🥏","🎱","🏓","🏸","🏒","🏑","🏏","⛳","🏹","🎣","🥊","🥋","🎽","🛹","🛼","⛸️","🎿","🏂","🏋️","🤼","🤸","🤺","🤾","🏌️","🏇","🧘","🏄","🏊","🤽","🚣","🧗","🚵","🚴","🏆","🥇","🥈","🥉","🏅","🎖️","🎗️","🎫","🎪","🤹","🎭","🎨","🎬","🎤","🎧","🎼","🎹","🥁","🎷","🎺","🎸","🪕","🎻","🎲","🎯","🎳","🎮","🎰","🧩"],
        "💡 Objects": ["⌚","📱","💻","⌨️","🖥️","🖨️","🖱️","🕹️","💽","💾","💿","📀","📼","📷","📸","📹","🎥","📽️","📞","☎️","📟","📠","📺","📻","🎙️","🧭","⏱️","⏲️","⏰","🕰️","⌛","⏳","📡","🔋","🔌","💡","🔦","🕯️","🧯","💸","💵","💴","💶","💷","💰","💳","💎","⚖️","🔧","🔨","🛠️","🔩","⚙️","🧱","⛓️","🧲","🔫","💣","🧨","🪓","🔪","🗡️","⚔️","🛡️","🚬","⚰️","⚱️","🏺","🔮","📿","🧿","💈","⚗️","🔭","🔬","🩹","🩺","💊","💉","🩸","🧬","🦠","🧫","🧪","🌡️","🧹","🧺","🧻","🚽","🚰","🚿","🛁","🧼","🪥","🪒","🧽","🪣","🧴","🛎️","🔑","🗝️","🚪","🪑","🛋️","🛏️","🛌","🧸","🖼️","🪞","🛍️","🛒","🎁","🎈","🎀"],
        "✈️ Travel": ["🚗","🚕","🚙","🚌","🚎","🏎️","🚓","🚑","🚒","🚐","🚚","🚛","🚜","🛴","🚲","🛵","🏍️","🚨","🚔","🚍","🚘","🚖","🚡","🚠","🚟","🚃","🚋","🚞","🚝","🚄","🚅","🚈","🚂","🚆","🚇","🚊","🚉","✈️","🛫","🛬","🛩️","💺","🛰️","🚀","🛸","🚁","🛶","⛵","🚤","🛥️","🛳️","⛴️","🚢","⚓","⛽","🚧","🚦","🚥","🚏","🗺️","🗿","🗽","🗼","🏰","🏯","🏟️","🎡","🎢","🎠","⛲","⛱️","🏖️","🏝️","🏜️","🌋","⛰️","🏔️","🗻","🏕️","⛺","🏠","🏡","🏘️","🏭","🏢","🏬","🏣","🏤","🏥","🏦","🏨","🏪","🏫","💒","🏛️","⛪","🕌","🕍","🕋","⛩️","🌅","🌄","🌠","🎇","🎆","🌇","🌆","🏙️","🌃","🌌","🌉","🌁"],
        "💯 Symbols": ["💯","🔥","⭐","🌟","✨","⚡","💥","💫","💦","💨","💬","💭","💤","🎵","🎶","➕","➖","➗","✖️","♾️","💲","💱","™️","©️","®️","➰","➿","🔚","🔙","🔛","🔝","🔜","✔️","☑️","🔘","🔴","🟠","🟡","🟢","🔵","🟣","⚫","⚪","🟤","🔺","🔻","🔸","🔹","🔶","🔷","🔳","🔲","▪️","▫️","◾","◽","◼️","◻️","🟥","🟧","🟨","🟩","🟦","🟪","⬛","⬜","🟫","🔈","🔇","🔉","🔊","🔔","🔕","📣","📢","💠","❗","❕","❓","❔","‼️","⁉️","❌","⭕","🛑","⛔","📛","🚫","💢","♨️","🚷","🚯","🚳","🚱","🔞","📵","🚭"]
    };

    let currentCategory = Object.keys(EMOJI_DATA)[0];
    function renderEmojiCategories() {
        emojiCategories.innerHTML = '';
        Object.keys(EMOJI_DATA).forEach(cat => {
            const btn = document.createElement('button');
            btn.innerText = cat.split(' ')[0];
            btn.style.cssText = `padding:6px 10px; border:none; background:${cat === currentCategory ? '#0052ff' : 'transparent'}; color:${cat === currentCategory ? 'white' : '#0f172a'}; border-radius:8px; cursor:pointer; font-size:18px;`;
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                currentCategory = cat;
                renderEmojiCategories();
                renderEmojis();
            });
            emojiCategories.appendChild(btn);
        });
    }
    function renderEmojis() {
        emojiList.innerHTML = '';
        EMOJI_DATA[currentCategory].forEach(emoji => {
            const span = document.createElement('span');
            span.innerText = emoji;
            span.style.cssText = 'font-size:22px; cursor:pointer; padding:4px; text-align:center; border-radius:6px; user-select:none;';
            span.addEventListener('mouseenter', () => span.style.background = document.body.classList.contains('dark-mode') ? '#334155' : '#f1f5f9');
            span.addEventListener('mouseleave', () => span.style.background = 'transparent');
            span.addEventListener('click', () => {
                const s = messageInput.selectionStart || 0, e = messageInput.selectionEnd || 0;
                const v = messageInput.value || '';
                messageInput.value = v.substring(0, s) + emoji + v.substring(e);
                messageInput.selectionStart = messageInput.selectionEnd = s + emoji.length;
                messageInput.focus();
            });
            emojiList.appendChild(span);
        });
    }
    if (emojiBtn && emojiPicker) {
        emojiBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = emojiPicker.style.display === 'flex';
            emojiPicker.style.display = open ? 'none' : 'flex';
            if (!open) { renderEmojiCategories(); renderEmojis(); }
        });
        document.addEventListener('click', (e) => {
            if (emojiPicker.style.display === 'flex' && !emojiPicker.contains(e.target) && e.target !== emojiBtn) emojiPicker.style.display = 'none';
        });
    }

 
// ============ VOICE NOTES ============
const voiceBtn = document.getElementById('voice-btn');

let mediaRecorder = null;
let audioChunks = [];
let audioStream = null;
let isRecording = false;
let recordingTimer = null;
let recordingSeconds = 0;

function updateVoiceButton() {
    if (!voiceBtn) return;

    if (isRecording) {
        const minutes = String(Math.floor(recordingSeconds / 60)).padStart(2, '0');
        const seconds = String(recordingSeconds % 60).padStart(2, '0');

        voiceBtn.innerHTML = `■ ${minutes}:${seconds}`;
        voiceBtn.style.background = '#ef4444';
        voiceBtn.style.color = '#ffffff';
        voiceBtn.title = 'Stop and send voice note';
    } else {
        voiceBtn.innerHTML = '🎤';
        voiceBtn.style.background = '';
        voiceBtn.style.color = '';
        voiceBtn.title = 'Record voice note';
    }
}

async function startVoiceRecording() {
    if (!currentReceiverId) {
        alert('Select a person to send a voice note to.');
        return;
    }

    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
        alert('Your browser does not support voice recording.');
        return;
    }

    try {
        audioStream = await navigator.mediaDevices.getUserMedia({
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
            }
        });

        let mimeType = '';

        const supportedTypes = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/ogg;codecs=opus',
            'audio/mp4'
        ];

        mimeType = supportedTypes.find(type =>
            MediaRecorder.isTypeSupported(type)
        ) || '';

        mediaRecorder = mimeType
            ? new MediaRecorder(audioStream, { mimeType })
            : new MediaRecorder(audioStream);

        audioChunks = [];
        recordingSeconds = 0;

        mediaRecorder.ondataavailable = event => {
            if (event.data && event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        mediaRecorder.onstop = async () => {
            clearInterval(recordingTimer);

            const audioBlob = new Blob(audioChunks, {
                type: mediaRecorder.mimeType || 'audio/webm'
            });

            audioStream?.getTracks().forEach(track => track.stop());
            audioStream = null;

            if (audioBlob.size === 0) {
                alert('The recording is empty. Please try again.');
                updateVoiceButton();
                return;
            }

            await uploadVoiceNote(audioBlob);
        };

        mediaRecorder.start();

        isRecording = true;
        updateVoiceButton();

        recordingTimer = setInterval(() => {
            recordingSeconds++;
            updateVoiceButton();

            if (recordingSeconds >= 180) {
                stopVoiceRecording();
            }
        }, 1000);

    } catch (error) {
        console.error('Voice recording error:', error);

        audioStream?.getTracks().forEach(track => track.stop());
        audioStream = null;

        alert('Unable to access your microphone. Please allow microphone permission.');
        isRecording = false;
        updateVoiceButton();
    }
}

function stopVoiceRecording() {
    if (!mediaRecorder || mediaRecorder.state === 'inactive') return;

    isRecording = false;
    clearInterval(recordingTimer);
    updateVoiceButton();

    mediaRecorder.stop();
}

async function uploadVoiceNote(audioBlob) {
    if (!currentReceiverId) {
        alert('No recipient selected.');
        return;
    }

    const extension = audioBlob.type.includes('ogg')
        ? 'ogg'
        : audioBlob.type.includes('mp4')
            ? 'm4a'
            : 'webm';

    const formData = new FormData();

    formData.append('audio', audioBlob, `voice-note.${extension}`);
    formData.append('receiver_id', currentReceiverId);

    voiceBtn.disabled = true;
    voiceBtn.innerHTML = '⌛';

    try {
        const response = await fetch('api/upload-audio.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || 'Voice note upload failed.');
        }

        await loadMessages();
        await loadChats();

    } catch (error) {
        console.error('Voice note upload error:', error);
        alert(error.message || 'Could not send voice note.');
    } finally {
        voiceBtn.disabled = false;
        isRecording = false;
        updateVoiceButton();
    }
}

if (voiceBtn) {
    voiceBtn.addEventListener('click', () => {
        if (isRecording) {
            stopVoiceRecording();
        } else {
            startVoiceRecording();
        }
    });
}


    // ============ IMAGE SHARING ============
    const attachBtn = document.getElementById('attach-btn');
    const imageInput = document.getElementById('image-input');
    const previewModal = document.getElementById('image-preview-modal');
    const previewImg = document.getElementById('preview-img');
    const closePreview = document.getElementById('close-preview');

    if (attachBtn) attachBtn.addEventListener('click', () => {
        if (!currentReceiverId) return alert("Select a user first!");
        imageInput.click();
    });
    if (imageInput) imageInput.addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('image', file);
        fd.append('receiver_id', currentReceiverId);
        try {
            const res = await fetch('api/upload-image.php', { method: 'POST', body: fd });
            const d = await res.json();
            if (d.status === 'success') { loadMessages(); loadChats(); }
        } catch (e) { console.error(e); }
        imageInput.value = '';
    });
    if (previewModal) {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('chat-image')) {
                previewImg.src = e.target.src;
                previewModal.style.display = 'flex';
            }
        });
        closePreview.addEventListener('click', () => previewModal.style.display = 'none');
    }

    // ============ PROFILE ============
    const profileClick = document.getElementById('open-profile-modal');
    const profileModal = document.getElementById('profile-modal');
    const profileCancel = document.getElementById('profile-cancel');
    const profileSave = document.getElementById('profile-save');
    const modalAvatarInput = document.getElementById('modal-avatar-input');
    const modalAvatarPreview = document.getElementById('modal-avatar-preview');
    const modalUsernameInput = document.getElementById('modal-username-input');
    const sidebarAvatar = document.querySelector('.my-profile-avatar');
    const sidebarUsername = document.querySelector('.my-profile-card h3');

    document.querySelectorAll('.avatar-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.avatar-tab').forEach(t => { t.classList.remove('active-tab'); t.style.background = 'transparent'; t.style.color = '#64748b'; });
            tab.classList.add('active-tab');
            tab.style.background = 'white'; tab.style.color = '#0f172a';
            document.querySelectorAll('.avatar-tab-content').forEach(c => c.style.display = 'none');
            document.getElementById('tab-' + tab.getAttribute('data-tab')).style.display = 'block';
        });
    });
    document.querySelectorAll('.default-avatar').forEach(img => {
        img.addEventListener('click', () => {
            document.querySelectorAll('.default-avatar').forEach(i => i.style.border = '2px solid transparent');
            img.style.border = '2px solid #0052ff';
            document.getElementById('selected-default-avatar').value = img.src;
            modalAvatarPreview.src = img.src;
            document.getElementById('camera-captured-data').value = '';
        });
    });
    if (modalAvatarInput) modalAvatarInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => { modalAvatarPreview.src = ev.target.result; };
        reader.readAsDataURL(file);
        document.getElementById('selected-default-avatar').value = '';
        document.getElementById('camera-captured-data').value = '';
    });
    let cameraStream = null;
    const video = document.getElementById('camera-video');
    async function startCamera() {
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = cameraStream;
        } catch (e) { alert('Camera denied'); }
    }
    function stopCamera() { if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; } }
    const camTab = document.querySelector('.avatar-tab[data-tab="camera"]');
    if (camTab) camTab.addEventListener('click', startCamera);
    document.querySelectorAll('.avatar-tab:not([data-tab="camera"])').forEach(t => t.addEventListener('click', stopCamera));
    const captureBtn = document.getElementById('capture-btn');
    if (captureBtn) captureBtn.addEventListener('click', () => {
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth; canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        const dataUrl = canvas.toDataURL('image/jpeg');
        modalAvatarPreview.src = dataUrl;
        document.getElementById('camera-captured-data').value = dataUrl;
        stopCamera();
        document.querySelector('.avatar-tab[data-tab="upload"]').click();
    });
    if (profileClick) profileClick.addEventListener('click', () => {
        profileModal.style.display = 'flex';
        modalAvatarPreview.src = sidebarAvatar.src;
        modalUsernameInput.value = sidebarUsername.innerText;
    });
    if (profileCancel) profileCancel.addEventListener('click', () => { profileModal.style.display = 'none'; stopCamera(); });
    if (profileSave) profileSave.addEventListener('click', async () => {
        const u = modalUsernameInput.value.trim();
        if (u.length < 3) return alert('Username too short');
        const fd = new FormData();
        fd.append('username', u);
        const f = modalAvatarInput.files[0];
        const d = document.getElementById('selected-default-avatar').value;
        const c = document.getElementById('camera-captured-data').value;
        if (f) fd.append('avatar', f);
        else if (d) fd.append('avatar_url', d);
        else if (c) fd.append('avatar_data', c);
        try {
            const res = await fetch('api/update-profile.php', { method: 'POST', body: fd });
            const j = await res.json();
            if (j.status === 'success') {
                sidebarUsername.innerText = j.username;
                sidebarAvatar.src = j.avatar;
                profileModal.style.display = 'none';
                setTimeout(() => location.reload(), 500);
            } else alert(j.message || 'Failed');
        } catch (e) { console.error(e); }
    });

    // ============ CALL & INFO ============
    const callBtn = document.getElementById('call-btn');
    const infoBtn = document.getElementById('info-btn');
    const callModal = document.getElementById('call-modal');
    const infoModal = document.getElementById('info-modal');
    const callClose = document.getElementById('call-close');
    const infoClose = document.getElementById('info-close');
    if (callBtn) callBtn.addEventListener('click', () => {
        if (!currentReceiverId && !currentGroupId) return alert("Select a chat first!");
        document.getElementById('call-avatar').src = chatAvatar.src;
        document.getElementById('call-username').innerText = chatName.innerText;
        callModal.style.display = 'flex';
    });
    if (infoBtn) infoBtn.addEventListener('click', () => {
        if (!currentReceiverId && !currentGroupId) return alert("Select a chat first!");
        document.getElementById('info-avatar').src = chatAvatar.src;
        document.getElementById('info-username').innerText = chatName.innerText;
        document.getElementById('info-status').innerText = "🟢 Online";
        infoModal.style.display = 'flex';
    });
    if (callClose) callClose.addEventListener('click', () => callModal.style.display = 'none');
    if (infoClose) infoClose.addEventListener('click', () => infoModal.style.display = 'none');

    // ============ UTILS ============
    function formatTime(d) {
        if (!d) return '';
        const date = new Date(d), now = new Date(), diff = (now - date) / 1000;
        if (diff < 60) return 'now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm';
        if (diff < 86400) return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        if (diff < 172800) return 'Yesterday';
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }
    function formatDateLabel(d) {
        const date = new Date(d), now = new Date();
        if (date.toDateString() === now.toDateString()) return 'Today';
        const y = new Date(now); y.setDate(now.getDate() - 1);
        if (date.toDateString() === y.toDateString()) return 'Yesterday';
        return date.toLocaleDateString([], { month: 'long', day: 'numeric', year: 'numeric' });
    }
    function formatLastSeen(d) {
        if (!d) return 'Offline';
        const diff = (new Date() - new Date(d)) / 1000;
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return 'Offline';
    }
    function escapeHtml(t) {
        if (t === null || t === undefined) return '';
        const m = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(t).replace(/[&<>"']/g, c => m[c]);
    }

    // ============ INIT ============
    loadChats();
    setInterval(() => { if (tabMessages.classList.contains('active')) loadChats(); }, 3000);
    setInterval(() => {
        if (currentReceiverId) loadMessages();
        if (currentGroupId) loadGroupMessages();
    }, 2000);
});