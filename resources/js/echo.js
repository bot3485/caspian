import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'vyks9t0oswy3jofj2wvl',
    wsHost: '10.1.0.8',
    wsPort: 8443,         // МЕНЯЕМ НА ТВОЙ ПОРТ HTTPS
    wssPort: 8443,        // МЕНЯЕМ НА ТВОЙ ПОРТ HTTPS
    forceTLS: true,       // Оставляем true, так как сайт на HTTPS
    enabledTransports: ['ws', 'wss'],
});