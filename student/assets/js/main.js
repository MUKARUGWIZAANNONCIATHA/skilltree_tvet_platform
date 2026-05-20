const aiFab = document.getElementById('aiTutorFab');
const aiChat = document.getElementById('aiChatWindow');
const closeChat = document.getElementById('closeChat');
const sendBtn = document.getElementById('sendChat');
const chatInput = document.getElementById('chatInput');
const chatMessages = document.getElementById('chatMessages');

if (aiFab) aiFab.onclick = () => aiChat.classList.toggle('hidden');
if (closeChat) closeChat.onclick = () => aiChat.classList.add('hidden');

function addMessage(sender, text) {
    const div = document.createElement('div');
    div.className = `message ${sender}`;
    div.textContent = text;
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

sendBtn?.addEventListener('click', async () => {
    const msg = chatInput.value.trim();
    if (!msg) return;
    addMessage('user', msg);
    chatInput.value = '';
    const resp = await fetch('/student/ai-tutor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'message=' + encodeURIComponent(msg)
    });
    const data = await resp.json();
    addMessage('ai', data.reply);
});