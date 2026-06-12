import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'vyks9t0oswy3jofj2wvl',
    wsHost: '10.1.0.8',
    wsPort: 8443,
    wssPort: 8443,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
});