(function () {
    'use strict';

    document.querySelectorAll('[data-character-input]').forEach(function (input) {
        var counter = input.parentElement ? input.parentElement.querySelector('[data-character-count]') : null;
        if (!counter) {
            return;
        }
        var update = function () {
            counter.textContent = input.value.length + ' / ' + (input.maxLength > 0 ? input.maxLength : '∞');
        };
        input.addEventListener('input', update);
        update();
    });

    document.querySelectorAll('[data-file-input]').forEach(function (input) {
        var output = input.parentElement ? input.parentElement.querySelector('[data-file-name]') : null;
        if (!output) {
            return;
        }
        input.addEventListener('change', function () {
            var file = input.files && input.files[0] ? input.files[0] : null;
            output.textContent = file ? file.name + ' (' + Math.ceil(file.size / 1024) + ' KB)' : 'No file selected.';
        });
    });

    document.querySelectorAll('form[data-submit-once]').forEach(function (form) {
        form.addEventListener('submit', function () {
            if (!form.checkValidity()) {
                return;
            }
            var button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.textContent = 'Submitting…';
            }
        });
    });

    var messageList = document.querySelector('[data-message-list]');
    if (messageList) {
        messageList.scrollTop = messageList.scrollHeight;
    }
}());
