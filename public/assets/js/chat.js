const chatData = window.chatData || {};
const userRole = chatData.userRole || '';
const csrfToken = chatData.csrfToken || '';
const form = document.getElementById('messageForm');
const input = form ? form.querySelector('input[name="message"]') : null;
const messagesDiv = document.getElementById('messages');
const chatErrorDiv = document.getElementById('chatError');

function scrollChatToBottom() {
    const chatbox = document.getElementById('chatbox');
    if (!chatbox) return;
    chatbox.scrollTop = chatbox.scrollHeight;
}

function showChatError(message) {
    if (!chatErrorDiv) return;
    if (message) {
        chatErrorDiv.textContent = message;
        chatErrorDiv.hidden = false;
        return;
    }
    chatErrorDiv.textContent = '';
    chatErrorDiv.hidden = true;
}

function renderMessages(messages) {
    const chatbox = document.getElementById('chatbox');
    const nearBottom = !chatbox
        || chatbox.scrollHeight - chatbox.scrollTop - chatbox.clientHeight < 40;
    const wasEmpty = messagesDiv.childElementCount === 0;

    messagesDiv.replaceChildren();

    if (messages.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'chat-empty';
        empty.textContent = 'Aucun message pour le moment.';
        messagesDiv.appendChild(empty);
        return;
    }

    messages.forEach((message) => {
        const item = document.createElement('div');
        item.className = 'message';
        if (message.is_admin || message.user_login === 'Admin' || Number(message.user_id) === 0) {
            item.classList.add('message--admin');
        }

        const author = document.createElement('strong');
        author.className = 'username';
        author.textContent = message.user_login;

        const date = document.createElement('small');
        date.className = 'chat-meta';
        date.textContent = ` (${message.date_post})`;

        item.append(author, date);

        if (userRole === 'admin' || userRole === 'editor') {
            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'chat-delete';
            deleteButton.dataset.id = String(message.id);
            deleteButton.setAttribute('aria-label', 'Supprimer le message');
            deleteButton.textContent = 'Supprimer';
            item.append(deleteButton);
        }

        const text = document.createElement('span');
        text.className = 'text';
        text.textContent = message.message;
        item.append(text);
        messagesDiv.appendChild(item);
    });

    if (wasEmpty || nearBottom) {
        scrollChatToBottom();
    }
}

function fetchMessages() {
    if (!messagesDiv) return;

    fetch('/chat', { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .then((data) => {
            showChatError(data.error || '');
            if (data.error || !Array.isArray(data.messages)) return;
            renderMessages(data.messages);
        })
        .catch((error) => console.error('Erreur fetchMessages:', error));
}

setInterval(fetchMessages, 10000);
fetchMessages();

document.addEventListener('click', (event) => {
    const button = event.target.closest('.chat-delete');
    if (!button) return;

    const body = new URLSearchParams({
        delete_id: button.dataset.id || '',
        csrf_token: csrfToken,
    });

    fetch('/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body,
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                showChatError('');
                fetchMessages();
                return;
            }
            showChatError(data.error || 'Suppression impossible.');
        })
        .catch((error) => console.error('Erreur suppression:', error));
});

if (form && input) {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) return;

        const body = new URLSearchParams({
            message,
            csrf_token: csrfToken,
        });

        fetch('/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    input.value = '';
                    showChatError('');
                    fetchMessages();
                    return;
                }
                showChatError(data.error || 'Envoi impossible.');
            })
            .catch((error) => console.error('Erreur fetch POST:', error));
    });
}
