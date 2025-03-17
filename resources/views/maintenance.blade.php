<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - Lendworks</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-900 text-slate-100">
    <div class="min-h-screen flex flex-col items-center justify-center p-4 relative overflow-hidden">
        <!-- Background gradient -->
        <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900"></div>
        
        <!-- Content -->
        <div class="relative z-10 max-w-lg w-full text-center space-y-8">
            <!-- Logo or Icon -->
            <div class="mb-8 animate-pulse-slow">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </div>

            <div class="space-y-4">
                <h1 class="text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-500 to-blue-700">
                    Under Maintenance
                </h1>
                <p class="text-xl text-slate-400">
                    We're making things better
                </p>
            </div>

            <div class="bg-slate-800/50 backdrop-blur-sm p-8 rounded-2xl border border-slate-700/50 shadow-xl">
                <p class="text-slate-300 text-lg leading-relaxed">
                    Our system is currently undergoing scheduled maintenance to improve your experience.<br>
                    We'll be back online shortly.
                </p>
            </div>

            <!-- Status indicator -->
            <div class="flex items-center justify-center gap-2 text-blue-500">
                <div class="w-2 h-2 bg-current rounded-full animate-pulse"></div>
                <span class="text-sm">System maintenance in progress...</span>
            </div>

            <div class="text-sm text-slate-500">
                © {{ date('Y') }} Lendworks. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
