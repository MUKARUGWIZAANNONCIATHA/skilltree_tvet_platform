<?php
/**
 * Student AI Assistant Chat Widget
 * Embed this in student dashboard or any protected page.
 */
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .ai-chat-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            font-family: 'Segoe UI', system-ui;
        }
        .chat-toggle {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: 0.3s;
        }
        .chat-toggle:hover {
            transform: scale(1.05);
        }
        .chat-box {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: none;
            flex-direction: column;
            overflow: hidden;
            max-height: 500px;
        }
        .chat-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-messages {
            height: 350px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .message {
            max-width: 80%;
            padding: 8px 12px;
            border-radius: 15px;
            font-size: 13px;
            line-height: 1.4;
        }
        .message.user {
            background: #667eea;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }
        .message.bot {
            background: #e9ecef;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }
        .chat-input {
            display: flex;
            border-top: 1px solid #ddd;
            padding: 10px;
            background: white;
        }
        .chat-input input {
            flex: 1;
            border: none;
            outline: none;
            padding: 8px;
            font-size: 14px;
        }
        .chat-input button {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
        }
        .typing {
            font-style: italic;
            color: #888;
            font-size: 12px;
            margin: 5px 0;
        }
        @media (max-width: 500px) {
            .chat-box { width: 300px; right: -10px; }
        }
    </style>
</head>
<body>
<div class="ai-chat-widget">
    <div class="chat-toggle" onclick="toggleChat()">
        <i class="fas fa-robot" style="font-size: 28px;"></i>
    </div>
    <div class="chat-box" id="chatBox">
        <div class="chat-header">
            <span><i class="fas fa-chalkboard-teacher"></i> AI Learning Assistant</span>
            <span style="cursor:pointer" onclick="toggleChat()">✕</span>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="message bot">👋 Hi! I'm your AI tutor. Ask me any concept you find difficult. I'll explain and give resources—without giving exam answers.</div>
        </div>
        <div class="chat-input">
            <input type="text" id="chatInput" placeholder="Type your question..." onkeypress="if(event.key==='Enter') sendMessage()">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>
</div>

<script>
function toggleChat() {
    const box = document.getElementById('chatBox');
    box.style.display = box.style.display === 'flex' ? 'none' : 'flex';
}

async function sendMessage() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if (!msg) return;
    
    // Add user message
    const messagesDiv = document.getElementById('chatMessages');
    const userMsgDiv = document.createElement('div');
    userMsgDiv.className = 'message user';
    userMsgDiv.innerText = msg;
    messagesDiv.appendChild(userMsgDiv);
    input.value = '';
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    
    // Show typing indicator
    const typingDiv = document.createElement('div');
    typingDiv.className = 'typing';
    typingDiv.innerText = 'Assistant is typing...';
    messagesDiv.appendChild(typingDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    
    try {
        const formData = new FormData();
        formData.append('message', msg);
        const response = await fetch('/student/ai-assistant.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        typingDiv.remove();
        
        const botMsgDiv = document.createElement('div');
        botMsgDiv.className = 'message bot';
        botMsgDiv.innerHTML = data.reply; // contains nl2br formatting
        messagesDiv.appendChild(botMsgDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    } catch (err) {
        typingDiv.remove();
        const errDiv = document.createElement('div');
        errDiv.className = 'message bot';
        errDiv.innerText = 'Sorry, I had a problem. Please try again later.';
        messagesDiv.appendChild(errDiv);
    }
}
</script>
<!-- Include FontAwesome if not already present -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</body>
</html>