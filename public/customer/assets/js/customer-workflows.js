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

    var reviewDemo = document.querySelector('[data-review-demo]');
    if (!reviewDemo) {
        return;
    }

    function setRating(group, rating) {
        group.querySelectorAll('[data-rating]').forEach(function (button) {
            var selected = Number(button.getAttribute('data-rating')) <= rating;
            button.classList.toggle('is-selected', selected);
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        group.querySelector('[data-rating-output]').textContent = rating + ' / 5';
    }

    reviewDemo.querySelectorAll('[data-rating-group]').forEach(function (group) {
        group.querySelectorAll('[data-rating]').forEach(function (button) {
            button.addEventListener('click', function () {
                setRating(group, Number(button.getAttribute('data-rating')));
            });
        });
    });

    reviewDemo.querySelectorAll('[data-tag-group] button').forEach(function (button) {
        button.addEventListener('click', function () {
            button.classList.toggle('is-selected');
        });
    });

    var comment = reviewDemo.querySelector('#review-demo-comment');
    reviewDemo.querySelectorAll('[data-suggestion]').forEach(function (button) {
        button.addEventListener('click', function () {
            var suggestion = button.getAttribute('data-suggestion');
            comment.value = comment.value.trim() ? comment.value.trim() + '. ' + suggestion : suggestion;
            comment.focus();
        });
    });

    reviewDemo.querySelectorAll('[data-recommend]').forEach(function (button) {
        button.addEventListener('click', function () {
            reviewDemo.querySelectorAll('[data-recommend]').forEach(function (choice) {
                var active = choice === button;
                choice.classList.toggle('is-selected', active);
                choice.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        });
    });

    var photoInput = reviewDemo.querySelector('[data-photo-input]');
    var photoList = reviewDemo.querySelector('[data-photo-list]');
    reviewDemo.querySelector('[data-photo-picker]').addEventListener('click', function () {
        photoInput.click();
    });
    photoInput.addEventListener('change', function () {
        Array.prototype.forEach.call(photoInput.files || [], function (file) {
            if (!file.type.match(/^image\//)) {
                return;
            }
            var reader = new FileReader();
            reader.addEventListener('load', function () {
                var figure = document.createElement('figure');
                var image = document.createElement('img');
                var remove = document.createElement('button');
                image.src = reader.result;
                image.alt = 'New review photo preview';
                remove.type = 'button';
                remove.setAttribute('data-remove-photo', '');
                remove.setAttribute('aria-label', 'Remove photo');
                remove.innerHTML = '&times;';
                figure.appendChild(image);
                figure.appendChild(remove);
                photoList.insertBefore(figure, reviewDemo.querySelector('[data-photo-picker]'));
            });
            reader.readAsDataURL(file);
        });
        photoInput.value = '';
    });
    photoList.addEventListener('click', function (event) {
        var remove = event.target.closest('[data-remove-photo]');
        if (remove) {
            remove.closest('figure').remove();
        }
    });

    var message = reviewDemo.querySelector('[data-review-message]');
    reviewDemo.querySelector('[data-preview-review]').addEventListener('click', function () {
        var vehicleRating = reviewDemo.querySelector('[data-rating-group="vehicle"] [data-rating-output]').textContent;
        var driverRating = reviewDemo.querySelector('[data-rating-group="driver"] [data-rating-output]').textContent;
        message.textContent = 'Preview ready: vehicle ' + vehicleRating + ', driver ' + driverRating + '. Nothing has been submitted.';
        reviewDemo.querySelector('[data-review-status]').textContent = 'Preview ready';
    });
    reviewDemo.querySelector('[data-clear-review]').addEventListener('click', function () {
        setRating(reviewDemo.querySelector('[data-rating-group="vehicle"]'), 5);
        setRating(reviewDemo.querySelector('[data-rating-group="driver"]'), 5);
        comment.value = '';
        reviewDemo.querySelectorAll('[data-tag-group] button').forEach(function (button) { button.classList.remove('is-selected'); });
        reviewDemo.querySelectorAll('[data-recommend]').forEach(function (choice, index) {
            var active = index === 0;
            choice.classList.toggle('is-selected', active);
            choice.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        message.textContent = 'Your preview selections have been cleared.';
        reviewDemo.querySelector('[data-review-status]').textContent = 'Ready to review';
    });
}());
