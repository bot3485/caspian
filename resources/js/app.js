import './bootstrap';

import Alpine from 'alpinejs';
import axios from 'axios';
import caspianCore from './components/caspian-core';


Alpine.data('caspianApp', caspianCore);
window.Alpine = Alpine;
/**
 * Настройка Axios
 */
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Перехватчик ответов (Interceptors)
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        // 1. Если сессия истекла (ошибка 419 CSRF), просто перезагружаем страницу
        if (error.response && error.response.status === 419) {
            window.location.reload();
        }

        // 2. Логируем критические ошибки фронтенда на сервер (через ваш BrowserLogController)
        // Это поможет вам видеть ошибки WebRTC в логах Laravel
        if (error.response && error.response.status >= 500) {
            window.axios.post('/_boost/browser-logs', {
                message: `Server Error ${error.response.status} at ${error.config.url}`,
                level: 'error',
                url: window.location.href
            }).catch(() => {}); // Игнорируем ошибки самого логгера
        }

        return Promise.reject(error);
    }
);

/**
 * Глобальный обработчик ошибок JS
 * Отправляет ошибки в лог Laravel (у вас уже есть роут и контроллер для этого)
 */
window.onerror = function(message, source, lineno, colno, error) {
    if (window.axios) {
        window.axios.post('/_boost/browser-logs', {
            message: `JS Error: ${message} at ${source}:${lineno}`,
            level: 'error',
            url: window.location.href
        }).catch(() => {});
    }
};

window.changeLanguage = async (lang) => {
    try {
        await window.axios.post('/lang/set', { locale: lang }); // Создай этот роут для гостей
        window.location.reload();
    } catch (e) {
        console.error("Language change failed");
    }
};
/**
 * Инициализация Alpine.js
 */
Alpine.start();

/**
 * Полезный хелпер для работы с URL (чтобы не было проблем с MethodNotAllowed)
 * Использование: window.safeUrl('/chat/signal')
 */
window.safeUrl = function(path) {
    // Убираем слеш в конце, если он есть, чтобы Laravel не делал редирект с POST на GET
    return path.endsWith('/') ? path.slice(0, -1) : path;
};

console.log('Caspian App Initialized');