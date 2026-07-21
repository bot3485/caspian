<style>
    /* --- ЭКСКЛЮЗИВНЫЙ ДВИЖОК LED ДЛЯ ВИДЕО (Perfect Fit) --- */
    .led-video-frame {
        position: relative;
        z-index: 1;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
    }
    
    .led-video-frame::before {
        content: '';
        position: absolute;
        top: 50%; left: 50%;
        width: 150vmax; height: 150vmax;
        background: conic-gradient(from 0deg, transparent 0%, #6366f1 10%, #06b6d4 25%, #ec4899 40%, transparent 50%);
        transform: translate(-50%, -50%);
        animation: led-rotate-video 4s linear infinite;
        z-index: -1;
        opacity: 0;
        transition: opacity 1s ease;
        pointer-events: none;
    }
    
    body.leds-on .led-video-frame::before {
        opacity: 1;
    }
    
    .led-video-content {
        position: absolute;
        inset: 3px;
        border-radius: calc(2.5rem - 3px);
        background: #020202;
        overflow: hidden;
        z-index: 2;
        box-shadow: inset 0 0 30px rgba(0,0,0,0.8);
    }
    
    .led-video-frame::after {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: 2.5rem;
        box-shadow: 0 0 40px -10px rgba(99,102,241,0);
        z-index: -2;
        transition: box-shadow 1s ease;
        pointer-events: none;
    }
    
    body.leds-on .led-video-frame::after {
        box-shadow: 0 0 50px -10px rgba(99,102,241,0.5);
    }
    
    @keyframes led-rotate-video {
        from { transform: translate(-50%, -50%) rotate(0deg); }
        to { transform: translate(-50%, -50%) rotate(360deg); }
    }

    /* --- ОСТАЛЬНОЙ CSS (Глитчи и Блиц) --- */
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

    @keyframes led-snake-flow {
        0% {
            filter: hue-rotate(0deg);
            background-position: 0% 50%;
        }
        50% {
            filter: hue-rotate(180deg);
            background-position: 100% 50%;
        }
        100% {
            filter: hue-rotate(360deg);
            background-position: 0% 50%;
        }
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
</style>