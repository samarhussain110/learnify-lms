<?php
declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    return;
}

$chatScriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
$chatIsStudentArea = basename(dirname($chatScriptName)) === 'student';
$chatApiEndpoint = $chatIsStudentArea ? '../api/chat-assistant.php' : 'api/chat-assistant.php';
?>
<aside class="chatbot" data-chatbot data-api-endpoint="<?= learnify_h($chatApiEndpoint) ?>" aria-label="Learnify AI assistant">
    <section
        class="chatbot-panel"
        id="chatbot-panel"
        data-chatbot-panel
        aria-labelledby="chatbot-title"
        aria-hidden="true"
        hidden
    >
        <div class="chatbot-header">
            <div class="chatbot-heading">
                <span class="chatbot-avatar" aria-hidden="true"><i class="fa-solid fa-sparkles"></i></span>
                <div>
                    <h2 class="mb-0" id="chatbot-title">Learnify Assistant</h2>
                    <p class="chatbot-subtitle mb-0">Here to help you learn</p>
                </div>
            </div>
            <button class="chatbot-close" type="button" data-chatbot-close aria-label="Close chat">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <div class="chatbot-messages" data-chatbot-messages role="log" aria-live="polite" aria-relevant="additions text">
            <div class="chatbot-message is-assistant">
                Hi! Ask me about Learnify modules, the Study Hub, or exam practice.
            </div>
        </div>

        <div class="chatbot-suggestions" data-chatbot-suggestions aria-label="Suggested questions">
            <button type="button" class="chatbot-suggestion" data-chat-suggestion="Explain code">Explain code</button>
            <button type="button" class="chatbot-suggestion" data-chat-suggestion="Give an example">Give an example</button>
            <button type="button" class="chatbot-suggestion" data-chat-suggestion="How to prepare for exam?">How to prepare for exam?</button>
        </div>

        <div class="chatbot-typing" data-chatbot-typing role="status" aria-live="polite" hidden>
            <span></span><span></span><span></span>
            <span class="sr-only">Learnify Assistant is typing</span>
        </div>

        <form class="chatbot-form" data-chatbot-form>
            <input type="hidden" data-chatbot-csrf value="<?= learnify_h(learnify_csrf_token()) ?>">
            <label class="sr-only" for="chatbot-input">Message Learnify Assistant</label>
            <textarea
                class="chatbot-input"
                id="chatbot-input"
                data-chatbot-input
                name="message"
                rows="1"
                maxlength="4000"
                placeholder="Ask a study question..."
                autocomplete="off"
                required
            ></textarea>
            <button class="chatbot-send" type="submit" data-chatbot-send aria-label="Send message">
                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
            </button>
        </form>
    </section>

    <button
        class="chatbot-toggle"
        type="button"
        data-chatbot-toggle
        aria-label="Open Learnify Assistant"
        aria-controls="chatbot-panel"
        aria-expanded="false"
    >
        <i class="fa-regular fa-comments" data-chatbot-toggle-icon aria-hidden="true"></i>
        <span class="sr-only">Open Learnify Assistant</span>
    </button>
</aside>

