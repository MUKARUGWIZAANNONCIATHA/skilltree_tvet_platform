    </main>

    <!-- AI Tutor FAB (Floating Action Button) -->
    <div class="ai-tutor-fab" id="aiTutorFab">
        <i class="fas fa-robot"></i>
    </div>

    <!-- AI Chat Window -->
    <div id="aiChatWindow" class="ai-chat-window hidden">
        <div class="chat-header">
            🤖 AI Learning Assistant
            <span id="closeChat">&times;</span>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="message ai">Hello! I'm your AI tutor. Ask me anything about your courses.</div>
        </div>
        <div class="chat-input">
            <input type="text" id="chatInput" placeholder="Ask a question...">
            <button id="sendChat">Send</button>
        </div>
    </div>

    <style>
        /* AI Tutor styles */
        .ai-tutor-fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: linear-gradient(135deg, #1e6a8c, #0e4b65);
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            color: white;
            font-size: 1.8rem;
            transition: transform 0.2s;
            z-index: 200;
        }
        .ai-tutor-fab:hover {
            transform: scale(1.05);
        }
        .ai-chat-window {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 340px;
            background: white;
            border-radius: 1.2rem;
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            z-index: 210;
            overflow: hidden;
            transition: 0.2s;
        }
        .ai-chat-window.hidden {
            display: none;
        }
        .chat-header {
            background: #1e6a8c;
            padding: 0.8rem 1rem;
            color: white;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .chat-messages {
            height: 280px;
            overflow-y: auto;
            padding: 0.8rem;
            background: #fefefe;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .message {
            padding: 0.5rem 0.8rem;
            border-radius: 1.2rem;
            max-width: 85%;
            font-size: 0.85rem;
        }
        .message.user {
            background: #eef2fa;
            align-self: flex-end;
            color: #1e2f3e;
        }
        .message.ai {
            background: #e3f0f8;
            align-self: flex-start;
            color: #1a5f7a;
        }
        .chat-input {
            display: flex;
            border-top: 1px solid #e2e8f0;
            padding: 0.6rem;
            gap: 0.5rem;
            background: white;
        }
        .chat-input input {
            flex: 1;
            border: 1px solid #ccc;
            border-radius: 2rem;
            padding: 0.5rem 1rem;
            font-family: inherit;
            outline: none;
        }
        .chat-input button {
            background: #1e6a8c;
            border: none;
            border-radius: 2rem;
            padding: 0.5rem 1rem;
            color: white;
            cursor: pointer;
        }
        @media (max-width: 550px) {
            .ai-chat-window {
                width: 280px;
                right: 10px;
                bottom: 70px;
            }
        }
    </style>

    <script>
        // AI Tutor functionality
        const aiFab = document.getElementById('aiTutorFab');
        const aiChat = document.getElementById('aiChatWindow');
        const closeChat = document.getElementById('closeChat');
        const sendBtn = document.getElementById('sendChat');
        const chatInput = document.getElementById('chatInput');
        const chatMessagesDiv = document.getElementById('chatMessages');

        if (aiFab) {
            aiFab.onclick = () => aiChat.classList.toggle('hidden');
        }
        if (closeChat) {
            closeChat.onclick = () => aiChat.classList.add('hidden');
        }

        function addMessage(sender, text) {
            const msgDiv = document.createElement('div');
            msgDiv.className = `message ${sender}`;
            msgDiv.textContent = text;
            chatMessagesDiv.appendChild(msgDiv);
            chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
        }

        if (sendBtn) {
            sendBtn.onclick = async () => {
                const msg = chatInput.value.trim();
                if (!msg) return;
                addMessage('user', msg);
                chatInput.value = '';
                try {
                    const response = await fetch('/student/ai-tutor.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'message=' + encodeURIComponent(msg)
                    });
                    const data = await response.json();
                    addMessage('ai', data.reply || "I'm here to help. Please rephrase or check your module resources.");
                } catch (err) {
                    addMessage('ai', "Sorry, I'm having trouble right now. Please try again later.");
                }
            };
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendBtn.click();
            });
        }
    </script>
</body>
</html>