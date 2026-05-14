const chatArea = document.getElementById('chatArea');
const chatForm = document.getElementById('chatForm');
const messageInput = document.getElementById('messageInput');
const userList = document.getElementById('userList');
const chatTitle = document.getElementById('chatTitle');
const chatHeaderAvatar = document.getElementById('chatHeaderAvatar');
const chatStatus = document.getElementById('chatStatus');
const btnCreateGroup = document.getElementById('btnCreateGroup');
const btnLeaveGroup = document.getElementById('btnLeaveGroup');
const btnSettings = document.getElementById('btnSettings');
const btnSettingsMobile = document.getElementById('btnSettingsMobile');
const settingsModal = document.getElementById('settingsModal');
const privacyToggle = document.getElementById('privacyToggle');
const btnSettingsLogout = document.getElementById('btnSettingsLogout');

let isScrolledToBottom = true;
let lastMessageId = 0;
let currentReceiverId = null;
let currentGroupId = null;

chatArea.addEventListener('scroll', () => {
    isScrolledToBottom = (chatArea.scrollHeight - chatArea.scrollTop) <= (chatArea.clientHeight + 50);
});

function scrollToBottom() {
    chatArea.scrollTop = chatArea.scrollHeight;
}

const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        fetchUsers(e.target.value);
    });
}