<script>
(() => {
    const widget = document.querySelector('[data-chatbot]');
    if (!widget) {
        return;
    }

    const panel = widget.querySelector('[data-chatbot-panel]');
    const toggle = widget.querySelector('[data-chatbot-toggle]');
    const toggleIcon = widget.querySelector('[data-chatbot-toggle-icon]');
    const close = widget.querySelector('[data-chatbot-close]');
    const messages = widget.querySelector('[data-chatbot-messages]');
    const form = widget.querySelector('[data-chatbot-form]');
    const input = widget.querySelector('[data-chatbot-input]');
    const send = widget.querySelector('[data-chatbot-send]');
    const typing = widget.querySelector('[data-chatbot-typing]');
    const csrfToken = widget.querySelector('[data-chatbot-csrf]').value;
    const suggestions = widget.querySelectorAll('[data-chat-suggestion]');
    const conversation = [];
    let isSending = false;

    const getContext = () => {
        const lessonPage = document.querySelector('[data-chat-module-id]');
        if (!lessonPage) {
            return null;
        }
        return {
            module_id: Number(lessonPage.dataset.chatModuleId) || 0,
            lesson_id: Number(lessonPage.dataset.chatLessonId) || 0
        };
    };

    const setSuggestionsDisabled = (disabled) => {
        suggestions.forEach((suggestion) => {
            suggestion.disabled = disabled;
        });
    };

    const setOpen = (open) => {
        panel.hidden = !open;
        panel.setAttribute('aria-hidden', String(!open));
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close Learnify Assistant' : 'Open Learnify Assistant');
        toggleIcon.className = open ? 'fa-solid fa-xmark' : 'fa-regular fa-comments';
        if (open) {
            input.focus();
            messages.scrollTop = messages.scrollHeight;
        }
    };

    const addMessage = (text, role) => {
        const message = document.createElement('div');
        message.className = `chatbot-message is-${role}`;
        if (role === 'assistant') {
            renderMarkdown(message, text);
        } else {
            message.textContent = text;
        }
        messages.append(message);
        messages.scrollTo({ top: messages.scrollHeight, behavior: 'smooth' });
        return message;
    };

    const appendInlineMarkdown = (container, text) => {
        const pattern = /(`[^`\n]+`|\*\*[^*]+\*\*|__[^_]+__|\*[^*\n]+\*|_[^_\n]+_|\[[^\]]+\]\((https?:\/\/[^\s)]+)\))/g;
        let cursor = 0;
        let match;

        while ((match = pattern.exec(text)) !== null) {
            if (match.index > cursor) {
                container.append(document.createTextNode(text.slice(cursor, match.index)));
            }

            const token = match[0];
            let node;
            if (token.startsWith('`')) {
                node = document.createElement('code');
                node.textContent = token.slice(1, -1);
                node.className = 'chatbot-inline-code';
            } else if (token.startsWith('**') || token.startsWith('__')) {
                node = document.createElement('strong');
                node.textContent = token.slice(2, -2);
            } else if (token.startsWith('*') || token.startsWith('_')) {
                node = document.createElement('em');
                node.textContent = token.slice(1, -1);
            } else {
                const linkMatch = /^\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)$/.exec(token);
                if (!linkMatch) {
                    container.append(document.createTextNode(token));
                    cursor = pattern.lastIndex;
                    continue;
                }
                node = document.createElement('a');
                node.textContent = linkMatch[1];
                node.href = linkMatch[2];
                node.target = '_blank';
                node.rel = 'noopener noreferrer';
            }
            container.append(node);
            cursor = pattern.lastIndex;
        }

        if (cursor < text.length) {
            container.append(document.createTextNode(text.slice(cursor)));
        }
    };

    const renderMarkdown = (container, markdown) => {
        const lines = markdown.replace(/\r\n?/g, '\n').split('\n');
        let paragraph = [];
        let list = null;
        let codeBlock = null;
        let codeLanguage = '';

        const flushParagraph = () => {
            if (paragraph.length === 0) {
                return;
            }
            const element = document.createElement('p');
            appendInlineMarkdown(element, paragraph.join(' '));
            container.append(element);
            paragraph = [];
        };

        const closeList = () => {
            if (list) {
                container.append(list);
                list = null;
            }
        };

        for (const line of lines) {
            const fence = /^\s*```([\w+#.-]*)\s*$/.exec(line);
            if (fence) {
                flushParagraph();
                closeList();
                if (codeBlock) {
                    const pre = document.createElement('pre');
                    const code = document.createElement('code');
                    if (codeLanguage) {
                        code.className = `language-${codeLanguage}`;
                    }
                    code.textContent = codeBlock.join('\n');
                    pre.append(code);
                    container.append(pre);
                    codeBlock = null;
                    codeLanguage = '';
                } else {
                    codeBlock = [];
                    codeLanguage = fence[1];
                }
                continue;
            }

            if (codeBlock) {
                codeBlock.push(line);
                continue;
            }

            const headingMatch = /^\s{0,3}(#{1,6})\s+(.+?)\s*#*\s*$/.exec(line);
            if (headingMatch) {
                flushParagraph();
                closeList();
                const level = Math.min(4, Math.max(2, headingMatch[1].length));
                const heading = document.createElement(`h${level}`);
                appendInlineMarkdown(heading, headingMatch[2]);
                container.append(heading);
                continue;
            }

            const listMatch = /^\s*(?:([-*+])|(\d+)\.)\s+(.+)$/.exec(line);
            if (listMatch) {
                flushParagraph();
                const listType = listMatch[2] ? 'ol' : 'ul';
                if (!list || list.tagName.toLowerCase() !== listType) {
                    closeList();
                    list = document.createElement(listType);
                }
                const item = document.createElement('li');
                appendInlineMarkdown(item, listMatch[3]);
                list.append(item);
                continue;
            }

            if (line.trim() === '') {
                flushParagraph();
                closeList();
                continue;
            }

            closeList();
            paragraph.push(line.trim());
        }

        if (codeBlock) {
            const pre = document.createElement('pre');
            const code = document.createElement('code');
            if (codeLanguage) {
                code.className = `language-${codeLanguage}`;
            }
            code.textContent = codeBlock.join('\n');
            pre.append(code);
            container.append(pre);
        }
        flushParagraph();
        closeList();
    };

    toggle.addEventListener('click', () => setOpen(panel.hidden));
    close.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            setOpen(false);
            toggle.focus();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message || isSending) {
            return;
        }

        addMessage(message, 'user');
        input.value = '';
        input.style.height = 'auto';
        isSending = true;
        send.disabled = true;
        input.disabled = true;
        setSuggestionsDisabled(true);
        typing.hidden = false;
        messages.scrollTop = messages.scrollHeight;

        try {
            const response = await fetch(widget.dataset.apiEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    message,
                    history: conversation.slice(-10),
                    context: getContext()
                })
            });
            let result;
            try {
                result = await response.json();
            } catch {
                throw new Error('The assistant returned an unreadable response.');
            }
            if (
                !response.ok
                || (result.success !== undefined && result.success !== true)
                || typeof result.reply !== 'string'
            ) {
                throw new Error(result.message || 'The assistant could not respond. Please try again.');
            }
            addMessage(result.reply, 'assistant');
            conversation.push(
                { role: 'user', content: message },
                { role: 'assistant', content: result.reply }
            );
        } catch (error) {
            addMessage(error.message || 'Message could not be sent. Please try again.', 'error');
        } finally {
            typing.hidden = true;
            isSending = false;
            send.disabled = false;
            input.disabled = false;
            setSuggestionsDisabled(false);
            input.focus();
        }
    });

    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 120)}px`;
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    suggestions.forEach((suggestion) => {
        suggestion.addEventListener('click', () => {
            if (isSending) {
                return;
            }
            input.value = suggestion.dataset.chatSuggestion;
            form.requestSubmit();
        });
    });
})();
</script>
