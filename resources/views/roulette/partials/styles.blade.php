<style>
/* === 1. БАЗОВАЯ ЭЛЕГАНТНОСТЬ (Стекло) === */
    .elegant-glass {
        background: rgba(10, 10, 10, 0.6);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    }

    /* === 2. ДИЗАЙН КНОПОК === */
    .btn-glass {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 1.5rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
    }
    .btn-glass:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }
    
    /* Состояние выключено/опасность (например, микрофон) */
    .btn-glass-danger {
        background: rgba(220, 38, 38, 0.05);
        border-color: rgba(220, 38, 38, 0.2);
    }
    .btn-glass-danger:hover {
        background: rgba(220, 38, 38, 0.15);
        border-color: rgba(220, 38, 38, 0.4);
    }

    /* Главные кнопки действий */
    .btn-action-primary {
        background: linear-gradient(135deg, rgba(99,102,241,0.8), rgba(168,85,247,0.8));
        border: 1px solid rgba(255,255,255,0.2);
        color: white;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        transition: all 0.4s ease;
    }
    .btn-action-primary:hover {
        background: linear-gradient(135deg, rgba(99,102,241,0.9), rgba(168,85,247,0.9));
        transform: scale(1.02);
    }

    /* === 3. LED ИНТЕГРАЦИЯ (Срабатывает только при body.leds-on) === */
    @keyframes elegant-led-flow {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .led-container-fx {
        position: relative;
        z-index: 1;
    }

    .led-container-fx::before,
    .led-container-fx::after {
        content: "";
        position: absolute;
        inset: -1px;
        border-radius: inherit;
        pointer-events: none;
        opacity: 0; /* ВЫКЛЮЧЕНО ПО УМОЛЧАНИЮ */
        transition: opacity 0.6s ease;
    }

    body.leds-on .led-container-fx::before {
        background: linear-gradient(90deg, rgba(99,102,241,0.5), rgba(6,182,212,0.3), rgba(236,72,153,0.3), rgba(99,102,241,0.5));
        background-size: 200% 200%;
        animation: elegant-led-flow 6s ease infinite;
        z-index: -1;
        opacity: 1; /* ВКЛЮЧАЕТСЯ РАМКА */
    }

    body.leds-on .led-container-fx::after {
        background: linear-gradient(90deg, rgba(99,102,241,0.3), rgba(6,182,212,0.1), rgba(236,72,153,0.1), rgba(99,102,241,0.3));
        background-size: 200% 200%;
        filter: blur(12px);
        animation: elegant-led-flow 6s ease infinite;
        z-index: -2;
        opacity: 0.6; /* ВКЛЮЧАЕТСЯ СВЕЧЕНИЕ */
    }

    body.leds-on .led-container-fx:hover::after {
        opacity: 0.8;
    }

    /* Свечение на кнопках при включенном LED */
    body.leds-on .btn-glass:hover {
        border-color: rgba(99, 102, 241, 0.4);
        box-shadow: 0 0 20px rgba(99, 102, 241, 0.15), inset 0 0 10px rgba(99, 102, 241, 0.05);
    }
    body.leds-on .btn-glass-danger {
        box-shadow: 0 0 15px rgba(220, 38, 38, 0.15);
    }
    body.leds-on .btn-action-primary {
        box-shadow: 0 0 25px rgba(99, 102, 241, 0.4);
    }

    /* === 4. ГЕНДЕРНАЯ АУРА (Вкл/Выкл) === */
    @keyframes spin-slow {
        from { transform: translate(-50%, -50%) rotate(0deg); }
        to { transform: translate(-50%, -50%) rotate(360deg); }
    }

    .gender-aura {
        opacity: 0;
        transition: opacity 0.6s ease;
    }
    body.leds-on .gender-aura {
        opacity: 0.4;
    }

    .gender-ring-wrapper {
        position: absolute;
        inset: -2px;
        border-radius: 1.6rem;
        overflow: hidden;
        z-index: 10;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.6s ease;
    }
    body.leds-on .gender-ring-wrapper {
        opacity: 1;
    }
    .gender-ring {
        position: absolute;
        top: 50%; left: 50%;
        width: 150%; height: 150%;
        animation: spin-slow 4s linear infinite;
    }

    /* --- ОСТАЛЬНОЙ CSS (Глитчи и Блиц - оставлены без изменений) --- */
    @keyframes blitz-hell-warp {
        0%, 100% { transform: perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1); }
        20% { transform: perspective(1000px) rotateX(15deg) rotateY(-15deg) scale(1.05) skewX(5deg); }
        40% { transform: perspective(1000px) rotateX(-20deg) rotateY(20deg) scale(0.95) skewY(-5deg); }
        60% { transform: perspective(1000px) rotateX(10deg) rotateY(10deg) scale(1.1); }
        80% { transform: perspective(1000px) rotateX(-5deg) rotateY(-10deg) scale(0.9); }
    }

    @keyframes blitz-hell-border {
        0%, 100% { border-color: #ff0000; box-shadow: 0 0 80px #ff0000, inset 0 0 30px #000; }
        50% { border-color: #ffffff; box-shadow: 0 0 120px #ff0000, inset 0 0 60px #600000; }
    }

    .blitz-hell-video {
        animation: blitz-glitch-visual 0.15s steps(2) infinite !important;
        filter: invert(1) hue-rotate(180deg) contrast(4) brightness(1.2) saturate(4) !important;
        mix-blend-mode: exclusion;
        will-change: filter, transform;
    }

    .blitz-hell-logic {
        animation: 
            blitz-shake-intense 0.08s linear infinite,
            blitz-hell-warp 1.2s ease-in-out infinite,
            blitz-hell-border 0.2s infinite !important;
        z-index: 500 !important;
        border-width: 8px !important;
        background: #000 !important;
        clip-path: polygon(0 0, 100% 0, 100% 90%, 80% 100%, 20% 95%, 0 100%) !important;
    }

    .blitz-grid-warp {
        perspective: 1500px;
        overflow: visible !important;
    }

    @keyframes toxic-flicker {
        0%, 100% { opacity: 1; filter: drop-shadow(0 0 15px #ff0000); }
        33% { opacity: 0.4; filter: drop-shadow(0 0 5px #7f1d1d); }
        66% { opacity: 0.8; filter: drop-shadow(0 0 20px #b91c1c); }
    }

    .led-toxic {
        background: conic-gradient(from 0deg, #ff0000, #000, #ff0000, #450a0a, #ff0000) !important;
        animation: led-rotate-video 2s linear infinite, toxic-flicker 0.5s ease-in-out infinite !important;
    }

    @keyframes devilish-pulse {
        0% { box-shadow: 0 0 5px #ff0000, inset 0 0 5px #ff0000; opacity: 0.8; }
        50% { box-shadow: 0 0 20px #ff0000, inset 0 0 10px #ff0000; opacity: 1; }
        100% { box-shadow: 0 0 5px #ff0000, inset 0 0 5px #ff0000; opacity: 0.8; }
    }

    .hud-no-network {
        background: rgba(127, 29, 29, 0.8) !important;
        border-color: rgba(220, 38, 38, 0.5) !important;
        animation: devilish-pulse 1s infinite;
    }

    .hud-away {
        background: rgba(245, 158, 11, 0.15) !important;
        border-color: rgba(245, 158, 11, 0.4) !important;
    }

    .inner-video-ring {
        position: absolute;
        inset: 0;
        border-radius: inherit;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
        pointer-events: none;
        z-index: 15; /* Располагаем над видео, но под оверлеями */
        transition: box-shadow 0.6s ease;
    }

    /* Внутреннее неоновое свечение на стекле при включении */
    body.leds-on .inner-video-ring {
        box-shadow: inset 0 0 25px rgba(99, 102, 241, 0.25);
    }

    /* Анимированная градиентная LED-лента (Толщина 2px) */
    .inner-video-ring::before {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        padding: 2px; /* Это ширина светящейся линии */
        background: linear-gradient(135deg, rgba(99,102,241,0.9), rgba(6,182,212,0.6), rgba(236,72,153,0.6), rgba(99,102,241,0.9));
        background-size: 200% 200%;
        animation: elegant-led-flow 4s ease infinite;
        
        /* Mask скрывает заливку внутри, оставляя только "border" равный padding */
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        
        opacity: 0; /* ВЫКЛЮЧЕНО ПО УМОЛЧАНИЮ */
        transition: opacity 0.6s ease;
    }

    /* Состояние: ВКЛЮЧЕНО */
    body.leds-on .inner-video-ring::before {
        opacity: 0.9;
    }
</style>