
document.addEventListener('DOMContentLoaded', () => {
    const emojiButton = document.getElementById('emoji-btn');
    const emojiPanel = document.getElementById('emoji-picker');
    const emojiPicker = document.getElementById('nextalk-emoji-picker');
    const messageInput = document.getElementById('message-input');

    if (!emojiButton || !emojiPanel || !emojiPicker || !messageInput) {
        console.warn('NexTalk emoji picker elements were not found.');
        return;
    }

    // Open and close the emoji panel
    emojiButton.addEventListener('click', (event) => {
        event.stopPropagation();

        emojiPanel.classList.toggle('active');
    });

    // Insert the selected standard Unicode emoji
    emojiPicker.addEventListener('emoji-click', (event) => {
        const emoji = event.detail.unicode;

        if (!emoji) return;

        messageInput.focus();

        const start = messageInput.selectionStart ?? messageInput.value.length;
        const end = messageInput.selectionEnd ?? start;

        messageInput.setRangeText(emoji, start, end, 'end');

        messageInput.dispatchEvent(
            new Event('input', { bubbles: true })
        );

        emojiPanel.classList.remove('active');
    });

    // Close when clicking outside
    document.addEventListener('click', (event) => {
        if (
            !emojiPanel.contains(event.target) &&
            !emojiButton.contains(event.target)
        ) {
            emojiPanel.classList.remove('active');
        }
    });

    // Keep clicks inside the picker from closing it
    emojiPanel.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    // Escape closes the picker
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            emojiPanel.classList.remove('active');
        }
    });
});
