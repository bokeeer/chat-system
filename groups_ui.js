/* ========================================================
   groups_ui.js — Choice Modal, Group Revamp, Channels
   ======================================================== */

// ─── Modal Elements ──────────────────────────────────────
const choiceModal = document.getElementById('choiceModal');
const createGroupModal = document.getElementById('createGroupModal');
const createChannelModal = document.getElementById('createChannelModal');
const joinLinkModal = document.getElementById('joinLinkModal');
const joinLinkInput = document.getElementById('joinLinkInput');

// ─── Button Overrides ────────────────────────────────────
// Override the existing ＋ button in index.php (different IDs for mobile/desktop)
const btnPlusDesktop = document.getElementById('btnCreateGroup');
const btnPlusMobile = document.getElementById('apiCreateGroup');

[btnPlusDesktop, btnPlusMobile].forEach(btn => {
    if (btn) {
        // Remove existing listeners if possible, or just add new one that stops propagation
        btn.onclick = (e) => {
            e.preventDefault();
            e.stopPropagation();
            choiceModal.style.display = 'flex';
        };
    }
});

// ─── Group Logic ─────────────────────────────────────────
async function openCreateGroupModal() {
    choiceModal.style.display = 'none';
    createGroupModal.style.display = 'flex';
    
    // Load users for selection
    const res = await fetch('fetch_users.php');
    const users = await res.json();
    const grpUsers = document.getElementById('grpUsers');
    
    if (Array.isArray(users)) {
        let html = '';
        users.forEach(u => {
            html += `
            <label class="modal-user">
                <input type="checkbox" name="grp_members" value="${u.id}">
                <div class="user-avatar" style="width:28px;height:28px;font-size:0.75rem;">${u.username[0].toUpperCase()}</div>
                <span>${escapeHtml(u.username)}</span>
            </label>`;
        });
        grpUsers.innerHTML = html || '<p style="text-align:center;opacity:0.6;">No other users found</p>';
    }
}

function closeCreateGroupModal() {
    createGroupModal.style.display = 'none';
}

const btnGrpCreate = document.getElementById('grpCreate');
if (btnGrpCreate) {
    btnGrpCreate.onclick = async () => {
        const name = document.getElementById('grpName').value.trim();
        if (!name) return alert('Please enter a group name');
        
        const selected = Array.from(document.querySelectorAll('input[name="grp_members"]:checked')).map(el => el.value);
        
        const form = new FormData();
        form.append('name', name);
        form.append('member_ids', selected.join(','));
        form.append('is_channel', '0');
        
        const res = await fetch('create_group.php', { method: 'POST', body: form });
        const data = await res.json();
        
        if (data.status === 'Success') {
            closeCreateGroupModal();
            fetchUsers(); // Refresh sidebar
            if (window.selectGroup) selectGroup(data.group_id, name);
        } else {
            alert(data.error || 'Failed to create group');
        }
    };
}

// ─── Channel Logic ───────────────────────────────────────
function openCreateChannelModal() {
    choiceModal.style.display = 'none';
    createChannelModal.style.display = 'flex';
}

function closeCreateChannelModal() {
    createChannelModal.style.display = 'none';
}

// Channel Avatar Preview
const chanAvatarInput = document.getElementById('channelAvatarInput');
const chanAvatarPreview = document.getElementById('channelAvatarPreview');

if (chanAvatarInput) {
    chanAvatarInput.onchange = () => {
        const file = chanAvatarInput.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                chanAvatarPreview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
            };
            reader.readAsDataURL(file);
        }
    };
}

const btnChanCreate = document.getElementById('chanCreate');
if (btnChanCreate) {
    btnChanCreate.onclick = async () => {
        const name = document.getElementById('chanName').value.trim();
        const desc = document.getElementById('chanDesc').value.trim();
        const type = document.querySelector('input[name="chanType"]:checked').value;
        const file = chanAvatarInput.files[0];
        
        if (!name) return alert('Please enter a channel name');
        
        const form = new FormData();
        form.append('name', name);
        form.append('description', desc);
        form.append('channel_type', type);
        form.append('is_channel', '1');
        if (file) form.append('avatar', file);
        
        const res = await fetch('create_group.php', { method: 'POST', body: form });
        const data = await res.json();
        
        if (data.status === 'Success') {
            closeCreateChannelModal();
            fetchUsers();
            
            if (data.join_token) {
                showJoinLink(data.join_token);
            }
            
            if (window.selectGroup) selectGroup(data.group_id, name);
        } else {
            alert(data.error || 'Failed to create channel');
        }
    };
}