async function fetchUsers(query = '') {
    try {
        let usersUrl = 'fetch_users.php';
        if (query) {
            usersUrl += `?search=${encodeURIComponent(query)}`;
        } else if (searchInput && searchInput.value) {
            usersUrl += `?search=${encodeURIComponent(searchInput.value)}`;
        }

        const [usersRes, groupsRes] = await Promise.all([
            fetch(usersUrl),
            fetch('fetch_groups.php')
        ]);
        const users = await usersRes.json();
        const groups = await groupsRes.json();

        // ─── PART 1: Combine and Sort ───────────────────
        let allItems = [];
        
        // Add groups (tagged)
        if (Array.isArray(groups)) {
            groups.forEach(g => {
                g.item_type = 'group';
                allItems.push(g);
            });
        }
        
        // Add users (tagged)
        if (Array.isArray(users)) {
            users.forEach(u => {
                if (u.is_channel == 1) u.item_type = 'group_join';
                else u.item_type = 'user';
                allItems.push(u);
            });
        }
        
        // Sort by last_time (latest first)
        allItems.sort((a, b) => {
            const timeA = a.last_time ? new Date(a.last_time).getTime() : 0;
            const timeB = b.last_time ? new Date(b.last_time).getTime() : 0;
            return timeB - timeA;
        });

        // ─── PART 2: Render ──────────────────────────────
        let html = '';

        // Keep Global Chat at Very top? 
        // User said: "put the names of the group or page on the top depending of who ichat the latest"
        // If Global has no recent chat, maybe it should move down? 
        // For now, I'll include Global in the sorting if it's there.
        // Actually, Global is always available in the sidebar but doesn't come from fetch_users.
        // I'll leave Global at top for now as a special room.
        
        const isGlobalActive = currentReceiverId === null && currentGroupId === null;
        html += `
            <div class="user-item ${isGlobalActive ? 'active' : ''}" data-type="global">
                 <div class="user-avatar" style="background-color: #6366f1;">G</div>
                 <div class="user-info">
                    <div class="user-top">
                        <span class="user-name">Global Chat</span>
                    </div>
                     <div class="user-bottom">
                         <span class="last-message">Public Room</span>
                     </div>
                 </div>
            </div>
        `;

        allItems.forEach(item => {
            const isGroup = item.item_type === 'group';
            const isGroupJoin = item.item_type === 'group_join';
            const isUser = item.item_type === 'user';
            
            const isActive = isGroup ? (currentGroupId == item.id) : 
                             (isGroupJoin ? (currentGroupId == item.id) : 
                             (currentReceiverId == item.id && currentGroupId === null));
            
            const initial = (item.name || item.username || 'G').charAt(0).toUpperCase();
            const timeDisplay = item.last_time ? new Date(item.last_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
            const safeName = escapeHtml(item.name || item.username);
            const safeMsg = escapeHtml(item.last_message || (isGroup ? 'No messages yet' : (isGroupJoin ? 'Public Channel' : 'Start a conversation')));
            
            const unreadCount = parseInt(item.unread_count || 0);
            const unreadBadge = unreadCount > 0 ? `<div class="unread-badge">${unreadCount}</div>` : '';
            const fontWeight = unreadCount > 0 ? 'font-weight: 600; color: var(--text-color);' : '';

            let avatarHtml = `<div class="user-avatar" style="background-color: var(--primary-color);">${initial}</div>`;
            if (item.profile_pic) {
                avatarHtml = `<div class="user-avatar" style="background-color: var(--primary-color); overflow:hidden;"><img src="${item.profile_pic}" style="width:100%;height:100%;object-fit:cover;"></div>`;
            } else if (isGroup || isGroupJoin) {
                avatarHtml = `<div class="user-avatar" style="background-color: #0ea5e9;">📢</div>`;
            }

            html += `
                <div class="user-item ${isActive ? 'active' : ''}" data-type="${item.item_type}" data-id="${item.id}" data-name="${safeName}" data-pic="${item.profile_pic || ''}" data-canmessage="${item.can_message}" data-ischannel="${item.is_channel}">
                      ${avatarHtml}
                      <div class="user-info">
                         <div class="user-top">
                             <span class="user-name">${safeName}</span>
                             <span class="user-time">${timeDisplay}</span>
                         </div>
                          <div class="user-bottom">
                              <span class="last-message" style="${fontWeight}">${safeMsg}</span>
                              ${unreadBadge}
                          </div>
                      </div>
                </div>
            `;
        });

        userList.innerHTML = html;
        Array.from(userList.querySelectorAll('.user-item')).forEach(el => {
            const type = el.getAttribute('data-type');
            if (type === 'global') {
                el.onclick = () => selectUser(null, 'Global Chat');
            } else if (type === 'group') {
                el.onclick = () => {
                   const pic = el.getAttribute('data-pic');
                   const isChannel = el.getAttribute('data-ischannel') === '1';
                   selectGroup(parseInt(el.getAttribute('data-id')), el.getAttribute('data-name'), pic, isChannel);
                };
            } else if (type === 'group_join') {
                el.onclick = () => {
                    const id = el.getAttribute('data-id');
                    const name = el.getAttribute('data-name');
                    if (confirm(`Join public channel "${name}"?`)) {
                        fetch(`join_channel.php?group_id=${id}`)
                            .then(r => r.json())
                            .then(data => {
                                if (data.status === 'Success') {
                                    fetchUsers();
                                    selectGroup(parseInt(id), name, null, true);
                                } else {
                                    alert(data.error || 'Failed to join');
                                }
                            });
                    }
                };
            } else if (type === 'user') {
                el.onclick = () => selectUser(parseInt(el.getAttribute('data-id')), el.getAttribute('data-name'), el.getAttribute('data-pic'), el.getAttribute('data-canmessage') === 'true');
            }
        });

    } catch (error) {
        console.error('Error fetching users:', error);
    }
}

async function markAsRead(contactId, groupId = null) {
    try {
        const formData = new FormData();
        if (groupId) {
            formData.append('group_id', groupId);
        } else if (contactId) {
            formData.append('contact_id', contactId);
        } else {
            return;
        }
        await fetch('mark_read.php', { method: 'POST', body: formData });
        fetchUsers();
    } catch (error) {
        console.error('Error marking read:', error);
    }
}

function selectUser(userId, username, pic = null, canMessage = true) {
    if (currentReceiverId === userId && userId !== null) return;
    currentReceiverId = userId;
    currentGroupId = null;
    window.currentChatId = null; 
    chatTitle.textContent = username;
    chatHeaderAvatar.style.visibility = 'visible';
    
    if (pic && pic !== 'null' && pic !== '') {
        chatHeaderAvatar.innerHTML = `<img src="${pic}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
        chatHeaderAvatar.style.backgroundColor = 'transparent';
    } else {
        const initial = username.charAt(0).toUpperCase();
        chatHeaderAvatar.innerText = initial;
        chatHeaderAvatar.style.backgroundColor = userId === null ? '#6366f1' : 'var(--primary-color)';
        chatHeaderAvatar.innerHTML = initial;
    }
    
    // UI Resets
    if (btnLeaveGroup) btnLeaveGroup.style.display = 'none';
    if (document.getElementById('btnChannelSettings')) document.getElementById('btnChannelSettings').style.display = 'none';
    
    // Privacy and Input state
    if (canMessage) {
        messageInput.disabled = false;
        messageInput.placeholder = "Type a message";
    } else {
        messageInput.disabled = true;
        messageInput.placeholder = "this user is set the account on private";
    }
    chatForm.style.display = 'flex';

    if (userId) markAsRead(userId, null);
    lastMessageId = 0;
    chatArea.innerHTML = '';
    fetchMessages();
    fetchUsers();
}

async function selectGroup(groupId, name, pic = null, isChannel = false) {
    if (currentGroupId === groupId && groupId !== null) return;
    currentGroupId = groupId;
    window.currentChatId = groupId;
    currentReceiverId = null;
    chatTitle.textContent = name;
    chatForm.style.display = 'flex';
    chatHeaderAvatar.style.visibility = 'visible';
    
    if (pic) {
        chatHeaderAvatar.innerHTML = `<img src="${pic}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
        chatHeaderAvatar.style.backgroundColor = 'transparent';
    } else {
        const initial = name && name.length ? name.charAt(0).toUpperCase() : 'G';
        chatHeaderAvatar.innerText = initial;
        chatHeaderAvatar.style.backgroundColor = isChannel ? '#0ea5e9' : '#1e293b';
        chatHeaderAvatar.innerHTML = initial;
    }
    
    if (btnLeaveGroup) btnLeaveGroup.style.display = 'inline-block';
    if (groupId) markAsRead(null, groupId);
    
    // Handle Settings Button and View-Only mode
    const settingsBtn = document.getElementById('btnChannelSettings');
    try {
        const res = await fetch(`fetch_group_details.php?group_id=${groupId}`);
        const data = await res.json();
        
        if (settingsBtn) {
            settingsBtn.style.display = 'flex';
        }
        
        if (data.settings.view_only == 1 && !data.is_admin) {
            messageInput.disabled = true;
            messageInput.placeholder = "Only admins can send messages here";
        } else {
            messageInput.disabled = false;
            messageInput.placeholder = "Type a message";
        }
    } catch (e) {
        if (settingsBtn) settingsBtn.style.display = 'none';
    }

    lastMessageId = 0;
    window.lastThreadSenderId = null; // Reset grouping on switch
    chatArea.innerHTML = '';
    fetchMessages();
    fetchUsers();
}

async function fetchMessages() {
    if (chatForm.style.display === 'none') return;
    try {
        let url = `fetch_messages.php?last_id=${lastMessageId}&t=${Date.now()}`;
        if (currentGroupId) url += `&group_id=${currentGroupId}`;
        else if (currentReceiverId) url += `&contact_id=${currentReceiverId}`;

        const response = await fetch(url);
        const messages = await response.json();
        if (messages.length === 0) return;

        let hasNewMessagesFromOther = false;
        let lastSenderId = window.lastThreadSenderId || null;
        const isGroupChat = currentGroupId !== null;

        messages.forEach((msg, idx) => {
            const isNewGroup = (msg.sender_id !== lastSenderId);
            
            // If we have a previous container and this is a new group, we could mark the last one as 'last-in-group'
            // But since we append to chatArea, it's easier to just use CSS for the 'first' one.
            
            const isAttachmentOnly = (!msg.message && msg.attachment);
            const container = document.createElement('div');
            container.className = `msg-container ${msg.is_self == 1 ? 'self' : 'other'} ${isNewGroup ? 'new-group' : ''} ${isAttachmentOnly ? 'attachment-only' : ''}`;
            container.setAttribute('data-sender-id', msg.sender_id);
            if (msg.is_self == 0) hasNewMessagesFromOther = true;

            const timeStr = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            let msgText = msg.message;
            if (window.makeLinksClickable) msgText = makeLinksClickable(escapeHtml(msgText));
            else msgText = escapeHtml(msgText);

            let identityHtml = '';
            let avatarHtml = '';

            if (msg.is_self == 0) {
                 avatarHtml = '<div class="msg-avatar-placeholder"></div>';
                hasNewMessagesFromOther = true;
                // If this is the same sender as the last message in current chat area,
                // remove the previous message's avatar and last-in-group status.
                if (!isNewGroup || (lastSenderId === msg.sender_id)) {
                    const lastOtherMsg = chatArea.querySelector('.msg-container.other:last-child');
                    if (lastOtherMsg && lastOtherMsg.getAttribute('data-sender-id') == msg.sender_id) {
                        const prevAvatar = lastOtherMsg.querySelector('.msg-sender-avatar');
                        if (prevAvatar) {
                            prevAvatar.outerHTML = '<div class="msg-avatar-placeholder"></div>';
                        }
                        lastOtherMsg.classList.remove('last-in-group');
                    }
                }
                container.classList.add('last-in-group');

                const initial = msg.username ? msg.username.charAt(0).toUpperCase() : '?';
                const avatarContent = msg.profile_pic 
                    ? `<img src="${msg.profile_pic}">`
                    : initial;
                
                avatarHtml = `
                    <div class="msg-sender-avatar" onclick="openUserProfile(${msg.sender_id})" title="${escapeHtml(msg.username)}">
                        ${avatarContent}
                    </div>
                `;

                if (isGroupChat && isNewGroup) {
                    identityHtml = `
                        <div class="msg-sender-info" onclick="openUserProfile(${msg.sender_id})">
                            <span class="msg-sender-name">${escapeHtml(msg.username)}</span>
                        </div>
                    `;
                }
            } else {
                // Handling for self messages: only the latest one in a group should have last-in-group rounding
                if (!isNewGroup || (lastSenderId === msg.sender_id)) {
                    const lastSelfMsg = chatArea.querySelector('.msg-container.self:last-child');
                    if (lastSelfMsg && lastSelfMsg.getAttribute('data-sender-id') == msg.sender_id) {
                        lastSelfMsg.classList.remove('last-in-group');
                    }
                }
                container.classList.add('last-in-group');
            }

            const attachmentHtml = msg.attachment ? renderAttachment(msg.attachment) : '';

            container.innerHTML = `
                ${avatarHtml}
                <div class="msg-column">
                    ${identityHtml}
                    <div class="message ${msg.is_self == 1 ? 'self' : 'other'} ${isAttachmentOnly ? 'attachment-only' : ''}">
                        ${msgText}
                        ${attachmentHtml}
                        ${isAttachmentOnly ? '' : `<span class="time">${timeStr}</span>`}
                    </div>
                </div>
            `;
            chatArea.appendChild(container);
            lastMessageId = Math.max(lastMessageId, msg.id);
            lastSenderId = msg.sender_id;
        });
        window.lastThreadSenderId = lastSenderId;

        if (isScrolledToBottom || lastMessageId === 0) scrollToBottom();
        if (hasNewMessagesFromOther && currentReceiverId) markAsRead(currentReceiverId);
        if (messages.length > 0) fetchUsers();
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
}

function renderAttachment(path) {
    if (!path) return '';
    const isImage = /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(path);
    if (isImage) {
        return `<img src="${path}" class="message-attachment" onclick="window.open('${path}')">`;
    } else {
        const filename = path.split('/').pop();
        return `<a href="${path}" class="file-attachment" target="_blank" download>📎 ${escapeHtml(filename)}</a>`;
    }
}

window.handleFileUpload = (input) => {
    if (input.files && input.files[0]) {
        sendMessage(input.files[0]);
        input.value = ''; // Reset
    }
};

async function sendMessage(file = null) {
    const message = messageInput.value.trim();
    if (!message && !file) return;

    const formData = new FormData();
    if (message) formData.append('message', message);
    if (file) formData.append('attachment', file);
    
    if (currentGroupId) formData.append('group_id', currentGroupId);
    else if (currentReceiverId) formData.append('receiver_id', currentReceiverId);

    messageInput.value = '';
    try {
        const response = await fetch('send_message.php', { method: 'POST', body: formData });
        const text = await response.text();
        if (text === 'Success') {
            fetchMessages();
            isScrolledToBottom = true;
            scrollToBottom();
            fetchUsers();
        } else {
            alert(text); // Added alert for error messages like "this user is set the account on private"
        }
    } catch (error) {
        console.error('Network Error:', error);
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

if (chatForm) {
    chatForm.onsubmit = (e) => {
        e.preventDefault();
        sendMessage();
    };
}

if (btnLeaveGroup) {
    btnLeaveGroup.onclick = async () => {
        if (!currentGroupId) return;
        const form = new FormData();
        form.append('group_id', currentGroupId);
        const res = await fetch('leave_group.php', { method: 'POST', body: form });
        const text = await res.text();
        if (text === 'Success') {
            currentGroupId = null;
            chatArea.innerHTML = '';
            selectUser(null, 'Global Chat');
        } else {
            alert(text || 'Failed to leave group');
        }
    };
}

// Settings and Privacy logic
if (btnSettings) {
    btnSettings.onclick = () => openSettings();
}
if (btnSettingsMobile) {
    btnSettingsMobile.onclick = () => openSettings();
}

async function openSettings() {
    if (settingsModal) {
        settingsModal.style.display = 'flex';
        // Fetch current privacy state
        try {
            const res = await fetch('update_privacy.php');
            const data = await res.json();
            if (privacyToggle) {
                privacyToggle.checked = (data.is_private === 1);
            }
        } catch (e) {
            console.error('Error fetching privacy state:', e);
        }
    }
}

if (privacyToggle) {
    privacyToggle.onchange = async () => {
        const isPrivate = privacyToggle.checked ? 1 : 0;
        const form = new FormData();
        form.append('is_private', isPrivate);
        try {
            const res = await fetch('update_privacy.php', { method: 'POST', body: form });
            const data = await res.json();
            if (data.status !== 'Success') {
                alert('Failed to update privacy settings');
            }
        } catch (e) {
            console.error('Error updating privacy:', e);
        }
    };
}

if (btnSettingsLogout) {
    btnSettingsLogout.onclick = () => {
        localStorage.removeItem('me');
        location.href = 'logout.php';
    };
}

setInterval(() => fetchMessages(), 1000);
setInterval(() => fetchUsers(), 5000);
// fetchUsers() will be called after friends_ui.js loads or by switchTab if needed.
