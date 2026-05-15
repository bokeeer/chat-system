<?php
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod|IEMobile|Opera Mini|Windows Phone/i', $ua);
$apiMode = (isset($_GET['mode']) && $_GET['mode'] === 'api') || $isMobile;
if ($apiMode) {
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b1220">
<title>Mobile Chat</title>
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#0f172a;color:#e2e8f0;-webkit-tap-highlight-color:transparent;touch-action:manipulation}
.wrap{max-width:720px;margin:0 auto}
.top{display:flex;align-items:center;gap:8px;justify-content:space-between;padding:12px;border-bottom:1px solid #1f2947;background:#0b1220;position:sticky;top:0;z-index:10;min-height:56px}
.brand{font-weight:600}
.btn{background:#6366f1;border:none;color:#fff;padding:10px 14px;border-radius:10px}
.btn.alt{background:#334155}
.tabs{display:flex;margin:12px}
.tab{flex:1;text-align:center;padding:10px;border:1px solid #1e293b;color:#94a3b8}
.tab.active{background:#111827;color:#e5e7eb;border-color:#334155}
.card{margin:12px;border:1px solid #1e293b;border-radius:12px;background:#0b1220}
.pad{padding:14px}
.row{display:flex;gap:10px;margin-bottom:10px}
.row>input{flex:1;padding:12px;border:1px solid #1e293b;border-radius:10px;background:#0f172a;color:#e2e8f0}
.msg{padding:10px;border-radius:10px;margin:6px 0;max-width:80%;word-wrap:break-word}
.msg.self{background:#1f3b57;margin-left:auto}
.msg.other{background:#1e293b;margin-right:auto}
.time{opacity:.7;font-size:12px;margin-left:8px}
.grid{display:grid;grid-template-columns:1fr;gap:12px}
.col{border:1px solid #1e293b;border-radius:12px;background:#0b1220;overflow:hidden}
.header{padding:12px;border-bottom:1px solid #1e293b}
.list{max-height:260px;overflow:auto}
.item{display:flex;align-items:center;gap:10px;padding:12px;border-bottom:1px solid #0f172a}
.item.active{background:#0f172a}
.avatar{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#6366f1;color:#fff;font-weight:600}
.name{flex:1}
.chat{display:flex;flex-direction:column;height:calc(100dvh - 56px - 24px)}
.chatlog{flex:1;overflow:auto;padding:12px}
.composer{display:flex;gap:10px;padding:12px;border-top:1px solid #1e293b}
.composer input{flex:1;padding:12px;border:1px solid #1e293b;border-radius:10px;background:#0f172a;color:#e2e8f0}
.hint{font-size:13px;color:#93c5fd}
.hide{display:none}
.icon{width:36px;height:36px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;background:#1e293b;color:#e2e8f0}
@media(max-width:639px){
  html, body{height:100%}
  body{min-height:100svh;height:100svh;display:block;justify-content:unset;align-items:unset}
  .sidebar{width:100% !important;min-width:0;border-right:none}
  #colChat{display:none}
  body.in-chat #colUsers{display:none}
  body.in-chat #colChat{display:block}
  #btnBack{display:none}
  body.in-chat #btnBack{display:inline-flex}
  .app-container{width:100vw;max-width:none;height:100svh;min-height:100svh;border-radius:0;border:none;box-shadow:none}
  .chat-area{height:100%;display:flex;flex-direction:column}
  .chat-area, .sidebar{width:100%}
  .chat{flex:1;min-height:0}
  .composer{padding-bottom:calc(12px + env(safe-area-inset-bottom,0px))}
  /* New mobile flow: list first, chat fullscreen when in-chat */
  body:not(.in-chat) .chat-area{display:none}
  body:not(.in-chat) .sidebar{display:flex}
  body.in-chat .sidebar{display:none}
  body.in-chat .chat-area{display:flex;width:100%}
  .chat-messages{padding:1rem;flex:1;min-height:0}
}
@media(min-width:640px){.grid{grid-template-columns:240px 1fr}.list{max-height:calc(100vh - 220px)}.chat{height:calc(100vh - 190px)}}
body.sidebar-mini .sidebar{width:64px;min-width:64px}
body.sidebar-mini .sidebar .sidebar-header span,
body.sidebar-mini .search-bar,
body.sidebar-mini .user-info,
body.sidebar-mini .user-top,
body.sidebar-mini .user-bottom{display:none}
body.sidebar-mini .user-item{justify-content:center}
</style>
</head>
<body>
<div class="auth-container" id="auth">
  <div class="auth-card">
    <div id="loginHead">
      <h2>Welcome Back</h2>
      <p>Enter your credentials to access your account</p>
    </div>
    <div id="registerHead" class="hide">
      <h2>Create Account</h2>
      <p>Join our secure messaging platform today</p>
    </div>
    <div class="auth-form" id="loginPane">
      <div class="form-group">
        <label>Username</label>
        <input id="loginUser" placeholder="Username" autocomplete="username">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input id="loginPass" type="password" placeholder="••••••••" autocomplete="current-password">
      </div>
      <button id="btnLogin" class="auth-btn">Sign In</button>
      <div id="loginMsg" class="error-msg" style="display:none"></div>
      <div class="auth-link">Don't have an account? <a href="#" id="toRegister">Sign up</a></div>
    </div>
    <div class="auth-form hide" id="registerPane">
      <div class="form-group">
        <label>Username</label>
        <input id="regUser" placeholder="Choose a username" autocomplete="username">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input id="regPass" type="password" placeholder="Create a password" autocomplete="new-password">
      </div>
      <button id="btnRegister" class="auth-btn">Sign Up</button>
      <div id="regMsg" class="error-msg" style="display:none"></div>
      <div class="auth-link">Already have an account? <a href="#" id="toLogin">Sign in</a></div>
    </div>
  </div>
 </div>

<div class="app-container hide" id="app">
  <div class="sidebar">
    <div class="sidebar-header">
      <div style="display:flex; align-items:center; gap:0.75rem;">
        <div class="user-avatar" id="apiAvatar" style="background-color: var(--primary-color);">U</div>
        <span id="apiUsernameBadge" style="font-weight:600; color: var(--text-color);">User</span>
      </div>
      <div style="display:flex;align-items:center;gap:.5rem;">
        <button id="btnFriendRequestsMobile" class="btn-icon" title="Friend Requests" style="position:relative;">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          <span id="friendReqBadgeMobile" style="display:none;position:absolute;top:-2px;right:-2px;background:#ef4444;color:#fff;border-radius:50%;width:16px;height:16px;font-size:0.6rem;align-items:center;justify-content:center;font-weight:bold;box-shadow:0 0 0 2px var(--sidebar-bg);">0</span>
        </button>
        <button id="btnAddFriendMobile" class="btn-icon" title="Add Friend">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="16" y1="11" x2="22" y2="11"></line></svg>
        </button>
        <button id="apiCreateGroup" class="btn-icon" title="New Group / Channel">
          <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        </button>
        <button id="btnSettingsMobile" class="btn-icon" title="Settings">
          <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
        </button>
      </div>
    </div>
    <!-- Sidebar Tabs -->
    <div class="sidebar-tabs">
      <button class="sidebar-tab active" id="tabAllMobile" onclick="switchTabMobile('all')">All Chats</button>
      <button class="sidebar-tab" id="tabFriendsMobile" onclick="switchTabMobile('friends')">Friends</button>
    </div>
    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Search or start new chat">
    </div>
    <div class="user-list" id="userList"></div>
  </div>
  <div class="chat-area">
    <div class="chat-header">
      <button id="btnBack" class="btn-icon" title="Back" style="margin-right:.5rem;">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><polyline points="15 18 9 12 15 6"></polyline></svg>
      </button>
      <div class="user-avatar" id="chatHeaderAvatar" style="background-color:#ccc; margin-right:1rem; visibility:hidden;">?</div>
      <div style="display:flex; flex-direction:column;">
        <h2 id="chatTitle" style="margin:0; font-size:1.1rem; font-weight:500;">Select a chat to start messaging</h2>
        <span id="chatStatus" style="font-size:0.8rem; color: var(--text-secondary); visibility:hidden;">online</span>
      </div>
      <div style="margin-left:auto;">
      </div>
    </div>
    <div class="chat-messages" id="chatArea"></div>
    <form id="chatForm" class="chat-input-area" style="display:none;" onsubmit="event.preventDefault();sendMessage();">
      <input type="file" id="fileInputMobile" style="display:none;" onchange="handleFileUpload(this)">
      <button type="button" class="btn-icon" onclick="document.getElementById('fileInputMobile').click()" style="padding:0; min-width:32px; height:32px;">📎</button>
      <input type="text" id="messageInput" placeholder="Type a message" autocomplete="off">
      <button type="submit" class="btn-send">➤</button>
    </form>
  </div>
  
  <!-- Settings Modal -->
  <div class="modal-overlay" id="settingsModalMobile" style="display:none;">
    <div class="modal" style="max-width:350px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3 style="margin:0;">Settings</h3>
            <button class="modal-close" onclick="document.getElementById('settingsModalMobile').style.display='none'">✕</button>
        </div>
        <div class="form-group" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;background:rgba(255,255,255,0.03);padding:1rem;border-radius:12px;border:1px solid var(--border-color);">
            <div>
                <strong style="display:block;margin-bottom:0.25rem;">Private Account</strong>
                <p style="font-size:0.75rem;color:var(--text-secondary);margin:0;">Only friends can message you</p>
            </div>
            <label class="switch">
                <input type="checkbox" id="privacyToggleMobile">
                <span class="slider round"></span>
            </label>
        </div>
        <hr style="border:0; border-top:1px solid var(--border-color); margin: 1rem 0;">
        <button id="btnSettingsLogoutMobile" class="auth-btn" style="width:100%;margin:0;background-color:rgba(239, 68, 68, 0.1);color:#ef4444;border:1px solid rgba(239, 68, 68, 0.2);">
            Logout
        </button>
    </div>
  </div>

  <!-- Add Friend Modal -->
  <div class="modal-overlay" id="addFriendModalMobile" style="display:none;">
    <div class="modal">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
        <h3 style="margin:0;">Add a Friend</h3>
        <button class="modal-close" onclick="document.getElementById('addFriendModalMobile').style.display='none'">✕</button>
      </div>
      <input type="text" id="addFriendSearchMobile" placeholder="Search by username..." style="width:100%;padding:.6rem .8rem;border:1px solid var(--border-color);border-radius:8px;background:var(--bg-color);color:var(--text-color);margin-bottom:.75rem;">
      <div id="addFriendResultsMobile" class="modal-users" style="max-height:280px;"></div>
    </div>
  </div>

  <!-- Friend Requests Modal -->
  <div class="modal-overlay" id="friendReqModalMobile" style="display:none;">
    <div class="modal">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
        <h3 style="margin:0;">Friend Requests</h3>
        <button class="modal-close" onclick="document.getElementById('friendReqModalMobile').style.display='none'">✕</button>
      </div>
      <div id="friendReqListMobile" class="modal-users" style="max-height:240px;"></div>
      <hr style="border:0; border-top:1px solid var(--border-color); margin: 1rem 0;">
      <h3 style="margin:0 0 .75rem 0;">Your Friends</h3>
      <div id="friendModalListMobile" class="modal-users" style="max-height:240px;"></div>
    </div>
  </div>

</div>
<script>
const base='api.php';
let me=null,contactId=null,groupId=null,lastId=0;
const loginUser=document.getElementById('loginUser');
const loginPass=document.getElementById('loginPass');
const regUser=document.getElementById('regUser');
const regPass=document.getElementById('regPass');
const btnLogin=document.getElementById('btnLogin');
const btnRegister=document.getElementById('btnRegister');
const loginMsg=document.getElementById('loginMsg');
const regMsg=document.getElementById('regMsg');
const auth=document.getElementById('auth');
const app=document.getElementById('app');
const userList=document.getElementById('userList');
const chatArea=document.getElementById('chatArea');
const chatTitle=document.getElementById('chatTitle');
const chatHeaderAvatar=document.getElementById('chatHeaderAvatar');
const chatForm=document.getElementById('chatForm');
const messageInput=document.getElementById('messageInput');
const searchInput=document.getElementById('searchInput');
const apiUsernameBadge=document.getElementById('apiUsernameBadge');
const apiAvatar=document.getElementById('apiAvatar');
const apiLogout=document.getElementById('apiLogout');
const btnBack=document.getElementById('btnBack');
const apiCreateGroup=document.getElementById('apiCreateGroup');
const apiLeaveGroup=document.getElementById('apiLeaveGroup');
const toRegister=document.getElementById('toRegister');
const toLogin=document.getElementById('toLogin');
const loginHead=document.getElementById('loginHead');
const registerHead=document.getElementById('registerHead');
const btnSettingsMobile=document.getElementById('btnSettingsMobile');
const settingsModalMobile=document.getElementById('settingsModalMobile');
const privacyToggleMobile=document.getElementById('privacyToggleMobile');
const btnSettingsLogoutMobile=document.getElementById('btnSettingsLogoutMobile');

function setMe(u){
  me=u;
  localStorage.setItem('me',JSON.stringify(u));
  auth.classList.add('hide');
  app.classList.remove('hide');
  apiUsernameBadge.textContent=u.username;
  const initial=u.username.charAt(0).toUpperCase();
  apiAvatar.style.overflow='hidden';
  apiAvatar.innerHTML=u.profile_pic ? '<img src="'+u.profile_pic+'" style="width:100%;height:100%;object-fit:cover;">' : initial;
  document.body.classList.remove('in-chat');
  loadUsers();
  resetChat();
}
function loadMe(){const s=localStorage.getItem('me');if(s){try{const u=JSON.parse(s);if(u&&u.id&&u.username){setMe(u)}}catch(e){}}}

async function postForm(url,data){const p=new URLSearchParams();Object.keys(data).forEach(k=>p.append(k,data[k]??''));const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:p});const t=await r.text();try{return{ok:r.ok,json:JSON.parse(t)}}catch(e){return{ok:r.ok,json:{},text:t}}}
async function getJson(url){const r=await fetch(url);return await r.json()}

btnRegister.onclick=async()=>{regMsg.style.display='none';const username=regUser.value.trim();const password=regPass.value;if(!username||!password){regMsg.textContent='Enter username and password';regMsg.style.display='block';return}const res=await postForm(base+'/users',{username,password});if(res.ok){regMsg.textContent='Account created. You can login.';regMsg.style.display='block'}else{regMsg.textContent=(res.json&&res.json.error==='username exists')?'Username already taken':'Failed to register';regMsg.style.display='block'}};
btnLogin.onclick=async()=>{loginMsg.style.display='none';const username=loginUser.value.trim();const password=loginPass.value;if(!username||!password){loginMsg.textContent='Enter username and password';loginMsg.style.display='block';return}const res=await postForm(base+'/login',{username,password});if(res.ok){setMe(res.json)}else{loginMsg.textContent='Invalid credentials';loginMsg.style.display='block'}};
if(toRegister){toRegister.onclick=(e)=>{e.preventDefault();loginPane.classList.add('hide');registerPane.classList.remove('hide');loginHead.classList.add('hide');registerHead.classList.remove('hide')}}
if(toLogin){toLogin.onclick=(e)=>{e.preventDefault();registerPane.classList.add('hide');loginPane.classList.remove('hide');registerHead.classList.add('hide');loginHead.classList.remove('hide')}}

async function loadUsers(){const q=searchInput&&searchInput.value?('?search='+encodeURIComponent(searchInput.value)) : '';const arr=await getJson(base+'/users?user_id='+(me?me.id:'')+q.replace('?','&'));let html='';const isGlobalActive=contactId===null;html+=('<div class=\"user-item '+(isGlobalActive?'active':'')+'\" data-id=\"\" data-name=\"Global Chat\" title=\"Global Chat\" aria-label=\"Global Chat\"><div class=\"user-avatar\" style=\"background-color:#6366f1;\">G</div><div class=\"user-info\"><div class=\"user-top\"><span class=\"user-name\">Global Chat</span><span class=\"user-time\"></span></div><div class=\"user-bottom\"><span class=\"last-message\">Public Room</span><span class=\"unread-badge\" style=\"display:none\">0</span></div></div></div>');arr.forEach(u=>{const isActive=contactId==u.id;const initial=u.username.charAt(0).toUpperCase();const safe=esc(u.username);let timeDisplay='';if(u.last_time){const d=new Date(u.last_time.replace(' ','T'));if(!isNaN(d))timeDisplay=d.toLocaleDateString([], {month:'short',day:'numeric'});}const unread=u.unread_count&&u.unread_count>0;const lastMsg=u.last_message?esc(u.last_message):'';html+='<div class=\"user-item '+(isActive?'active':'')+'\" data-id=\"'+u.id+'\" data-name=\"'+safe+'\" title=\"'+safe+'\" aria-label=\"'+safe+'\"><div class=\"user-avatar\" style=\"background-color: var(--primary-color); box-shadow: 0 0 5px rgba(99,102,241,0.4);\">'+initial+'</div><div class=\"user-info\"><div class=\"user-top\"><span class=\"user-name\">'+safe+'</span><span class=\"user-time\">'+timeDisplay+'</span></div><div class=\"user-bottom\"><span class=\"last-message\" style=\"'+(unread?'font-weight:600; color: var(--text-color);':'')+'\">'+(lastMsg||'')+'</span>'+(unread?'<div class=\"unread-badge\">'+u.unread_count+'</div>':'')+'</div></div></div>';});userList.innerHTML=html;Array.from(userList.querySelectorAll('.user-item')).forEach(el=>{el.onclick=()=>{const id=el.getAttribute('data-id');selectContact(id?parseInt(id):null,el.getAttribute('data-name'))}})}
async function loadUsers2(){
  const q = (searchInput && searchInput.value) ? ('?search='+encodeURIComponent(searchInput.value)) : '';
  const users = await getJson(base+'/users?user_id='+(me?me.id:'')+q.replace('?','&'));
  const groups = await getJson(base+'/groups?user_id='+(me?me.id:''));
  let html = '';
  const isGlobalActive = (contactId===null && groupId===null);
  html += '<div class="user-item '+(isGlobalActive?'active':'')+'" data-type="global" data-id="" data-name="Global Chat" title="Global Chat" aria-label="Global Chat"><div class="user-avatar" style="background-color:#6366f1;">G</div><div class="user-info"><div class="user-top"><span class="user-name">Global Chat</span><span class="user-time"></span></div><div class="user-bottom"><span class="last-message">Public Room</span></div></div></div>';
  (Array.isArray(groups)?groups:[]).forEach(g=>{
    const isActive = (groupId==g.id);
    const initial = (g.name&&g.name.length?g.name.charAt(0).toUpperCase():'G');
    const safe = esc(g.name||'Group');
    const avHtml = g.profile_pic ? '<img src="'+g.profile_pic+'" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">' : initial;
    let timeDisplay='';
    if(g.last_time){const d=new Date(g.last_time.replace(' ','T'));if(!isNaN(d))timeDisplay=d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});}
    const lastMsg=g.last_message?esc(g.last_message):'';
    html+='<div class="user-item '+(isActive?'active':'')+'" data-type="group" data-gid="'+g.id+'" data-name="'+safe+'" title="'+safe+'" aria-label="'+safe+'"><div class="user-avatar" style="background-color:#1e293b;overflow:hidden;">'+avHtml+'</div><div class="user-info"><div class="user-top"><span class="user-name">'+safe+'</span><span class="user-time">'+timeDisplay+'</span></div><div class="user-bottom"><span class="last-message">'+(lastMsg||'')+'</span></div></div></div>';
  });
  (Array.isArray(users)?users:[]).forEach(u=>{
    const isActive=(contactId==u.id && groupId===null);
    const initial=u.username.charAt(0).toUpperCase();
    const safe=esc(u.username);
    const avHtml = u.profile_pic ? '<img src="'+u.profile_pic+'" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">' : initial;
    let timeDisplay='';
    if(u.last_time){const d=new Date(u.last_time.replace(' ','T'));if(!isNaN(d))timeDisplay=d.toLocaleDateString([], {month:'short',day:'numeric'});}
    const unread=u.unread_count&&u.unread_count>0;
    const lastMsg=u.last_message?esc(u.last_message):'';
    html+='<div class="user-item '+(isActive?'active':'')+'" data-type="user" data-id="'+u.id+'" data-name="'+safe+'" data-canmessage="'+(u.can_message?'true':'false')+'" title="'+safe+'" aria-label="'+safe+'"><div class="user-avatar" style="background-color: var(--primary-color); box-shadow: 0 0 5px rgba(99,102,241,0.4);overflow:hidden;">'+avHtml+'</div><div class="user-info"><div class="user-top"><span class="user-name">'+safe+'</span><span class="user-time">'+timeDisplay+'</span></div><div class="user-bottom"><span class="last-message" style="'+(unread?'font-weight:600; color: var(--text-color);':'')+'">'+(lastMsg||'')+'</span>'+(unread?'<div class="unread-badge">'+u.unread_count+'</div>':'')+'</div></div></div>';
  });
  userList.innerHTML = html;
  Array.from(userList.querySelectorAll('.user-item')).forEach(el=>{
    el.onclick=()=>{
      const type=el.getAttribute('data-type');
      if(type==='global'){selectContact(null,'Global Chat');}
      else if(type==='group'){const gid=parseInt(el.getAttribute('data-gid'));const name=el.getAttribute('data-name');selectGroup(gid,name);}
      else {const id=el.getAttribute('data-id');selectContact(id?parseInt(id):null,el.getAttribute('data-name'),el.getAttribute('data-canmessage')==='true');}
    };
  });
}
loadUsers = loadUsers2;
let renderedIds=new Set();
function resetChat(){contactId=null;groupId=null;lastId=0;renderedIds=new Set();chatTitle.textContent='Global Chat';chatArea.innerHTML='';chatForm.style.display='none';chatHeaderAvatar.style.visibility='hidden';if(apiLeaveGroup)apiLeaveGroup.style.display='none'}
function selectContact(id,name,canMessage=true){contactId=id;groupId=null;lastId=0;renderedIds=new Set();lastThreadSenderId=null;chatTitle.textContent=name;chatArea.innerHTML='';chatForm.style.display='flex';chatHeaderAvatar.style.visibility='visible';chatHeaderAvatar.innerText=name&&name.length?name.charAt(0).toUpperCase():'?';chatHeaderAvatar.style.backgroundColor=id? 'var(--primary-color)' : '#6366f1';if(canMessage){messageInput.disabled=false;messageInput.placeholder="Type a message"}else{messageInput.disabled=true;messageInput.placeholder="this user is set the account on private"}if(apiLeaveGroup)apiLeaveGroup.style.display='none';if(window.innerWidth<640){document.body.classList.add('in-chat')}loadUsers();loadMessages()}
function selectGroup(gid,name){groupId=gid;contactId=null;lastId=0;renderedIds=new Set();lastThreadSenderId=null;chatTitle.textContent=name;chatArea.innerHTML='';chatForm.style.display='flex';chatHeaderAvatar.style.visibility='visible';chatHeaderAvatar.innerText=name&&name.length?name.charAt(0).toUpperCase():'G';chatHeaderAvatar.style.backgroundColor='#1e293b';messageInput.disabled=false;messageInput.placeholder="Type a message";if(apiLeaveGroup)apiLeaveGroup.style.display='inline-flex';if(window.innerWidth<640){document.body.classList.add('in-chat')}loadUsers();loadMessages()}

let isLoadingMessages=false;
let lastThreadSenderId=null;
async function loadMessages(){
  if(!me||isLoadingMessages)return;
  isLoadingMessages=true;
  try{
    let url=base+'/messages?user_id='+me.id+'&last_id='+lastId;
    if(groupId)url+='&group_id='+groupId; else if(contactId)url+='&contact_id='+contactId;
    const arr=await getJson(url);
    if(!Array.isArray(arr)||arr.length===0)return;
    let seenOther=false;
    const isGroupChat=(groupId!==null)||(contactId===null);
    arr.forEach(m=>{
      if(renderedIds.has(m.id)) return;
      const d=new Date(m.created_at);
      const t=d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
      const isSelf=m.is_self==1;
      const isNewGroup=(m.sender_id!=lastThreadSenderId);
      if(!isSelf) seenOther=true;
      const container=document.createElement('div');
      container.className='msg-container '+(isSelf?'self':'other')+' '+(isNewGroup?'new-group':'')+' last-in-group';
      container.setAttribute('data-sender-id',m.sender_id||'');
      let avatarHtml='';
      let identityHtml='';
      if(!isSelf){
        if(!isNewGroup){
          const prev=chatArea.querySelector('.msg-container.other.last-in-group:last-child');
          if(prev&&prev.getAttribute('data-sender-id')==m.sender_id){
            const pa=prev.querySelector('.msg-sender-avatar');
            if(pa)pa.outerHTML='<div class="msg-avatar-placeholder"></div>';
            prev.classList.remove('last-in-group');
          }
        }
        const initial=m.username?m.username.charAt(0).toUpperCase():'?';
        const avContent=m.profile_pic?'<img src="'+m.profile_pic+'">':initial;
        avatarHtml='<div class="msg-sender-avatar" title="'+esc(m.username)+'">'+avContent+'</div>';
        if(isGroupChat&&isNewGroup){
          identityHtml='<div class="msg-sender-info"><span class="msg-sender-name">'+esc(m.username)+'</span></div>';
        }
      } else {
        if(!isNewGroup){
          const prev=chatArea.querySelector('.msg-container.self.last-in-group:last-child');
          if(prev&&prev.getAttribute('data-sender-id')==m.sender_id) prev.classList.remove('last-in-group');
        }
      }
      container.innerHTML=avatarHtml+'<div class="msg-column">'+identityHtml+'<div class="message '+(isSelf?'self':'other')+'">'+esc(m.message)+'<span class="time">'+t+'</span></div></div>';
      chatArea.appendChild(container);
      lastId=Math.max(lastId,m.id);
      renderedIds.add(m.id);
      lastThreadSenderId=m.sender_id;
    });
    chatArea.scrollTop=chatArea.scrollHeight;
    if(seenOther&&contactId){
      await postForm(base+'/messages/read',{user_id:me.id,sender_id:contactId});
    }
  } finally {
    isLoadingMessages=false;
  }
}
function esc(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML}
function resetAuthFields(){if(loginUser)loginUser.value='';if(loginPass)loginPass.value='';if(regUser)regUser.value='';if(regPass)regPass.value='';}
if(btnSettingsLogoutMobile){btnSettingsLogoutMobile.onclick=(e)=>{e.preventDefault();localStorage.removeItem('me');me=null;resetAuthFields();auth.classList.remove('hide');app.classList.add('hide');document.body.classList.remove('in-chat');settingsModalMobile.style.display='none';}};
if(btnSettingsMobile){btnSettingsMobile.onclick=async()=>{settingsModalMobile.style.display='flex';try{const r=await fetch('update_privacy.php?user_id='+me.id);const d=await r.json();if(privacyToggleMobile)privacyToggleMobile.checked=(d.is_private===1);}catch(e){}};}
if(privacyToggleMobile){privacyToggleMobile.onchange=async()=>{const isPrivate=privacyToggleMobile.checked?1:0;const fd=new FormData();fd.append('is_private',isPrivate);try{await fetch('update_privacy.php',{method:'POST',body:fd});}catch(e){}};}
if(searchInput){searchInput.addEventListener('input',()=>{loadUsers()})}
chatForm.addEventListener('submit',async e=>{e.preventDefault();await sendMessage();});
let isSending=false;
async function sendMessage(){
  if(!me||isSending)return;
  const message=messageInput.value.trim();
  if(!message)return;
  isSending=true;
  messageInput.value='';
  const data={user_id:me.id,message};
  if(groupId)data.group_id=groupId; else if(contactId)data.receiver_id=contactId;
  try{
    const res=await postForm(base+'/messages',data);
    if(res.ok){await loadMessages();}else{alert(res.json.error||res.text||'Failed to send');}
  }finally{
    isSending=false;
  }
}
btnBack.onclick=()=>{document.body.classList.remove('in-chat')};
window.addEventListener('resize',()=>{if(window.innerWidth>=640){document.body.classList.remove('in-chat')}});

setInterval(loadMessages,1000);
setInterval(loadUsers,5000);
loadMe();
 
// load groups periodically and add minimal group UI
async function loadGroups(){
  if(!me)return;
  try{
    const arr=await getJson(base+'/groups?user_id='+me.id);
    const wrapId='groups-wrap';
    const wrap=document.getElementById(wrapId);
    if(document.getElementById(wrapId)){document.getElementById(wrapId).remove();}
    let html='<div id="'+wrapId+'">';
    (Array.isArray(arr)?arr:[]).forEach(g=>{
      const isActive=(window.groupId==g.id);
      const initial=(g.name&&g.name.length?g.name.charAt(0).toUpperCase():'G');
      const safe=esc(g.name||'Group');
      const last=g.last_message?esc(g.last_message):'';
      html+='<div class="user-item '+(isActive?'active':'')+'" data-type="group" data-gid="'+g.id+'" data-name="'+safe+'"><div class="user-avatar" style="background-color:#1e293b;">'+initial+'</div><div class="user-info"><div class="user-top"><span class="user-name">'+safe+'</span></div><div class="user-bottom"><span class="last-message">'+last+'</span></div></div></div>';
    });
    html+='</div>';
    userList.insertAdjacentHTML('afterbegin',html);
    Array.from(document.querySelectorAll('#'+wrapId+' .user-item')).forEach(el=>{
      el.onclick=()=>{const gid=parseInt(el.getAttribute('data-gid'));const name=el.getAttribute('data-name');groupId=gid;selectGroup(gid,name);}
    });
  }catch(e){}
}
if(apiCreateGroup){apiCreateGroup.onclick=(e)=>{e.preventDefault();openGroupCreateMobile();};}
if(apiLeaveGroup){apiLeaveGroup.onclick=async(e)=>{e.preventDefault();if(!groupId)return;await postForm(base+'/groups/leave',{user_id:me.id,group_id:groupId});groupId=null;lastId=0;chatArea.innerHTML='';chatTitle.textContent='Global Chat';chatHeaderAvatar.innerText='G';chatHeaderAvatar.style.backgroundColor='#6366f1';apiLeaveGroup.style.display='none';await loadUsers();};}
function openGroupCreateMobile(){
  const overlay=document.createElement('div');
  overlay.className='modal-overlay';
  overlay.innerHTML='<div class="modal" style="padding:.75rem"><h3 style="margin:0 0 .5rem 0;font-size:1rem;">New Group</h3><input id="gname" placeholder="Group name" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid var(--border-color);background:var(--bg-color);color:var(--text-color);"><div id="glist" class="modal-users" style="margin-top:.5rem;max-height:180px"></div><div class="modal-actions"><button class="btn-cancel" id="gcancel">Cancel</button><button class="btn-primary" id="gcreate">Create</button></div></div>';
  document.body.appendChild(overlay);
  getJson(base+'/users?user_id='+(me?me.id:'')).
    then(arr=>{const box=overlay.querySelector('#glist');let h='';(arr||[]).forEach(u=>{const safe=esc(u.username);h+='<label class="modal-user"><input type="checkbox" value="'+u.id+'"> <span>'+safe+'</span></label>';});box.innerHTML=h||'<div style="opacity:.7;">No other users</div>';});
  overlay.querySelector('#gcancel').onclick=()=>overlay.remove();
  overlay.querySelector('#gcreate').onclick=async()=>{
    const name=overlay.querySelector('#gname').value.trim();
    if(!name){overlay.querySelector('#gname').focus();return;}
    const ids=Array.from(overlay.querySelectorAll('input[type=checkbox]:checked')).map(el=>el.value).join(',');
    const res=await postForm(base+'/groups',{user_id:me.id,name:name,member_ids:ids});
    if(res.ok){overlay.remove();await loadUsers();const gid=res.json.group_id||res.json.id||0;selectGroup(gid,name);}else{overlay.remove();}
  };
}

// ─── Sidebar Tabs ──────────────────────────────────────
let currentTabMobile='all';
function switchTabMobile(tab){
  currentTabMobile=tab;
  document.getElementById('tabAllMobile').classList.toggle('active',tab==='all');
  document.getElementById('tabFriendsMobile').classList.toggle('active',tab==='friends');
  if(tab==='friends') loadFriendsTabMobile(); else loadUsers();
}
async function loadFriendsTabMobile(){
  if(!me) return;
  try{
    const arr=await getJson(base+'/friends/list?user_id='+me.id);
    if(!Array.isArray(arr)||arr.length===0){
      userList.innerHTML='<div style="padding:1rem;color:var(--text-secondary);text-align:center;">No friends yet.<br><small>Use the Add Friend button to find friends.</small></div>';
      return;
    }
    let html='';
    arr.forEach(f=>{
      const initial=f.username.charAt(0).toUpperCase();
      const safe=esc(f.username);
      const avContent=f.profile_pic?'<img src="'+f.profile_pic+'" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">':initial;
      html+='<div class="user-item" data-type="user" data-id="'+f.id+'" data-name="'+safe+'"><div class="user-avatar" style="background-color:var(--primary-color);overflow:hidden;">'+avContent+'</div><div class="user-info"><div class="user-top"><span class="user-name">'+safe+'</span></div><div class="user-bottom"><span class="last-message">'+esc(f.bio||'Friend')+'</span></div></div></div>';
    });
    userList.innerHTML=html;
    Array.from(userList.querySelectorAll('.user-item')).forEach(el=>{
      el.onclick=()=>selectContact(parseInt(el.getAttribute('data-id')),el.getAttribute('data-name'));
    });
  }catch(e){console.error('loadFriendsTab error',e);}
}
// Override loadUsers to respect current tab
const _origLoadUsers=loadUsers;
loadUsers=function(...args){
  if(currentTabMobile==='friends') return loadFriendsTabMobile();
  return _origLoadUsers(...args);
};

// ─── Add Friend Modal ──────────────────────────────────
const addFriendModalMobile=document.getElementById('addFriendModalMobile');
const btnAddFriendMobile=document.getElementById('btnAddFriendMobile');
const addFriendSearchMobile=document.getElementById('addFriendSearchMobile');
const addFriendResultsMobile=document.getElementById('addFriendResultsMobile');

if(btnAddFriendMobile){
  btnAddFriendMobile.onclick=()=>{
    addFriendModalMobile.style.display='flex';
    addFriendSearchMobile.value='';
    searchFriendCandidatesMobile('');
    setTimeout(()=>addFriendSearchMobile.focus(),80);
  };
}
if(addFriendModalMobile) addFriendModalMobile.onclick=e=>{if(e.target===addFriendModalMobile)addFriendModalMobile.style.display='none';};
if(addFriendSearchMobile) addFriendSearchMobile.oninput=()=>searchFriendCandidatesMobile(addFriendSearchMobile.value.trim());

async function searchFriendCandidatesMobile(query){
  if(!me) return;
  try{
    const url=base+'/users?user_id='+me.id+(query?'&search='+encodeURIComponent(query):'');
    const users=await getJson(url);
    if(!Array.isArray(users)||users.length===0){
      addFriendResultsMobile.innerHTML='<div style="padding:.75rem;color:var(--text-secondary);text-align:center;">No users found.</div>';
      return;
    }
    const statuses=await Promise.all(users.map(u=>getJson(base+'/friends/status?user_id='+me.id+'&friend_id='+u.id)));
    let html='';
    users.forEach((u,i)=>{
      const safe=esc(u.username);
      const st=statuses[i];
      const avHtml=u.profile_pic?'<img src="'+u.profile_pic+'" style="width:32px;height:32px;object-fit:cover;border-radius:50%;">':'<div class="user-avatar" style="width:32px;height:32px;background:var(--primary-color);font-size:.85rem;">'+safe.charAt(0).toUpperCase()+'</div>';
      let actionBtn='';
      if(st.status==='accepted') actionBtn='<button class="btn-friend-status friend-accepted" disabled>✓ Friends</button>';
      else if(st.status==='pending'&&st.direction==='sent') actionBtn='<button class="btn-friend-status friend-pending" onclick="cancelFriendReqMobile('+u.id+','+st.request_id+',this)">⏳ Pending</button>';
      else if(st.status==='pending'&&st.direction==='received') actionBtn='<button class="btn-friend-status btn-primary" onclick="acceptFriendFromSearchMobile('+st.request_id+',this)">Accept</button>';
      else actionBtn='<button class="btn-friend-status btn-add-friend" onclick="sendFriendReqMobile('+u.id+',this)">Add Friend</button>';
      html+='<div class="friend-candidate-row"><div style="display:flex;align-items:center;gap:.6rem;">'+avHtml+'<span class="user-name">'+safe+'</span></div>'+actionBtn+'</div>';
    });
    addFriendResultsMobile.innerHTML=html;
  }catch(e){console.error('searchFriendCandidates error',e);}
}
window.sendFriendReqMobile=async(friendId,btn)=>{
  const res=await postForm(base+'/friends/send',{user_id:me.id,friend_id:friendId});
  if(res.ok){btn.textContent='⏳ Pending';btn.className='btn-friend-status friend-pending';btn.onclick=null;}
  else alert(res.json.error||'Could not send request');
};
window.cancelFriendReqMobile=async(friendId,requestId,btn)=>{
  await postForm(base+'/friends/cancel',{user_id:me.id,request_id:requestId});
  btn.textContent='Add Friend';btn.className='btn-friend-status btn-add-friend';btn.onclick=()=>sendFriendReqMobile(friendId,btn);
};
window.acceptFriendFromSearchMobile=async(requestId,btn)=>{
  const res=await postForm(base+'/friends/accept',{user_id:me.id,request_id:requestId});
  if(res.ok){btn.textContent='✓ Friends';btn.className='btn-friend-status friend-accepted';btn.disabled=true;pollFriendReqMobile();}
};

// ─── Friend Requests Modal ─────────────────────────────
const friendReqModalMobile=document.getElementById('friendReqModalMobile');
const btnFriendRequestsMobile=document.getElementById('btnFriendRequestsMobile');
const friendReqBadgeMobile=document.getElementById('friendReqBadgeMobile');

if(btnFriendRequestsMobile){
  btnFriendRequestsMobile.onclick=()=>{
    friendReqModalMobile.style.display='flex';
    loadFriendRequestsMobile();
    loadFriendsInModalMobile();
  };
}
if(friendReqModalMobile) friendReqModalMobile.onclick=e=>{if(e.target===friendReqModalMobile)friendReqModalMobile.style.display='none';};

async function loadFriendRequestsMobile(){
  if(!me) return;
  try{
    const arr=await getJson(base+'/friends/pending?user_id='+me.id);
    const list=document.getElementById('friendReqListMobile');
    if(!Array.isArray(arr)||arr.length===0){
      list.innerHTML='<div style="padding:.75rem;color:var(--text-secondary);text-align:center;">No pending requests 🎉</div>';
      updateFriendBadgeMobile(0);return;
    }
    updateFriendBadgeMobile(arr.length);
    let html='';
    arr.forEach(r=>{
      const safe=esc(r.username);
      const avHtml=r.profile_pic?'<img src="'+r.profile_pic+'" style="width:36px;height:36px;object-fit:cover;border-radius:50%;">':'<div class="user-avatar" style="width:36px;height:36px;font-size:.9rem;">'+safe.charAt(0).toUpperCase()+'</div>';
      html+='<div class="friend-req-row" id="freq-m-'+r.request_id+'"><div style="display:flex;align-items:center;gap:.6rem;">'+avHtml+'<span class="user-name">'+safe+'</span></div><div style="display:flex;gap:.4rem;"><button class="btn-primary btn-sm" onclick="acceptReqMobile('+r.request_id+')">Accept</button><button class="btn-cancel btn-sm" onclick="rejectReqMobile('+r.request_id+')">Reject</button></div></div>';
    });
    list.innerHTML=html;
  }catch(e){console.error('loadFriendRequests error',e);}
}
window.acceptReqMobile=async(reqId)=>{
  const res=await postForm(base+'/friends/accept',{user_id:me.id,request_id:reqId});
  if(res.ok){const row=document.getElementById('freq-m-'+reqId);if(row)row.remove();pollFriendReqMobile();loadFriendsInModalMobile();if(currentTabMobile==='friends')loadFriendsTabMobile();}
};
window.rejectReqMobile=async(reqId)=>{
  await postForm(base+'/friends/reject',{user_id:me.id,request_id:reqId});
  const row=document.getElementById('freq-m-'+reqId);if(row)row.remove();pollFriendReqMobile();
};

async function loadFriendsInModalMobile(){
  if(!me) return;
  try{
    const arr=await getJson(base+'/friends/list?user_id='+me.id);
    const list=document.getElementById('friendModalListMobile');
    if(!Array.isArray(arr)||arr.length===0){
      list.innerHTML='<div style="padding:.75rem;color:var(--text-secondary);text-align:center;">No friends yet.</div>';
      return;
    }
    let html='';
    arr.forEach(f=>{
      const safe=esc(f.username);
      const avHtml=f.profile_pic?'<img src="'+f.profile_pic+'" style="width:36px;height:36px;object-fit:cover;border-radius:50%;">':'<div class="user-avatar" style="width:36px;height:36px;font-size:.9rem;">'+safe.charAt(0).toUpperCase()+'</div>';
      html+='<div class="friend-req-row" id="fmod-m-'+f.id+'"><div style="display:flex;align-items:center;gap:.6rem;cursor:pointer;" onclick="friendReqModalMobile.style.display=\'none\';selectContact('+f.id+',\''+safe+'\')">'+avHtml+'<div><div class="user-name">'+safe+'</div><div style="font-size:0.75rem;color:var(--text-secondary);">'+esc(f.bio||'Friend')+'</div></div></div><div style="display:flex;gap:.4rem;"><button class="btn-cancel btn-sm" onclick="unfriendMobile('+f.id+',\''+safe+'\')">✕</button></div></div>';
    });
    list.innerHTML=html;
  }catch(e){console.error('loadFriendsInModal error',e);}
}
window.unfriendMobile=async(friendId,username)=>{
  if(!confirm('Unfriend '+username+'?'))return;
  await postForm(base+'/friends/unfriend',{user_id:me.id,friend_id:friendId});
  loadFriendsInModalMobile();
  if(currentTabMobile==='friends')loadFriendsTabMobile();
  loadUsers();
};

function updateFriendBadgeMobile(count){
  if(!friendReqBadgeMobile) return;
  if(count>0){friendReqBadgeMobile.textContent=count>9?'9+':count;friendReqBadgeMobile.style.display='flex';}
  else friendReqBadgeMobile.style.display='none';
}
async function pollFriendReqMobile(){
  if(!me) return;
  try{const r=await getJson(base+'/friends/count?user_id='+me.id);updateFriendBadgeMobile(r.count||0);}catch(e){}
}
setInterval(pollFriendReqMobile,10000);
setTimeout(()=>pollFriendReqMobile(),1500);

// ─── File Upload Handler ───────────────────────────────
window.handleFileUpload=(input)=>{
  if(input.files&&input.files[0]){
    const file=input.files[0];
    const fd=new FormData();
    fd.append('attachment',file);
    fd.append('message','');
    if(groupId)fd.append('group_id',groupId);
    else if(contactId)fd.append('receiver_id',contactId);
    fetch('send_message.php',{method:'POST',body:fd}).then(()=>loadMessages());
    input.value='';
  }
};
</script>
</body>
</html>
<?php
    exit;
}
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distributed Chat System</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="app-container">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-header">
            <!-- Clickable avatar + name opens profile modal -->
            <div id="myProfileBtn" style="display:flex;align-items:center;gap:0.75rem;cursor:pointer;" title="View my profile">
                <div class="user-avatar" id="myAvatarEl" style="background-color:var(--primary-color);overflow:hidden;">
                    <?php
                    $stmt = $pdo->prepare("SELECT profile_pic, bio, created_at FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $myProfile = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!empty($myProfile['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($myProfile['profile_pic']); ?>?v=<?php echo time(); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <span id="myUsernameBadge" style="font-weight:600;color:var(--text-color);"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <div style="display:flex;gap:.5rem;align-items:center;">
                <!-- Friend requests button with badge -->
                <button id="btnFriendRequests" class="btn-icon" title="Friend Requests" style="position:relative;">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                    <span id="friendReqBadge" style="display:none;position:absolute;top:-2px;right:-2px;background:#ef4444;color:#fff;border-radius:50%;width:16px;height:16px;font-size:0.6rem;align-items:center;justify-content:center;font-weight:bold;box-shadow: 0 0 0 2px var(--sidebar-bg);">0</span>
                </button>
                <!-- Add Friend button -->
                <button id="btnAddFriend" class="btn-icon" title="Add Friend">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><line x1="19" y1="8" x2="19" y2="14"></line><line x1="16" y1="11" x2="22" y2="11"></line></svg>
                </button>
                <button id="btnCreateGroup" class="btn-icon" title="New Group / Channel">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </button>
                <button id="btnSettings" class="btn-icon" title="Settings">
                    <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                </button>
            </div>
        </div>

        <!-- Sidebar Tabs: All Chats / Friends -->
        <div class="sidebar-tabs">
            <button class="sidebar-tab active" id="tabAll" onclick="switchTab('all')">All Chats</button>
            <button class="sidebar-tab" id="tabFriends" onclick="switchTab('friends')">Friends</button>
        </div>

        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search or start new chat">
        </div>

        <div class="user-list" id="userList"></div>
    </div>

    <!-- CHAT AREA -->
    <div class="chat-area">
        <div class="chat-header">
            <div class="user-avatar" id="chatHeaderAvatar" style="background-color:#ccc;margin-right:1rem;visibility:hidden;overflow:hidden;">?</div>
            <div style="display:flex;flex-direction:column;">
                <h2 id="chatTitle" style="margin:0;font-size:1.1rem;font-weight:500;">Select a chat to start messaging</h2>
                <span id="chatStatus" style="font-size:0.8rem;color:var(--text-secondary);visibility:hidden;">online</span>
            </div>
            <div style="margin-left:auto; display:flex; gap:0.5rem; align-items:center;">
                <button id="btnChannelSettings" class="btn-icon" title="Channel Settings" style="display:none;" onclick="openChannelSettings()">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                </button>
            </div>
        </div>
        <div class="chat-messages" id="chatArea"></div>
        <form id="chatForm" class="chat-input-area" onsubmit="event.preventDefault();sendMessage();" style="display:none;">
            <input type="file" id="fileInput" style="display:none;" onchange="handleFileUpload(this)">
            <button type="button" class="btn-icon" onclick="document.getElementById('fileInput').click()" style="padding:0; min-width:32px; height:32px;">📎</button>
            <input type="text" id="messageInput" placeholder="Type a message" autocomplete="off">
            <button type="submit" class="btn-send">➤</button>
        </form>
    </div>
</div>

<!-- ===== PROFILE MODAL ===== -->
<div class="modal-overlay" id="profileModal" style="display:none;">
  <div class="modal profile-modal">
    <button class="modal-close" onclick="document.getElementById('profileModal').style.display='none'">✕</button>

    <!-- Cover Banner -->
    <div class="profile-cover"></div>

    <!-- Avatar section -->
    <div class="profile-avatar-wrap">
      <div class="profile-avatar-ring" id="profileAvatarRing">
        <div class="user-avatar profile-avatar-lg" id="profileAvatarEl" style="background-color:var(--primary-color);overflow:hidden;">
          <?php if (!empty($myProfile['profile_pic'])): ?>
            <img id="profileAvatarImg" src="<?php echo htmlspecialchars($myProfile['profile_pic']); ?>?v=<?php echo time(); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
          <?php else: ?>
            <span id="profileAvatarInitial"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
            <img id="profileAvatarImg" src="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:none;">
          <?php endif; ?>
        </div>
        <label class="avatar-upload-btn" title="Change photo">
          📷
          <input type="file" id="avatarFileInput" accept="image/*" style="display:none;">
        </label>
      </div>
    </div>

    <!-- Profile info -->
    <div class="profile-info">
      <h2 class="profile-username"><?php echo htmlspecialchars($_SESSION['username']); ?></h2>
      <p class="profile-member">Member since <?php echo date('F Y', strtotime($myProfile['created_at'] ?? 'now')); ?></p>

      <!-- Bio display -->
      <div id="bioDisplay" class="profile-bio-area">
        <p id="bioText" class="profile-bio"><?php echo !empty($myProfile['bio']) ? htmlspecialchars($myProfile['bio']) : '<em style="color:var(--text-secondary)">No bio yet. Click Edit to add one.</em>'; ?></p>
        <button class="btn-primary" onclick="startEditBio()" style="margin-top:.5rem;">✏️ Edit Bio</button>
      </div>

      <!-- Bio edit form -->
      <div id="bioEdit" style="display:none;" class="profile-bio-area">
        <textarea id="bioInput" maxlength="300" placeholder="Write something about yourself..." class="bio-textarea"><?php echo htmlspecialchars($myProfile['bio'] ?? ''); ?></textarea>
        <div style="display:flex;gap:.5rem;margin-top:.5rem;">
          <button class="btn-primary" onclick="saveBio()">💾 Save</button>
          <button class="btn-cancel" onclick="cancelEditBio()">Cancel</button>
        </div>
        <p id="bioSaveMsg" style="font-size:.8rem;color:#4ade80;margin-top:.3rem;display:none;">Saved!</p>
      </div>
    </div>
  </div>
</div>

<!-- ===== ADD FRIEND MODAL ===== -->
<div class="modal-overlay" id="addFriendModal" style="display:none;">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
      <h3 style="margin:0;">Add a Friend</h3>
      <button class="modal-close" onclick="document.getElementById('addFriendModal').style.display='none'">✕</button>
    </div>
    <input type="text" id="addFriendSearch" placeholder="Search by username..." style="width:100%;padding:.6rem .8rem;border:1px solid var(--border-color);border-radius:8px;background:var(--bg-color);color:var(--text-color);margin-bottom:.75rem;">
    <div id="addFriendResults" class="modal-users" style="max-height:280px;"></div>
  </div>
</div>

<!-- ===== FRIEND REQUESTS MODAL ===== -->
<div class="modal-overlay" id="friendReqModal" style="display:none;">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
      <h3 style="margin:0;">Friend Requests</h3>
      <button class="modal-close" onclick="document.getElementById('friendReqModal').style.display='none'">✕</button>
    </div>
    <div id="friendReqList" class="modal-users" style="max-height:240px;"></div>
    <hr style="border:0; border-top:1px solid var(--border-color); margin: 1rem 0;">
    <h3 style="margin:0 0 .75rem 0;">Your Friends</h3>
    <div id="friendModalList" class="modal-users" style="max-height:240px;"></div>
  </div>
</div>

<!-- ===== CHOICE MODAL (Group or Channel) ===== -->
<div class="modal-overlay" id="choiceModal" style="display:none;">
  <div class="modal" style="max-width:360px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
      <h3 style="margin:0;">Create New</h3>
      <button class="modal-close" onclick="document.getElementById('choiceModal').style.display='none'">✕</button>
    </div>
    <div style="display:flex;flex-direction:column;gap:1rem;">
      <button class="choice-btn" onclick="openCreateGroupModal()">
        <div class="choice-icon" style="background: linear-gradient(135deg, rgba(0, 242, 254, 0.15) 0%, rgba(79, 172, 254, 0.15) 100%); color: #00f2fe; border: 1px solid rgba(0, 242, 254, 0.3); box-shadow: 0 4px 15px rgba(0, 242, 254, 0.2);">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="28" height="28">
            <rect width="256" height="256" fill="none"/>
            <circle cx="88" cy="108" r="52" fill="currentColor" opacity="0.2"/>
            <path d="M155.4,146A52.2,52.2,0,0,1,168,108a52,52,0,0,0-52-52H115a51.6,51.6,0,0,1,11,11A52,52,0,0,1,168,108a52.2,52.2,0,0,1-12.6,38" fill="currentColor" opacity="0.2"/>
            <path d="M144.3,196.7A95.5,95.5,0,0,1,208,216a8,8,0,0,0,0-16,80.1,80.1,0,0,0-53-20.2" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/>
            <circle cx="88" cy="108" r="52" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/>
            <path d="M155.4,146A52.2,52.2,0,0,1,168,108a52,52,0,0,0-52-52" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/>
            <path d="M16,216a72,72,0,0,1,144,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/>
          </svg>
        </div>
        <div class="choice-text">
          <strong>Private Group</strong>
          <p>Chat with specific friends</p>
        </div>
      </button>
      <button class="choice-btn" onclick="openCreateChannelModal()">
        <div class="choice-icon" style="background: linear-gradient(135deg, rgba(240, 147, 251, 0.15) 0%, rgba(245, 87, 108, 0.15) 100%); color: #f093fb; border: 1px solid rgba(240, 147, 251, 0.3); box-shadow: 0 4px 15px rgba(240, 147, 251, 0.2);">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 256" width="28" height="28">
            <rect width="256" height="256" fill="none"/>
            <polygon points="216 144 72 176 72 48 216 80 216 144" fill="currentColor" opacity="0.2"/>
            <path d="M72,176,32,224a8,8,0,0,1-12.3-10.2L55.5,168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/>
            <polygon points="216 144 72 176 72 48 216 80 216 144" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/>
            <path d="M216,144v24a8,8,0,0,1-8,8H184a8,8,0,0,1-8-8V151.1" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/>
            <path d="M72,48A40,40,0,0,0,32,88v24a40,40,0,0,0,40,40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/>
          </svg>
        </div>
        <div class="choice-text">
          <strong>Channel</strong>
          <p>Public or private broadcast</p>
        </div>
      </button>
    </div>
  </div>
</div>

<!-- ===== CREATE PRIVATE GROUP MODAL ===== -->
<div class="modal-overlay" id="createGroupModal" style="display:none;">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
      <h3 style="margin:0;">Create Private Group</h3>
      <button class="modal-close" onclick="closeCreateGroupModal()">✕</button>
    </div>
    <div class="form-group">
      <label>Group Name</label>
      <input type="text" id="grpName" placeholder="e.g. Weekend Plans">
    </div>
    <div class="form-group">
      <label>Select Members</label>
      <div id="grpUsers" class="modal-users" style="max-height:200px;"></div>
    </div>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeCreateGroupModal()">Cancel</button>
      <button class="btn-primary" id="grpCreate">Create Group</button>
    </div>
  </div>
</div>

<!-- ===== CREATE CHANNEL MODAL ===== -->
<div class="modal-overlay" id="createChannelModal" style="display:none;">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
      <h3 style="margin:0;">Create Channel</h3>
      <button class="modal-close" onclick="closeCreateChannelModal()">✕</button>
    </div>
    
    <div style="display:flex;gap:1rem;margin-bottom:1rem;">
      <div class="profile-avatar-ring">
        <div class="user-avatar" id="channelAvatarPreview" style="width:64px;height:64px;background:var(--primary-color);overflow:hidden;font-size:1.5rem;">C</div>
        <label class="avatar-upload-btn" title="Channel photo" style="width:20px;height:20px;font-size:0.6rem;bottom:0;right:0;">
          📷
          <input type="file" id="channelAvatarInput" accept="image/*" style="display:none;">
        </label>
      </div>
      <div style="flex:1;">
        <div class="form-group" style="margin:0;">
          <label>Channel Name</label>
          <input type="text" id="chanName" placeholder="e.g. Tech News">
        </div>
      </div>
    </div>

    <div class="form-group">
      <label>Description (optional)</label>
      <textarea id="chanDesc" class="bio-textarea" style="min-height:60px;" placeholder="What is this channel about?"></textarea>
    </div>

    <div class="form-group">
      <label>Visibility</label>
      <div style="display:flex;gap:1.5rem;margin-top:0.5rem;">
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
          <input type="radio" name="chanType" value="public" checked> 
          <span><strong>Public</strong> (Searchable)</span>
        </label>
        <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
          <input type="radio" name="chanType" value="private"> 
          <span><strong>Private</strong> (Via Link)</span>
        </label>
      </div>
    </div>

    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeCreateChannelModal()">Cancel</button>
      <button class="btn-primary" id="chanCreate">Create Channel</button>
    </div>
  </div>
</div>

<!-- ===== JOIN LINK MODAL ===== -->
<div class="modal-overlay" id="joinLinkModal" style="display:none;">
  <div class="modal" style="max-width:400px;text-align:center;">
    <div class="choice-icon" style="font-size:3rem;margin-bottom:0.5rem;">🔗</div>
    <h3 id="joinLinkTitle">Invite Link Generated</h3>
    <p style="font-size:0.9rem;color:var(--text-secondary);margin-bottom:1rem;">Share this link with others so they can join your channel/group.</p>
    <div style="display:flex;gap:0.5rem;background:var(--bg-color);padding:0.75rem;border-radius:0.5rem;border:1px solid var(--border-color);margin-bottom:1.5rem;">
      <input type="text" id="joinLinkInput" readonly style="flex:1;background:transparent;border:none;color:var(--text-color);font-size:0.85rem;outline:none;">
      <button class="btn-primary btn-sm" onclick="copyJoinLink()">Copy</button>
    </div>
    <button class="btn-primary" style="width:100%;" onclick="document.getElementById('joinLinkModal').style.display='none'">Done</button>
  </div>
</div>

    </div>
</div>

<!-- ===== SETTINGS MODAL ===== -->
<div class="modal-overlay" id="settingsModal" style="display:none;">
    <div class="modal" style="max-width:350px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
            <h3 style="margin:0;">Settings</h3>
            <button class="modal-close" onclick="document.getElementById('settingsModal').style.display='none'">✕</button>
        </div>
        <div class="form-group" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;background:rgba(255,255,255,0.03);padding:1rem;border-radius:12px;border:1px solid var(--border-color);">
            <div>
                <strong style="display:block;margin-bottom:0.25rem;">Private Account</strong>
                <p style="font-size:0.75rem;color:var(--text-secondary);margin:0;">Only friends can message you</p>
            </div>
            <label class="switch">
                <input type="checkbox" id="privacyToggle">
                <span class="slider round"></span>
            </label>
        </div>
        <hr style="border:0; border-top:1px solid var(--border-color); margin: 1rem 0;">
        <button id="btnSettingsLogout" class="auth-btn" style="width:100%;margin:0;background-color:rgba(239, 68, 68, 0.1);color:#ef4444;border:1px solid rgba(239, 68, 68, 0.2);">
            Logout
        </button>
    </div>
</div>

<!-- ===== CHANNEL SETTINGS MODAL ===== -->
<div class="modal-overlay" id="channelSettingsModal" style="display:none;">
    <div class="modal" style="max-width: 480px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0;">Channel Settings</h3>
            <button class="btn-icon" onclick="closeChannelSettings()" style="width:32px;height:32px;border-radius:50%;">✕</button>
        </div>
        <div id="channelSettingsContent" style="max-height: 75vh; overflow-y: auto;">
            <div style="text-align:center; padding:2rem; color:var(--text-secondary);">Loading settings...</div>
        </div>
    </div>
</div>

<!-- ===== ADD MEMBER MODAL ===== -->
<div class="modal-overlay" id="addMemberModal" style="display:none; z-index: 1100;">
    <div class="modal" style="max-width: 400px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h3 style="margin:0;">Add Member</h3>
            <button class="btn-icon" onclick="document.getElementById('addMemberModal').style.display='none'" style="width:32px;height:32px;border-radius:50%;">✕</button>
        </div>
        <div class="form-group" style="margin-bottom:1rem;">
            <input type="text" id="addMemberSearch" placeholder="Search friends or users..." style="width:100%;" autocomplete="off">
        </div>
        <div id="addMemberResults" class="modal-users" style="max-height: 350px;">
            <!-- Dynamic Results -->
        </div>
    </div>
</div>

<!-- ===== USER PROFILE MODAL (View Others) ===== -->
<div class="modal-overlay" id="userProfileModal" style="display:none;">
    <div class="modal" style="max-width: 400px; text-align: center; position: relative;">
        <button class="modal-close" onclick="document.getElementById('userProfileModal').style.display='none'">✕</button>
        <div class="profile-cover" style="height: 100px; margin: -1.5rem -1.5rem 0 -1.5rem; border-radius: 12px 12px 0 0; background: linear-gradient(135deg, var(--primary-color), #4f46e5);"></div>
        
        <div style="margin-top: -40px; position: relative; z-index: 1;">
            <div class="user-avatar" id="uProfAvatar" style="width:80px; height:80px; font-size:2rem; border:4px solid var(--sidebar-bg); margin: 0 auto; overflow:hidden; background: var(--primary-color);"></div>
        </div>
        
        <h2 id="uProfUsername" style="margin: 0.75rem 0 0.25rem 0;"></h2>
        <p id="uProfJoinDate" style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 1rem;"></p>
        
        <div style="display: flex; justify-content: center; gap: 2rem; margin-bottom: 1.5rem; padding: 0.75rem; background: rgba(255,255,255,0.03); border-radius: 10px;">
            <div style="text-align: center;">
                <div id="uProfFriendCount" style="font-weight: 600; font-size: 1.1rem;">0</div>
                <div style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Friends</div>
            </div>
        </div>
        
        <div class="form-group" style="text-align: left;">
            <label>Bio</label>
            <p id="uProfBio" style="font-size: 0.9rem; min-height: 40px; line-height: 1.4; color: var(--text-color);"></p>
        </div>
        
        <div id="uProfActions" style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <!-- Dynamic Actions -->
        </div>
    </div>
</div>

<script>
    window.currentUserId = <?php echo $_SESSION['user_id']; ?>;
</script>
<script src="script.js?v=<?php echo time(); ?>"></script>
<script src="friends_ui.js?v=<?php echo time(); ?>"></script>
<script src="groups_ui.js?v=<?php echo time(); ?>"></script>
</body>
</html>
