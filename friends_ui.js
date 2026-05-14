/* ========================================================
   friends_ui.js — Profile, Add Friend, Friend Requests UI
   ======================================================== */

// ─── Current sidebar tab ─────────────────────────────────
let currentTab = 'all'; // 'all' | 'friends'

function switchTab(tab) {
    currentTab = tab;
    document.getElementById('tabAll').classList.toggle('active', tab === 'all');
    document.getElementById('tabFriends').classList.toggle('active', tab === 'friends');
    if (tab === 'friends') {
        loadFriendsTab();
    } else {
        fetchUsers();
    }
}

// ─── Load friends list into sidebar ──────────────────────
async function loadFriendsTab() {
    try {
        const searchInput = document.getElementById('searchUsers');
        const q = searchInput ? searchInput.value.trim() : '';
        const url = 'friends.php?action=list' + (q ? '&search=' + encodeURIComponent(q) : '');
        const res = await fetch(url);
        const friends = await res.json();
        const userList = document.getElementById('userList');
        if (!Array.isArray(friends) || friends.length === 0) {
            userList.innerHTML = '<div style="padding:1rem;color:var(--text-secondary);text-align:center;">No friends yet.<br><small>Use ➕ to add friends.</small></div>';
            return;
        }
        let html = '';
        friends.forEach(f => {
            const initial = f.username.charAt(0).toUpperCase();
            const safe = escapeHtml(f.username);
            const avatarContent = f.profile_pic
                ? `<img src="${f.profile_pic}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`
                : initial;
            html += `
                <div class="user-item" data-type="user" data-id="${f.id}" data-name="${safe}" data-pic="${f.profile_pic || ''}">
                    <div class="user-avatar" style="background-color:var(--primary-color);overflow:hidden;">${avatarContent}</div>
                    <div class="user-info">
                        <div class="user-top">
                            <span class="user-name">${safe}</span>
                        </div>
                        <div class="user-bottom">
                            <span class="last-message">${escapeHtml(f.bio || 'Friend')}</span>
                        </div>
                    </div>
                </div>`;
        });
        userList.innerHTML = html;
        Array.from(userList.querySelectorAll('.user-item')).forEach(el => {
            el.onclick = () => selectUser(parseInt(el.getAttribute('data-id')), el.getAttribute('data-name'), el.getAttribute('data-pic'));
        });
    } catch (e) {
        console.error('loadFriendsTab error', e);
    }
}

// Override fetchUsers to respect tab
const _origFetchUsers = fetchUsers;
window._origFetchUsers = _origFetchUsers;
fetchUsers = function (...args) {
    if (currentTab === 'friends') {
        return loadFriendsTab();
    }
    return _origFetchUsers(...args);
};

// Initial load
fetchUsers();

// ─── Profile Modal ────────────────────────────────────────
const profileModal = document.getElementById('profileModal');
const myProfileBtn = document.getElementById('myProfileBtn');

if (myProfileBtn) {
    myProfileBtn.addEventListener('click', () => {
        profileModal.style.display = 'flex';
    });
}

// Click outside to close
if (profileModal) {
    profileModal.addEventListener('click', e => {
        if (e.target === profileModal) profileModal.style.display = 'none';
    });
}

function startEditBio() {
    document.getElementById('bioDisplay').style.display = 'none';
    document.getElementById('bioEdit').style.display = 'block';
    document.getElementById('bioInput').focus();
}

function cancelEditBio() {
    document.getElementById('bioDisplay').style.display = 'block';
    document.getElementById('bioEdit').style.display = 'none';
    document.getElementById('bioSaveMsg').style.display = 'none';
}

