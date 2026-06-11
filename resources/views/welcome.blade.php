<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/js/app.js'])
</head>
<body>
    <h1>Тест WebSocket</h1>
    <script type="module">
        window.addEventListener('load', () => {
            console.log("Echo статус:", window.Echo ? "Загружен" : "НЕ ЗАГРУЖЕН");
            window.Echo.channel('test-channel')
                .listen('TestEvent', (e) => {
                    console.log('Событие пришло!', e);
                });
        });
    </script>
</body>
</html>