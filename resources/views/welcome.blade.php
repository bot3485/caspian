<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reverb Debug Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div style="padding: 20px; font-family: sans-serif;">
        <h1>Laravel Reverb Debug Dashboard</h1>
        <div id="status" style="padding: 10px; background: #eee; border-radius: 5px;">
            Echo статус: Инициализация...
        </div>
        
        <div id="debug-log" style="margin-top: 20px; padding: 10px; background: #000; color: #0f0; border-radius: 5px; height: 300px; overflow-y: scroll; font-family: monospace;">
            Логи событий будут здесь...
        </div>
    </div>

    <script type="module">
        // Функция для вывода логов на страницу
        function log(message, data = null) {
            const logDiv = document.getElementById('debug-log');
            const entry = document.createElement('div');
            entry.textContent = `> ${new Date().toLocaleTimeString()}: ${message}`;
            logDiv.appendChild(entry);
            logDiv.scrollTop = logDiv.scrollHeight;
            if (data) console.log(message, data);
        }

        window.addEventListener('load', () => {
            document.getElementById('status').innerText = "Echo: загружен и готов.";
            log("Echo: попытка подписки на test-channel...");

            // Основная логика Echo
            window.Echo.channel('test-channel')
                .subscribed(() => {
                    log("✅ УСПЕШНАЯ ПОДПИСКА на test-channel!");
                    document.getElementById('status').style.background = "#d4edda";
                })
                .error((error) => {
                    log("❌ ОШИБКА Echo:", error);
                })
                // Ловим событие с точкой, так как Laravel отправляет его как .test-event
                .listen('.test-event', (e) => {
                    log('🎉 СОБЫТИЕ ПОЙМАНО:', e);
                    alert('Данные пришли!');
                })
                // Страховка: ловим всё, что прилетает в этот канал
                .listen('.*', (e) => {
                    log('🔥 ГЛОБАЛЬНЫЙ ПЕРЕХВАТ:', e);
                });
        });
    </script>
</body>
</html>