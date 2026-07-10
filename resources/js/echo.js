import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Динамически определяем порт: если зашли по дефолтному https (443), то и сокет идет на 443. 
// Если в урле указан локальный порт 8443, сокет пойдет на 8443.
const currentPort = window.location.port ? parseInt(window.location.port) : 443;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'vyks9t0oswy3jofj2wvl',
    
    // Берет хост из .env, иначе автоматически подставляет IP/Домен из браузера
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    
    // Динамические порты в зависимости от того, как клиент зашел на сайт
    wsPort: import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT) : currentPort,
    wssPort: import.meta.env.VITE_REVERB_PORT ? parseInt(import.meta.env.VITE_REVERB_PORT) : currentPort,
    
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
});
