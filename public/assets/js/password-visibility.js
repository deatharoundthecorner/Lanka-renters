document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    if (!button) return;

    const input = document.getElementById(button.dataset.passwordToggle);
    if (!input || !['password', 'text'].includes(input.type)) return;

    const showing = input.type === 'password';
    input.type = showing ? 'text' : 'password';
    button.textContent = showing ? 'Hide' : 'Show';
    button.setAttribute('aria-label', `${showing ? 'Hide' : 'Show'} password`);
    button.setAttribute('aria-pressed', String(showing));
});
