<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Caspian — Authorization</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('roulette.jpg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#020202] text-white antialiased font-sans">
    <div class="min-h-screen flex flex-col items-center justify-center p-6 relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-brand-indigo/10 blur-[120px] rounded-full"></div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-purple-600/10 blur-[120px] rounded-full"></div>

        <div class="w-full max-w-[440px] relative z-10">
            <div class="flex justify-center mb-12">
                <div class="w-20 h-20 bg-brand-indigo rounded-[2rem] flex items-center justify-center shadow-2xl shadow-brand-indigo/20">
                    <img src="{{ asset('roulette.jpg') }}" class="w-12 h-12 rounded-xl" alt="Logo">
                </div>
            </div>
            
            <div class="caspian-glass rounded-[3rem] p-10 md:p-14 shadow-2xl border-white/10">
                {{ $slot }}
            </div>

            <p class="mt-10 text-center text-[10px] font-black uppercase tracking-[0.4em] text-gray-600">
                Powered by Caspian OS 3.0
            </p>
        </div>
    </div>
</body>
</html>