// ─── Join Link Logic ─────────────────────────────────────
function showJoinLink(token) {
    const url = window.location.origin + window.location.pathname + '?join=' + token;
    joinLinkInput.value = url;
    joinLinkModal.style.display = 'flex';
}

function copyJoinLink() {
    joinLinkInput.select();
    document.execCommand('copy');
    alert('Link copied to clipboard!');
}

// Detect ?join=TOKEN on load
window.addEventListener('load', async () => {
    const params = new URLSearchParams(window.location.search);
    const joinToken = params.get('join');
    
    if (joinToken) {
        if (confirm('Do you want to join this group/channel?')) {
            const res = await fetch('join_channel.php?token=' + joinToken);
            const data = await res.json();
            if (data.status === 'Success') {
                alert('Joined ' + data.name + ' successfully!');
                // Wait for script.js to load then select
                setTimeout(() => {
                    if (window.selectGroup) selectGroup(data.group_id, data.name);
                }, 500);
            } else {
                alert(data.error || 'Failed to join');
            }
        }
        // Clean up URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// Handle clickable links in chat view
// This should be called by the message rendering logic in script.js
function makeLinksClickable(text) {
    // Regex for our join links
    const joinRegex = /(https?:\/\/[^\s]+[\?&]join=([a-f0-9]{32}))/gi;
    return text.replace(joinRegex, (match, url, token) => {
        return `<a href="#" onclick="handleJoinLinkClick('${token}'); return false;" class="chat-link">${match}</a>`;
    });
}

window.handleJoinLinkClick = async (token) => {
    if (confirm('Do you want to join this group/channel?')) {
        const res = await fetch('join_channel.php?token=' + token);
        const data = await res.json();
        if (data.status === 'Success') {
            fetchUsers();
            if (window.selectGroup) selectGroup(data.group_id, data.name);
        } else {
            alert(data.error || 'Failed to join');
        }
    }
};
// ─── Channel Settings & Member Management ────────────────
const channelSettingsModal = document.getElementById('channelSettingsModal');
const channelSettingsContent = document.getElementById('channelSettingsContent');

window.openChannelSettings = async () => {
    const groupId = window.currentChatId; // Assumes script.js sets this globally
    if (!groupId) return;
    
    channelSettingsModal.style.display = 'flex';
    channelSettingsContent.innerHTML = '<div style="text-align:center; padding:2rem; color:var(--text-secondary);">Loading settings...</div>';
    
    try {
        const res = await fetch(`fetch_group_details.php?group_id=${groupId}`);
        const data = await res.json();
        
        if (data.error) {
            channelSettingsContent.innerHTML = `<div style="color:#ef4444; padding:1rem;">Error: ${data.error}</div>`;
            return;
        }
        
        renderChannelSettings(data);
    } catch (err) {
        channelSettingsContent.innerHTML = `<div style="color:#ef4444; padding:1rem;">Failed to load settings.</div>`;
    }
};

window.closeChannelSettings = () => {
    channelSettingsModal.style.display = 'none';
};

function renderChannelSettings(data) {
    const s = data.settings;
    const m = data.members;
    const role = data.user_role;
    const isAdmin = data.is_admin;
    
    let html = `
        <!-- Section: Info -->
        <div style="background:var(--bg-color); border:1px solid var(--border-color); border-radius:10px; padding:1rem; margin-bottom:1.5rem;">
            <div style="display:flex; align-items:center; gap:1rem; margin-bottom:1rem; position:relative;">
                <div id="groupAvatarEdit" onclick="${isAdmin ? 'triggerGroupAvatarUpload()' : ''}" style="position:relative; cursor:${isAdmin ? 'pointer' : 'default'}">
                    <div class="user-avatar" id="groupSettingsAvatar" style="width:60px; height:60px; font-size:1.8rem; border:2px solid var(--border-color);">
                        ${s.profile_pic ? `<img src="${s.profile_pic}" style="width:100%;height:100%;object-fit:cover;">` : s.name[0].toUpperCase()}
                    </div>
                    ${isAdmin ? '<div style="position:absolute; bottom:0; right:0; background:var(--primary-color); border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; font-size:0.7rem; border:2px solid var(--bg-color);">✏️</div>' : ''}
                    <input type="file" id="groupAvatarInput" style="display:none;" onchange="handleGroupAvatarChange(this)">
                </div>
                <div style="flex:1;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <input type="text" id="groupNameInput" value="${escapeHtml(s.name)}" style="display:none; width:100%; margin-bottom:4px;" class="modal-input">
                        <h4 id="groupNameText" style="margin:0;">${escapeHtml(s.name)}</h4>
                        ${isAdmin ? `<button id="btnEditGroup" class="btn-icon" style="padding:4px; opacity:0.6;" onclick="toggleEditGroup(true)">✏️</button>` : ''}
                    </div>
                    <textarea id="groupDescInput" style="display:none; width:100%; margin-top:4px; font-size:0.85rem;" class="modal-input" rows="2">${escapeHtml(s.description || '')}</textarea>
                    <p id="groupDescText" style="margin:0; font-size:0.85rem; color:var(--text-secondary);">${escapeHtml(s.description || 'No description')}</p>
                    <div id="groupEditActions" style="display:none; margin-top:8px; gap:8px;">
                        <button class="btn-send" style="padding:4px 12px; font-size:0.75rem;" onclick="saveGroupProfile(${s.id})">Save</button>
                        <button class="btn-icon" style="padding:4px 12px; font-size:0.75rem; background:rgba(255,255,255,0.05);" onclick="toggleEditGroup(false)">Cancel</button>
                    </div>
                </div>
            </div>
            
            ${s.join_token ? `
                <div style="font-size:0.85rem; margin-bottom:1rem;">
                    <label style="display:block; color:var(--text-secondary); margin-bottom:4px;">Channel Join Link</label>
                    <div style="display:flex; gap:0.5rem;">
                        <input type="text" readonly value="${window.location.origin + window.location.pathname}?join=${s.join_token}" 
                               style="flex:1; background:rgba(0,0,0,0.2); border:1px solid var(--border-color); color:#60a5fa; padding:8px; border-radius:6px; font-size:0.75rem;">
                        <button class="btn-send" style="padding:4px 12px; font-size:0.8rem;" onclick="copySettingsLink(this)">Copy</button>
                    </div>
                </div>
            ` : ''}

            ${isAdmin ? `
                <div style="display:flex; align-items:center; justify-content:space-between; padding-top:1rem; border-top:1px solid var(--border-color);">
                    <div>
                        <strong style="display:block; font-size:0.9rem;">View Only Mode</strong>
                        <span style="font-size:0.8rem; color:var(--text-secondary);">Only admins can send messages</span>
                    </div>
                    <label class="switch">
                        <input type="checkbox" ${s.view_only == 1 ? 'checked' : ''} onchange="toggleViewOnly(${s.id}, this.checked)">
                        <span class="slider round"></span>
                    </label>
                </div>
            ` : ''}
        </div>

        <!-- Section: Members -->
        <div style="margin-bottom:1rem;">
            <h4 style="margin:0 0 1rem 0; font-size:1rem; display:flex; align-items:center; gap:8px; justify-content:space-between;">
                <span>Members <span style="font-size:0.8rem; opacity:0.6; font-weight:normal;">(${m.length})</span></span>
                <button class="btn-send" style="padding:4px 12px; font-size:0.75rem; border-radius:6px; white-space:nowrap;" onclick="openAddMemberModal()">＋ Add</button>
            </h4>
            <div class="modal-users" style="max-height: 250px;">
    `;
    
    m.forEach(mem => {
        const isSelf = mem.id == window.currentUserId; // Need window.currentUserId
        const canManage = isAdmin && (role === 'owner' || mem.role === 'member') && !isSelf;
        
        html += `
            <div class="modal-user" style="justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <div class="user-avatar" style="width:32px; height:32px; font-size:0.8rem; background:${mem.role === 'owner' ? '#eab308' : (mem.role === 'admin' ? '#8b5cf6' : 'var(--primary-color)')}">
                        ${mem.profile_pic ? `<img src="${mem.profile_pic}" style="width:100%;height:100%;object-fit:cover;">` : mem.username[0].toUpperCase()}
                    </div>
                    <div>
                        <div style="font-size:0.9rem;">${escapeHtml(mem.username)} ${isSelf ? '(You)' : ''}</div>
                        <div style="font-size:0.75rem; color:${mem.role === 'owner' ? '#facc15' : (mem.role === 'admin' ? '#a78bfa' : 'var(--text-secondary)')}; text-transform:capitalize;">${mem.role}</div>
                    </div>
                </div>
                
                ${canManage ? `
                    <div style="display:flex; gap:0.5rem;">
                        ${role === 'owner' ? `
                            <button onclick="toggleAdminRole(${s.id}, ${mem.id})" class="btn-icon" style="width:28px; height:28px; font-size:0.7rem; border-color:${mem.role === 'admin' ? '#8b5cf6' : 'rgba(255,255,255,0.1)'}" title="${mem.role === 'admin' ? 'Demote to Member' : 'Promote to Admin'}">
                                ${mem.role === 'admin' ? '⬇️' : '⬆️'}
                            </button>
                        ` : ''}
                        <button onclick="kickMember(${s.id}, ${mem.id})" class="btn-icon" style="width:28px; height:28px; font-size:0.7rem; border-color:rgba(239, 68, 68, 0.3); color:#ef4444;" title="Kick Member">✕</button>
                    </div>
                ` : ''}
            </div>
        `;
    });
    
    html += `
            </div>
        </div>

        <!-- Section: Actions -->
        <div style="margin-top:2rem; border-top:1px solid var(--border-color); padding-top:1rem;">
            <button onclick="leaveCurrentGroup(${s.id})" class="auth-btn" style="width:100%; padding:0.75rem; background:rgba(239, 68, 68, 0.1); color:#ef4444; border:1px solid rgba(239, 68, 68, 0.2); font-weight:500;">
                Leave Group
            </button>
        </div>
    `;
    
    // Store current members for filtering
    window._lastGroupMembers = m.map(member => parseInt(member.id));
    
    channelSettingsContent.innerHTML = html;
}

window.leaveCurrentGroup = async (groupId) => {
    if (!confirm('Are you sure you want to leave this group?')) return;
    
    const form = new FormData();
    form.append('group_id', groupId);
    
    try {
        const res = await fetch('leave_group.php', { method: 'POST', body: form });
        const text = await res.text();
        if (text === 'Success') {
            closeChannelSettings();
            if (typeof fetchUsers === 'function') fetchUsers();
            if (typeof resetChat === 'function') resetChat();
        } else {
            alert(text || 'Failed to leave group');
        }
    } catch (err) {
        alert('Error leaving group');
    }
};

window.copySettingsLink = (btn) => {
    const input = btn.previousElementSibling;
    input.select();
    document.execCommand('copy');
    const oldText = btn.innerText;
    btn.innerText = 'Copied!';
    setTimeout(() => btn.innerText = oldText, 2000);
};

window.toggleViewOnly = async (groupId, active) => {
    const form = new FormData();
    form.append('group_id', groupId);
    form.append('view_only', active ? 1 : 0);
    
    const res = await fetch('update_group_settings.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.error) alert(data.error);
    // Refresh header state in selectGroup if needed, but the toggle UI is enough here
};

window.kickMember = async (groupId, userId) => {
    if (!confirm('Are you sure you want to kick this member?')) return;
    
    const form = new FormData();
    form.append('group_id', groupId);
    form.append('user_id', userId);
    form.append('action', 'kick');
    
    const res = await fetch('manage_group_member.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'Success') {
        openChannelSettings(); // Refresh
    } else {
        alert(data.error || 'Failed to kick member');
    }
};

window.toggleAdminRole = async (groupId, userId) => {
    const form = new FormData();
    form.append('group_id', groupId);
    form.append('user_id', userId);
    form.append('action', 'toggle_admin');
    
    const res = await fetch('manage_group_member.php', { method: 'POST', body: form });
    const data = await res.json();
    if (data.status === 'Success') {
        openChannelSettings(); // Refresh
    } else {
        alert(data.error || 'Failed to change admin status');
    }
};

// ─── Add Member Functional Section ───────────────────────
const addMemberSearch = document.getElementById('addMemberSearch');
const addMemberResults = document.getElementById('addMemberResults');

window.openAddMemberModal = () => {
    const modal = document.getElementById('addMemberModal');
    modal.style.display = 'flex';
    if (addMemberSearch) addMemberSearch.value = '';
    searchAddMember('');
};

if (addMemberSearch) {
    addMemberSearch.oninput = () => searchAddMember(addMemberSearch.value.trim());
}

async function searchAddMember(query) {
    let users = [];
    try {
        if (!query) {
            // Fetch friends by default
            const res = await fetch('friends.php?action=list');
            users = await res.json();
        } else {
            // Fetch users by search
            const res = await fetch('fetch_users.php?search=' + encodeURIComponent(query));
            users = await res.json();
        }
    } catch (e) {
        console.error('Search add member error', e);
    }
    
    // We already have members from current settings view. 
    // Let's filter out to make it cleaner, although the PHP also handles it.
    
    let html = '';
    const currentMemberIds = window._lastGroupMembers || [];
    
    if (Array.isArray(users)) {
        users.forEach(u => {
            // Filter out existing members
            if (currentMemberIds.includes(parseInt(u.id))) return;

            // Only show actual users, not channels or groups
            if (u.item_type && u.item_type !== 'user') return;
            if (u.is_channel) return;

            const initial = u.username[0].toUpperCase();
            html += `
                <div class="modal-user" style="justify-content:space-between; padding: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.03);">
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <div class="user-avatar" style="width:32px; height:32px; font-size:0.8rem; background:var(--primary-color); border-radius:50%; overflow:hidden;">
                            ${u.profile_pic ? `<img src="${u.profile_pic}" style="width:100%;height:100%;object-fit:cover;">` : initial}
                        </div>
                        <div style="font-size:0.9rem;">${escapeHtml(u.username)}</div>
                    </div>
                    <button onclick="addGroupMember(${u.id})" class="btn-send" style="padding:4px 14px; font-size:0.75rem; border-radius:6px;">Add</button>
                </div>
            `;
        });
    }
    addMemberResults.innerHTML = html || '<div style="text-align:center; padding:2rem; opacity:0.6; font-size:0.9rem;">No users found</div>';
}

window.addGroupMember = async (userId) => {
    const groupId = window.currentChatId;
    if (!groupId) return;
    
    const form = new FormData();
    form.append('group_id', groupId);
    form.append('user_id', userId);
    form.append('action', 'add');
    
    try {
        const res = await fetch('manage_group_member.php', { method: 'POST', body: form });
        const data = await res.json();
        if (data.status === 'Success') {
            openChannelSettings(); // Refresh settings member list
            // Optionally clear search or keep searching
            if (addMemberSearch) searchAddMember(addMemberSearch.value.trim());
        } else {
            alert(data.error || 'Failed to add user');
        }
    } catch (err) {
        alert('An error occurred while adding the user.');
    }
};
// ─── Group Profile Editing ───────────────────
window.toggleEditGroup = (editing) => {
    document.getElementById('groupNameInput').style.display = editing ? 'block' : 'none';
    document.getElementById('groupNameText').style.display = editing ? 'none' : 'block';
    document.getElementById('groupDescInput').style.display = editing ? 'block' : 'none';
    document.getElementById('groupDescText').style.display = editing ? 'none' : 'block';
    document.getElementById('groupEditActions').style.display = editing ? 'flex' : 'none';
    document.getElementById('btnEditGroup').style.display = editing ? 'none' : 'block';
};

window.triggerGroupAvatarUpload = () => {
    document.getElementById('groupAvatarInput').click();
};

window.handleGroupAvatarChange = (input) => {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const avatarDiv = document.getElementById('groupSettingsAvatar');
            avatarDiv.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
        // Show edit actions if not already editing
        window.toggleEditGroup(true);
    }
};

window.saveGroupProfile = async (groupId) => {
    const name = document.getElementById('groupNameInput').value.trim();
    const desc = document.getElementById('groupDescInput').value.trim();
    const avatar = document.getElementById('groupAvatarInput').files[0];

    if (!name) return alert("Group name cannot be empty");

    const formData = new FormData();
    formData.append('group_id', groupId);
    formData.append('name', name);
    formData.append('description', desc);
    if (avatar) formData.append('avatar', avatar);

    try {
        const res = await fetch('update_group_profile.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.status === 'Success') {
            window.toggleEditGroup(false);
            window.openChannelSettings(); // Refresh
            // If the current chat is this group, refresh title too
            if (window.currentChatId == groupId) {
                 document.getElementById('chatTitle').textContent = name;
                 if (data.profile_pic) {
                      const headerAvatar = document.getElementById('chatHeaderAvatar');
                      headerAvatar.innerHTML = `<img src="${data.profile_pic}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
                      headerAvatar.style.backgroundColor = 'transparent';
                 }
            }
            fetchUsers(); // Refresh sidebar
        } else {
            alert(data.error || 'Failed to update profile');
        }
    } catch (e) {
        console.error('Error updating group:', e);
    }
};
