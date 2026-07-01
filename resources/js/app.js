import './bootstrap';
import Alpine from 'alpinejs';

window.sendBrowserLog = function(message, level = 'error') {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch('/_boost/browser-logs', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            message: message,
            level: level,
            url: window.location.href
        })
    })
    .then(response => response.json())
    .then(data => console.log('Лог отправлен:', data))
    .catch(error => console.error('Ошибка отправки лога:', error));
}

window.Alpine = Alpine;
Alpine.start();