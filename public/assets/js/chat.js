const chatData = window.chatData || {};
const userRole = chatData.userRole || '';
const csrfToken = chatData.csrfToken || '';
const form = document.getElementById('messageForm');
const input = form ? form.querySelector('input[name="message"]') : null;
const messagesDiv = document.getElementById('messages');
const chatErrorDiv = document.getElementById('chatError');
const chatbox = document.getElementById('chatbox');
const POLL_ACTIVE_MS = 1000;
const POLL_IDLE_MS = 2000;
const ACTIVE_WINDOW_MS = 15000;
const FULL_SYNC_INTERVAL_MS = 30000;
let lastMessageId = 0;
let lastFullSyncAt = 0;
let lastActivityAt = 0;
let hasLoadedMessages = false;
let requestInFlight = false;
let fullSyncRequested = false;
let syncRequested = false;
let pollTimeout = null;

function scrollChatToBottom() {
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

function renderMessages(messages, replace = false) {
    const nearBottom = !chatbox
        || chatbox.scrollHeight - chatbox.scrollTop - chatbox.clientHeight < 40;
    const wasEmpty = replace || messagesDiv.querySelector('.message') === null;

    if (replace) {
        messagesDiv.replaceChildren();
        lastMessageId = 0;
    }

    if (messages.length === 0) {
        if (replace) {
            const empty = document.createElement('p');
            empty.className = 'chat-empty';
            empty.textContent = 'Aucun message pour le moment.';
            messagesDiv.appendChild(empty);
        }
        return;
    }

    messagesDiv.querySelector('.chat-empty')?.remove();
    let appended = false;
    messages.forEach((message) => {
        const messageId = Number(message.id);
        if (!Number.isSafeInteger(messageId) || messageId <= lastMessageId) {
            return;
        }

        const item = document.createElement('div');
        item.className = 'message';
        item.dataset.messageId = String(messageId);
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
        lastMessageId = messageId;
        appended = true;
    });

    if (appended && (wasEmpty || nearBottom)) {
        scrollChatToBottom();
    }
}

async function syncMessages(forceFull = false) {
    if (!messagesDiv || document.hidden) return;
    if (requestInFlight) {
        fullSyncRequested ||= forceFull;
        syncRequested = true;
        return;
    }

    const shouldFullSync = forceFull
        || !hasLoadedMessages
        || Date.now() - lastFullSyncAt >= FULL_SYNC_INTERVAL_MS;
    requestInFlight = true;

    try {
        const url = shouldFullSync ? '/chat' : `/chat?after_id=${lastMessageId}`;
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (!response.ok || !data.success || !Array.isArray(data.messages)) {
            showChatError(data.error || 'Impossible de charger les messages.');
            return;
        }

        renderMessages(data.messages, shouldFullSync);
        showChatError('');
        hasLoadedMessages = true;
        if (shouldFullSync) {
            lastFullSyncAt = Date.now();
        }
    } catch (error) {
        showChatError('Connexion au chat indisponible. Nouvelle tentative en cours.');
        console.error('Erreur de synchronisation du chat:', error);
    } finally {
        requestInFlight = false;
        if (fullSyncRequested) {
            fullSyncRequested = false;
            syncRequested = false;
            window.setTimeout(() => syncMessages(true), 0);
        } else if (syncRequested) {
            syncRequested = false;
            window.setTimeout(() => syncMessages(), 0);
        } else {
            scheduleNextSync();
        }
    }
}

function scheduleNextSync() {
    window.clearTimeout(pollTimeout);
    if (document.hidden || !hasLoadedMessages) return;

    const recentlyActive = Date.now() - lastActivityAt < ACTIVE_WINDOW_MS;
    const delay = recentlyActive ? POLL_ACTIVE_MS : POLL_IDLE_MS;
    pollTimeout = window.setTimeout(() => syncMessages(), delay);
}

function noteChatActivity() {
    lastActivityAt = Date.now();
}

if (chatbox) {
    ['pointerdown', 'keydown', 'focusin'].forEach((eventName) => {
        chatbox.addEventListener(eventName, noteChatActivity);
    });
}

form?.addEventListener('focusin', noteChatActivity);
form?.addEventListener('keydown', noteChatActivity);

document.addEventListener('visibilitychange', () => {
    window.clearTimeout(pollTimeout);
    if (!document.hidden) {
        syncMessages(true);
    }
});

syncMessages(true);

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
                syncMessages(true);
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
                    syncMessages();
                    return;
                }
                showChatError(data.error || 'Envoi impossible.');
            })
            .catch((error) => console.error('Erreur fetch POST:', error));
    });
}
