<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Teacher Portal</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: rgba(26, 31, 58, 0.95);
            backdrop-filter: blur(20px);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .navbar h1 {
            color: white;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .chat-container {
            display: grid;
            grid-template-columns: 380px 1fr;
            height: calc(100vh - 84px);
            max-width: 1600px;
            margin: 20px auto;
            gap: 20px;
            padding: 0 20px;
        }

        .contacts-panel {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .contacts-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            font-size: 20px;
            font-weight: 700;
        }

        .contacts-search {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .contacts-search input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }

        .contacts-search input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .contacts-list {
            overflow-y: auto;
            height: calc(100vh - 240px);
        }

        .contacts-list::-webkit-scrollbar {
            width: 6px;
        }

        .contacts-list::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .contacts-list::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 3px;
        }

        .contact-item {
            padding: 18px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .contact-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .contact-item.active {
            background: rgba(102, 126, 234, 0.1);
            border-left: 4px solid #667eea;
        }

        .contact-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745, #20c997);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .contact-info {
            flex: 1;
            min-width: 0;
        }

        .contact-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .contact-subtitle {
            font-size: 12px;
            color: #666;
            margin-bottom: 2px;
        }

        .contact-meta {
            font-size: 11px;
            color: #999;
            margin-top: 2px;
        }

        .contact-last-message {
            font-size: 13px;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 3px;
        }

        .unread-badge {
            background: #ff6b6b;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .chat-panel {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
        }

        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #28a745;
            border-radius: 3px;
        }

        .message {
            display: flex;
            margin-bottom: 20px;
            animation: messageSlide 0.3s ease;
        }

        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 60%;
            padding: 12px 18px;
            border-radius: 15px;
            font-size: 14px;
            line-height: 1.6;
            word-wrap: break-word;
        }

        .message.received .message-content {
            background: rgba(0, 0, 0, 0.05);
            color: #333;
            border-bottom-left-radius: 5px;
        }

        .message.sent .message-content {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-bottom-right-radius: 5px;
        }

        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 5px;
            text-align: right;
        }

        .chat-input-container {
            padding: 20px 30px;
            background: white;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            padding: 12px 20px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }

        .chat-input:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .send-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
        }

        .send-btn:active {
            transform: scale(0.95);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            font-size: 18px;
        }

        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 15px;
            margin: 15px;
            border-radius: 10px;
            border-left: 4px solid #c33;
        }

        @media (max-width: 768px) {
            .chat-container {
                grid-template-columns: 1fr;
            }
            
            .navbar {
                padding: 15px 20px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>💬 Messages</h1>
        <div>
            <a href="index.php" class="btn btn-primary">🏠 Dashboard</a>
        </div>
    </nav>

    <div class="chat-container">
        <!-- Contacts Panel -->
        <div class="contacts-panel">
            <div class="contacts-header">
                👥 Students (<span id="studentCount">0</span>)
            </div>
            <div class="contacts-search">
                <input type="text" id="searchContacts" placeholder="🔍 Search students...">
            </div>
            <div class="contacts-list" id="contactsList">
                <div class="loading">
                    <div style="font-size: 40px; margin-bottom: 10px;">⏳</div>
                    <p>Loading students...</p>
                </div>
            </div>
        </div>

        <!-- Chat Panel -->
        <div class="chat-panel">
            <div id="emptyChatState" class="empty-state">
                <div class="empty-state-icon">💬</div>
                <p>Select a student to start messaging</p>
            </div>
            
            <div id="activeChatContainer" style="display: none; height: 100%; flex-direction: column;">
                <div class="chat-header">
                    <div class="chat-header-info">
                        <div class="contact-avatar" id="chatHeaderAvatar">S</div>
                        <div>
                            <div style="font-size: 18px; font-weight: 700;" id="chatHeaderName">Student Name</div>
                            <div style="font-size: 13px; opacity: 0.9;" id="chatHeaderSubtitle">Class Info</div>
                        </div>
                    </div>
                </div>
                <div class="chat-messages" id="chatMessages"></div>
                <div class="chat-input-container">
                    <input type="text" class="chat-input" id="messageInput" placeholder="Type your message...">
                    <button class="send-btn" id="sendBtn">➤</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentContact = null;
        let contacts = [];
        let messageCheckInterval = null;

        function debugLog(message, data = null) {
            console.log(`[CHAT DEBUG] ${message}`, data || '');
        }

        debugLog('Page loaded, starting to load contacts...');
        loadContacts();

        document.getElementById('searchContacts').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const contactItems = document.querySelectorAll('.contact-item');
            
            contactItems.forEach(item => {
                const name = item.querySelector('.contact-name span').textContent.toLowerCase();
                const subtitle = item.querySelector('.contact-subtitle').textContent.toLowerCase();
                const meta = item.querySelector('.contact-meta')?.textContent.toLowerCase() || '';
                
                const matches = name.includes(searchTerm) || 
                              subtitle.includes(searchTerm) || 
                              meta.includes(searchTerm);
                              
                item.style.display = matches ? 'flex' : 'none';
            });
        });

        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        document.getElementById('sendBtn').addEventListener('click', sendMessage);

        function loadContacts() {
            debugLog('Making fetch request to load contacts...');
            
            fetch('../chat_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_contacts'
            })
            .then(response => {
                debugLog('Got response from server', response.status);
                return response.text();
            })
            .then(text => {
                debugLog('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    debugLog('Parsed JSON data:', data);
                    
                    if (data.success) {
                        contacts = data.contacts;
                        document.getElementById('studentCount').textContent = data.total || contacts.length;
                        renderContacts(data.contacts);
                    } else {
                        showError('Failed to load contacts: ' + (data.message || 'Unknown error'));
                    }
                } catch (e) {
                    debugLog('JSON parse error:', e);
                    showError('Server response error. Check console for details.');
                }
            })
            .catch(error => {
                debugLog('Fetch error:', error);
                showError('Network error: ' + error.message);
            });
        }

        function renderContacts(contactsList) {
            debugLog('Rendering contacts, count:', contactsList.length);
            const container = document.getElementById('contactsList');
            
            if (!contactsList || contactsList.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🔭</div>
                        <p>No students found</p>
                        <p style="font-size: 14px; margin-top: 10px;">Make sure students are assigned to your classes</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = contactsList.map((contact, index) => {
                debugLog(`Rendering contact ${index}:`, contact);
                return `
                    <div class="contact-item" 
                         data-id="${contact.id}" 
                         data-type="${contact.type}" 
                         onclick="selectContact(${contact.id}, '${contact.type}', '${escapeHtml(contact.name)}', '${escapeHtml(contact.subtitle)}')">
                        <div class="contact-avatar">${contact.name.charAt(0).toUpperCase()}</div>
                        <div class="contact-info">
                            <div class="contact-name">
                                <span>${escapeHtml(contact.name)}</span>
                                ${contact.unread_count > 0 ? `<span class="unread-badge">${contact.unread_count}</span>` : ''}
                            </div>
                            <div class="contact-subtitle">${escapeHtml(contact.subtitle)}</div>
                            ${contact.meta ? `<div class="contact-meta">${escapeHtml(contact.meta)}</div>` : ''}
                            ${contact.last_message ? `<div class="contact-last-message">${escapeHtml(contact.last_message)}</div>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function selectContact(id, type, name, subtitle) {
            debugLog('Selecting contact:', {id, type, name, subtitle});
            currentContact = { id, type, name, subtitle };
            
            document.querySelectorAll('.contact-item').forEach(item => item.classList.remove('active'));
            const selectedItem = document.querySelector(`[data-id="${id}"]`);
            if (selectedItem) {
                selectedItem.classList.add('active');
            }
            
            document.getElementById('emptyChatState').style.display = 'none';
            document.getElementById('activeChatContainer').style.display = 'flex';
            
            document.getElementById('chatHeaderAvatar').textContent = name.charAt(0).toUpperCase();
            document.getElementById('chatHeaderName').textContent = name;
            document.getElementById('chatHeaderSubtitle').textContent = subtitle;
            
            loadMessages(id, type);
            markAsRead(id, type);
            
            if (messageCheckInterval) clearInterval(messageCheckInterval);
            messageCheckInterval = setInterval(() => {
                if (currentContact) {
                    loadMessages(currentContact.id, currentContact.type);
                }
            }, 3000);
        }

        function loadMessages(contactId, contactType) {
            const formData = new FormData();
            formData.append('action', 'get_messages');
            formData.append('contact_id', contactId);
            formData.append('contact_type', contactType);

            fetch('../chat_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderMessages(data.messages);
                }
            })
            .catch(error => debugLog('Error loading messages:', error));
        }

        function renderMessages(messages) {
            const container = document.getElementById('chatMessages');
            const scrolledToBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 50;
            
            if (messages.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">💬</div>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = messages.map(msg => `
                <div class="message ${msg.is_sent ? 'sent' : 'received'}">
                    <div class="message-content">
                        ${escapeHtml(msg.message)}
                        <div class="message-time">${msg.time}</div>
                    </div>
                </div>
            `).join('');
            
            if (scrolledToBottom) {
                container.scrollTop = container.scrollHeight;
            }
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message || !currentContact) return;
            
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', currentContact.id);
            formData.append('receiver_type', currentContact.type);
            formData.append('message', message);

            fetch('../chat_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadMessages(currentContact.id, currentContact.type);
                    loadContacts();
                } else {
                    alert('Failed to send message: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                debugLog('Error sending message:', error);
                alert('Network error while sending message');
            });
        }

        function markAsRead(contactId, contactType) {
            const formData = new FormData();
            formData.append('action', 'mark_read');
            formData.append('contact_id', contactId);
            formData.append('contact_type', contactType);

            fetch('../chat_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(() => loadContacts())
            .catch(error => debugLog('Error marking as read:', error));
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        function showError(message) {
            const container = document.getElementById('contactsList');
            container.innerHTML = `
                <div class="error-message">
                    <strong>⚠️ Error:</strong><br>
                    ${message}
                    <button onclick="loadContacts()" style="margin-top: 10px; padding: 8px 15px; border: none; background: #667eea; color: white; border-radius: 5px; cursor: pointer;">
                        🔄 Retry
                    </button>
                </div>
            `;
        }

        setInterval(loadContacts, 30000);

        window.addEventListener('beforeunload', function() {
            if (messageCheckInterval) {
                clearInterval(messageCheckInterval);
            }
        });
    </script>
</body>
</html>