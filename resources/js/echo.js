import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: '10.1.0.8', // Твой IP
    wsPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws'],
});