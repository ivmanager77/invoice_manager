<?php
$loggedIn = function_exists('is_logged_in') && is_logged_in();
?>
    </main>

    <footer class="border-t border-border bg-surface px-6 py-5 text-center text-xs text-text-muted">
        <div class="mx-auto max-w-6xl flex flex-col items-center justify-between gap-2 sm:flex-row">
            <span>Invoice Manager &copy; <?= date('Y') ?></span>
            <span>Personal Invoice &amp; Warranty Organizer</span>
        </div>
    </footer>

    <?php if ($loggedIn): ?>
        <div id="ai-chat-widget" class="fixed bottom-5 right-5 z-50">
            <div id="ai-chat-box" class="mb-3 hidden w-80 sm:w-96 flex-col overflow-hidden rounded-2xl border border-border bg-surface shadow-xl">
                <div class="flex items-center justify-between bg-primary px-4 py-3 text-white">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                        <span class="text-sm font-semibold">Invoice Assistant</span>
                    </div>
                    <button type="button" id="ai-chat-close" class="text-white/80 hover:text-white text-lg leading-none">&times;</button>
                </div>

                <div id="ai-chat-messages" class="flex h-72 flex-col gap-2.5 overflow-y-auto bg-bg p-3 text-xs">
                    <div class="self-start max-w-[85%] rounded-xl border border-border bg-surface px-3 py-2 text-text shadow-2xs">
                        Hi! Ask me anything about your stores, invoices, or warranties.
                    </div>
                </div>

                <form id="ai-chat-form" class="flex items-center gap-2 border-t border-border bg-surface p-2.5">
                    <input type="text" id="ai-chat-input" placeholder="Ask about your invoices..." required
                           class="flex-1 rounded-lg border border-slate-300 bg-bg px-3 py-2 text-xs text-text focus:border-primary focus:outline-none">
                    <button type="submit" class="rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-primary-hover">
                        Send
                    </button>
                </form>
            </div>

            <button type="button" id="ai-chat-toggle"
                    class="ml-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary text-white shadow-lg transition hover:bg-primary-hover"
                    aria-label="Open AI Assistant">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </button>
        </div>

        <script>
            (function () {
                const toggleBtn = document.getElementById('ai-chat-toggle');
                const closeBtn = document.getElementById('ai-chat-close');
                const chatBox = document.getElementById('ai-chat-box');
                const form = document.getElementById('ai-chat-form');
                const input = document.getElementById('ai-chat-input');
                const messages = document.getElementById('ai-chat-messages');

                function appendMessage(text, isUser) {
                    const div = document.createElement('div');
                    div.className = isUser
                        ? 'self-end max-w-[85%] rounded-xl bg-primary px-3 py-2 text-white shadow-2xs whitespace-pre-wrap'
                        : 'self-start max-w-[85%] rounded-xl border border-border bg-surface px-3 py-2 text-text shadow-2xs whitespace-pre-wrap';
                    div.textContent = text;
                    messages.appendChild(div);
                    messages.scrollTop = messages.scrollHeight;
                    return div;
                }

                toggleBtn.addEventListener('click', function () {
                    chatBox.classList.toggle('hidden');
                    chatBox.classList.toggle('flex');
                    if (!chatBox.classList.contains('hidden')) {
                        input.focus();
                    }
                });

                closeBtn.addEventListener('click', function () {
                    chatBox.classList.add('hidden');
                    chatBox.classList.remove('flex');
                });

                form.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    const text = input.value.trim();
                    if (!text) return;

                    appendMessage(text, true);
                    input.value = '';
                    const loadingEl = appendMessage('Thinking...', false);

                    try {
                        const res = await fetch('ai_bot.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ message: text })
                        });
                        const raw = await res.text();
                        let data = {};
                        try {
                            data = JSON.parse(raw);
                        } catch (parseErr) {
                            loadingEl.textContent = 'Server error: ' + raw.slice(0, 150);
                            return;
                        }
                        loadingEl.textContent = data.reply || data.error || 'No response.';
                    } catch (err) {
                        loadingEl.textContent = 'Connection error. Please try again.';
                    }
                });
            })();
        </script>
    <?php endif; ?>
</body>
</html>
