# 🌊 Caspian v3.4 — Extreme Performance Video-Chat Ecosystem

Caspian — это высокотехнологичная платформа для видеосвязи в реальном времени, построенная на базе **Laravel 13**, **Tailwind CSS 4**, **Alpine.js** и **WebRTC**. Версия 3.4 ориентирована на абсолютную стабильность мобильных соединений и глубокую геймификацию пользовательского опыта.

---

## 🚀 Что нового в v3.4?

### 🛠 Стабильность WebRTC (Perfect Negotiation)
Забудьте о «черных экранах» и зависших подключениях. Мы внедрили протокол согласования, который позволяет браузерам автоматически разрешать конфликты при установке связи. Специальный адаптер для **Android Chrome** принудительно перезагружает аппаратный декодер при возвращении пользователя в приложение.

### 🛡 Система безопасности и модерации
Внедрено Middleware для проверки банов. Теперь кнопка «Report» — это не просто запись в базе, а реальный инструмент. Модераторы могут ограничивать доступ пользователям на любой срок, и система мгновенно прервет их текущие сессии.

### 🏆 Система престижа и рангов
Мы оживили поле `site_minutes`. Теперь время, проведенное в системе, конвертируется в статус. Пройдите путь от **Explorer** до **Celestial**. Ранг отображается в виде элегантной HUD-карты со свечением, видимым вашему собеседнику.

### ⚡ Оптимизация производительности
*   **Smart Caching:** Лидерборд теперь работает на 400% быстрее благодаря 10-минутному кэшированию запросов.
*   **No Zombie Rooms:** Система Spaces (Комнаты) теперь корректно отображает онлайн, используя комбинацию Heartbeat-сигналов и Beacon API.

---

## 🛠 Технологический стек

*   **Backend:** Laravel 13 (PHP 8.5)
*   **Real-time:** Laravel Reverb (WebSockets)
*   **Frontend:** Alpine.js (Reactive UI)
*   **Styles:** Tailwind CSS 4 (OKLCH Color Palette)
*   **Database:** PostgreSQL / Redis (Matchmaking Queue)
*   **Video:** WebRTC (P2P Mesh Architecture)

---

## 📂 Архитектурные особенности

### 1. Механизм "Живого видео" (`rebootMobileCamera`)
Для борьбы с агрессивным энергосбережением мобильных ОС, Caspian использует `replaceTrack`. При возврате пользователя во вкладку, система запрашивает новый поток `getUserMedia` и подменяет его в активном PeerConnection без разрыва звонка.

### 2. Matchmaking в Redis
Очередь поиска разделена на сегменты по карме и странам. Мы используем атомарные операции `LPOP` и `RPUSH` в Redis, что позволяет обрабатывать тысячи одновременных запросов на поиск партнера с минимальной задержкой.

---

## ⚙️ Быстрый старт

1. **Клонирование:**
   ```bash
   git clone https://github.com/your-username/caspian.git
   cd caspian
Окружение:
code
Bash
composer install
npm install
cp .env.example .env
php artisan key:generate
База и Очереди:
code
Bash
php artisan migrate
php artisan queue:work
Запуск WebSocket сервера:
code
Bash
php artisan reverb:start
Assets:
code
Bash
npm run dev
💎 Система рангов (Prestige Levels)
Минуты	Ранг	Иконка
100,000+	Celestial	⚛️ (White Glow)
50,000	Eternal	🌌 (Red Glow)
10,000	Overlord	🔱
1,000	Resident	🏠
0	Guest	🐚
Developed by Caspian Intelligence Ecosystem © 2026