async function saveBio() {
    const bio = document.getElementById('bioInput').value;
    const form = new FormData();
    form.append('bio', bio);
    try {
        const res = await fetch('update_profile.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data.ok) {
            const bioTextEl = document.getElementById('bioText');
            bioTextEl.innerHTML = bio
                ? escapeHtml(bio)
                : '<em style="color:var(--text-secondary)">No bio yet. Click Edit to add one.</em>';
            document.getElementById('bioSaveMsg').style.display = 'block';
            setTimeout(() => {
                cancelEditBio();
            }, 1000);
        }
    } catch (e) {
        console.error('saveBio error', e);
    }
}

// Avatar upload handler
const avatarFileInput = document.getElementById('avatarFileInput');
if (avatarFileInput) {
    avatarFileInput.addEventListener('change', async () => {
        const file = avatarFileInput.files[0];
        if (!file) return;
        const form = new FormData();
        form.append('avatar', file);
        try {
            const res = await fetch('upload_avatar.php', { method: 'POST', body: form });
            const data = await res.json();
            if (data.ok) {
                const imgSrc = data.path;
                // Update profile modal avatar
                const profileImg = document.getElementById('profileAvatarImg');
                const profileInitial = document.getElementById('profileAvatarInitial');
                if (profileImg) { profileImg.src = imgSrc; profileImg.style.display = 'block'; }
                if (profileInitial) profileInitial.style.display = 'none';
                // Update sidebar avatar
                const myAvatarEl = document.getElementById('myAvatarEl');
                if (myAvatarEl) {
                    myAvatarEl.innerHTML = `<img src="${imgSrc}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
                }
            } else {
                alert(data.error || 'Upload failed');
            }
        } catch (e) {
            console.error('Avatar upload error', e);
        }
    });
}

// ─── Add Friend Modal ─────────────────────────────────────
const addFriendModal = document.getElementById('addFriendModal');
const btnAddFriend = document.getElementById('btnAddFriend');
const addFriendSearch = document.getElementById('addFriendSearch');
const addFriendResults = document.getElementById('addFriendResults');

if (btnAddFriend) {
    btnAddFriend.addEventListener('click', () => {
        addFriendModal.style.display = 'flex';
        addFriendSearch.value = '';
        searchFriendCandidates('');
        setTimeout(() => addFriendSearch.focus(), 80);
    });
}

if (addFriendModal) {
    addFriendModal.addEventListener('click', e => {
        if (e.target === addFriendModal) addFriendModal.style.display = 'none';
    });
}

if (addFriendSearch) {
    addFriendSearch.addEventListener('input', () => {
        searchFriendCandidates(addFriendSearch.value.trim());
    });
}

async function searchFriendCandidates(query) {
    try {
        const url = 'fetch_users.php' + (query ? `?search=${encodeURIComponent(query)}` : '');
        const res = await fetch(url);
        const users = await res.json();
        if (!Array.isArray(users) || users.length === 0) {
            addFriendResults.innerHTML = '<div style="padding:.75rem;color:var(--text-secondary);text-align:center;">No users found.</div>';
            return;
        }
        // Get current friendship statuses in parallel
        const statuses = await Promise.all(users.map(u =>
            fetch(`friends.php?action=status&friend_id=${u.id}`).then(r => r.json())
        ));
        let html = '';
        users.forEach((u, i) => {
            const safe = escapeHtml(u.username);
            const st = statuses[i];
            const avatarContent = u.profile_pic
                ? `<img src="${u.profile_pic}" style="width:32px;height:32px;object-fit:cover;border-radius:50%;">`
                : `<div class="user-avatar" style="width:32px;height:32px;background:var(--primary-color);font-size:.85rem;">${safe.charAt(0).toUpperCase()}</div>`;
            let actionBtn = '';
            if (st.status === 'accepted') {
                actionBtn = `<button class="btn-friend-status friend-accepted" disabled>✓ Friends</button>`;
            } else if (st.status === 'pending' && st.direction === 'sent') {
                actionBtn = `<button class="btn-friend-status friend-pending" onclick="cancelFriendReq(${u.id}, ${st.request_id}, this)">⏳ Pending</button>`;
            } else if (st.status === 'pending' && st.direction === 'received') {
                actionBtn = `<button class="btn-friend-status btn-primary" onclick="acceptFriendFromSearch(${st.request_id}, this)">Accept</button>`;
            } else {
                actionBtn = `<button class="btn-friend-status btn-add-friend" onclick="sendFriendReq(${u.id}, this)">Add Friend</button>`;
            }
            html += `
                <div class="friend-candidate-row">
                    <div style="display:flex;align-items:center;gap:.6rem;">
                        ${avatarContent}
                        <span class="user-name">${safe}</span>
                    </div>
                    ${actionBtn}
                </div>`;
        });
        addFriendResults.innerHTML = html;
    } catch (e) {
        console.error('searchFriendCandidates error', e);
    }
}

async function sendFriendReq(friendId, btn) {
    const form = new FormData();
    form.append('action', 'send');
    form.append('friend_id', friendId);
    const res = await fetch('friends.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'ok') {
        btn.textContent = '⏳ Pending';
        btn.classList.remove('btn-add-friend');
        btn.classList.add('friend-pending');
        btn.onclick = null;
    } else {
        alert(data.message || data.error || 'Could not send request');
    }
}

async function cancelFriendReq(friendId, requestId, btn) {
    const form = new FormData();
    form.append('action', 'cancel');
    form.append('friend_id', friendId);
    form.append('request_id', requestId);
    const res = await fetch('friends.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'ok') {
        btn.textContent = 'Add Friend';
        btn.classList.remove('friend-pending');
        btn.classList.add('btn-add-friend');
        btn.onclick = () => sendFriendReq(friendId, btn);
    }
}

async function acceptFriendFromSearch(requestId, btn) {
    const form = new FormData();
    form.append('action', 'accept');
    form.append('request_id', requestId);
    const res = await fetch('friends.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'ok') {
        btn.textContent = '✓ Friends';
        btn.classList.add('friend-accepted');
        btn.classList.remove('btn-primary');
        btn.disabled = true;
        pollFriendRequests();
    }
}

// ─── Friend Requests Modal ────────────────────────────────
const friendReqModal = document.getElementById('friendReqModal');
const btnFriendRequests = document.getElementById('btnFriendRequests');
const friendReqBadge = document.getElementById('friendReqBadge');

if (btnFriendRequests) {
    btnFriendRequests.addEventListener('click', () => {
        friendReqModal.style.display = 'flex';
        loadFriendRequests();
        loadFriendsInModal();
    });
}

if (friendReqModal) {
    friendReqModal.addEventListener('click', e => {
        if (e.target === friendReqModal) friendReqModal.style.display = 'none';
    });
}

async function loadFriendRequests() {
    try {
        const res = await fetch('friends.php?action=pending');
        const reqs = await res.json();
        const list = document.getElementById('friendReqList');
        if (!Array.isArray(reqs) || reqs.length === 0) {
            list.innerHTML = '<div style="padding:.75rem;color:var(--text-secondary);text-align:center;">No pending requests 🎉</div>';
            updateFriendBadge(0);
            return;
        }
        updateFriendBadge(reqs.length);
        let html = '';
        reqs.forEach(r => {
            const safe = escapeHtml(r.username);
            const avatarContent = r.profile_pic
                ? `<img src="${r.profile_pic}" style="width:36px;height:36px;object-fit:cover;border-radius:50%;">`
                : `<div class="user-avatar" style="width:36px;height:36px;font-size:.9rem;">${safe.charAt(0).toUpperCase()}</div>`;
            html += `
                <div class="friend-req-row" id="freq-${r.request_id}">
                    <div style="display:flex;align-items:center;gap:.6rem;">
                        ${avatarContent}
                        <span class="user-name">${safe}</span>
                    </div>
                    <div style="display:flex;gap:.4rem;">
                        <button class="btn-primary btn-sm" onclick="acceptReq(${r.request_id}, ${r.id})">Accept</button>
                        <button class="btn-cancel btn-sm" onclick="rejectReq(${r.request_id}, ${r.id})">Reject</button>
                    </div>
                </div>`;
        });
        list.innerHTML = html;
    } catch (e) {
        console.error('loadFriendRequests error', e);
    }
}

async function acceptReq(requestId, userId) {
    const form = new FormData();
    form.append('action', 'accept');
    form.append('request_id', requestId);
    const res = await fetch('friends.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'ok') {
        const row = document.getElementById(`freq-${requestId}`);
        if (row) row.remove();
        pollFriendRequests();
        loadFriendsInModal();
        if (currentTab === 'friends') loadFriendsTab();
    }
}

async function rejectReq(requestId, userId) {
    const form = new FormData();
    form.append('action', 'reject');
    form.append('request_id', requestId);
    const res = await fetch('friends.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'ok') {
        const row = document.getElementById(`freq-${requestId}`);
        if (row) row.remove();
        pollFriendRequests();
        loadFriendsInModal();
    }
}

async function loadFriendsInModal() {
    try {
        const res = await fetch('friends.php?action=list');
        const friends = await res.json();
        const list = document.getElementById('friendModalList');
        if (!Array.isArray(friends) || friends.length === 0) {
            list.innerHTML = '<div style="padding:.75rem;color:var(--text-secondary);text-align:center;">No friends yet.</div>';
            return;
        }
        let html = '';
        friends.forEach(f => {
            const safe = escapeHtml(f.username);
            const avatarContent = f.profile_pic
                ? `<img src="${f.profile_pic}" style="width:36px;height:36px;object-fit:cover;border-radius:50%;">`
                : `<div class="user-avatar" style="width:36px;height:36px;font-size:.9rem;">${safe.charAt(0).toUpperCase()}</div>`;
            html += `
                <div class="friend-req-row" id="fmod-${f.id}">
                    <div style="display:flex;align-items:center;gap:.6rem;cursor:pointer;" onclick="closeFriendModalAndChat(${f.id}, '${safe}', '${f.profile_pic || ''}')">
                        ${avatarContent}
                        <div>
                            <div class="user-name">${safe}</div>
                            <div style="font-size:0.75rem;color:var(--text-secondary);">${escapeHtml(f.bio || 'Friend')}</div>
                        </div>
                    </div>
                    <div style="display:flex;gap:.4rem;">
                        <button class="btn-cancel btn-sm" onclick="unfriendReq(${f.id}, '${safe}')" title="Unfriend">✕</button>
                    </div>
                </div>`;
        });
        list.innerHTML = html;
    } catch (e) {
        console.error('loadFriendsInModal error', e);
    }
}

window.closeFriendModalAndChat = (userId, username, pic) => {
    friendReqModal.style.display = 'none';
    if (window.selectUser) selectUser(userId, username, pic);
};

window.unfriendReq = async (friendId, username) => {
    if (!confirm(`Are you sure you want to unfriend ${username}?`)) return;
    const form = new FormData();
    form.append('action', 'unfriend');
    form.append('friend_id', friendId);
    const res = await fetch('friends.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'ok') {
        loadFriendsInModal();
        if (currentTab === 'friends') loadFriendsTab();
        fetchUsers();
    } else {
        alert(data.message || 'Failed to unfriend');
    }
};

function updateFriendBadge(count) {
    if (!friendReqBadge) return;
    if (count > 0) {
        friendReqBadge.textContent = count > 9 ? '9+' : count;
        friendReqBadge.style.display = 'flex';
    } else {
        friendReqBadge.style.display = 'none';
    }
}

// Poll for friend request count
async function pollFriendRequests() {
    try {
        const res = await fetch('friends.php?action=count');
        const data = await res.json();
        updateFriendBadge(data.count || 0);
    } catch (e) {}
}

// Poll every 10 seconds
setInterval(pollFriendRequests, 10000);
pollFriendRequests();

window.openUserProfile = async (userId) => {
    const modal = document.getElementById('userProfileModal');
    if (!modal) return;
    modal.style.display = 'flex';
    
    // Clear previous
    document.getElementById('uProfUsername').textContent = 'Loading...';
    document.getElementById('uProfAvatar').innerHTML = '';
    document.getElementById('uProfBio').textContent = '';
    document.getElementById('uProfFriendCount').textContent = '0';
    document.getElementById('uProfJoinDate').textContent = '';
    document.getElementById('uProfActions').innerHTML = '';

    try {
        const res = await fetch(`fetch_user_profile.php?user_id=${userId}`);
        const user = await res.json();
        if (user.error) throw new Error(user.error);

        document.getElementById('uProfUsername').textContent = user.username;
        const initial = user.username.charAt(0).toUpperCase();
        document.getElementById('uProfAvatar').innerHTML = user.profile_pic 
            ? `<img src="${user.profile_pic}" style="width:100%;height:100%;object-fit:cover;">`
            : initial;
        document.getElementById('uProfBio').textContent = user.bio || 'No bio yet.';
        document.getElementById('uProfFriendCount').textContent = user.friend_count || 0;
        
        const joinDate = new Date(user.created_at).toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
        document.getElementById('uProfJoinDate').textContent = `Member since ${joinDate}`;

        let actionHtml = '';
        // 1. Message button
        actionHtml += `<button class="btn-primary" onclick="messageUserFromProfile(${user.id}, '${escapeHtml(user.username)}', '${user.profile_pic || ''}')">💬 Send Message</button>`;

        // 2. Social Action (Add Friend, Pending, Unfriend)
        if (userId !== window.currentUserId) {
            if (user.friendship) {
                if (user.friendship.status === 'accepted') {
                    actionHtml += `<button class="btn-cancel" onclick="unfriendFromProfile(${user.id}, '${escapeHtml(user.username)}')">✕ Unfriend</button>`;
                } else if (user.friendship.status === 'pending') {
                    if (user.friendship.direction === 'sent') {
                        actionHtml += `<button class="friend-pending" style="width:100%; cursor:default;" disabled>Friend Request Sent</button>`;
                    } else {
                        actionHtml += `<button class="btn-primary" onclick="acceptReq(${user.friendship.id})">✓ Accept Friend Request</button>`;
                    }
                }
            } else {
                actionHtml += `<button class="btn-primary" onclick="sendFriendReqFromProfile(${user.id})">👤 Add Friend</button>`;
            }
        }

        document.getElementById('uProfActions').innerHTML = actionHtml;

    } catch (e) {
        console.error('openUserProfile error', e);
        document.getElementById('uProfUsername').textContent = 'Error loading profile';
    }
};

window.messageUserFromProfile = (userId, username, pic) => {
    document.getElementById('userProfileModal').style.display = 'none';
    if (window.selectUser) selectUser(userId, username, pic);
};

window.sendFriendReqFromProfile = async (userId) => {
    const form = new FormData();
    form.append('action', 'send');
    form.append('friend_id', userId);
    try {
        const res = await fetch('friends.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data.status === 'ok') {
            openUserProfile(userId); // Refresh
        } else {
            alert(data.error || 'Failed to send request');
        }
    } catch (e) {
        console.error(e);
    }
};

window.unfriendFromProfile = async (friendId, username) => {
    if (!confirm(`Are you sure you want to unfriend ${username}?`)) return;
    const form = new FormData();
    form.append('action', 'unfriend');
    form.append('friend_id', friendId);
    try {
        await fetch('friends.php', { method: 'POST', body: form });
        openUserProfile(friendId); // Refresh
        if (typeof loadFriendsTab === 'function' && currentTab === 'friends') loadFriendsTab();
        if (typeof fetchUsers === 'function') fetchUsers();
    } catch (e) {
        console.error(e);
    }
};

// Helper (reuse from script.js if available)
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
