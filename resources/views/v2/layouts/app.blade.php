<!doctype html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · omsetaPOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="v2-app h-full text-slate-800 antialiased">
<div x-data="{ sidebar: false }" class="min-h-full">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full bg-white border-r border-slate-200 transition-transform lg:translate-x-0"
           :class="sidebar && 'translate-x-0'">
        <div class="flex h-16 items-center gap-2 px-5 border-b border-slate-200">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-indigo-600 text-white font-bold">o</span>
            <span class="font-bold text-slate-900">omsetaPOS</span>
            <span class="ml-auto text-xs font-medium text-slate-400">v2</span>
        </div>
        <nav class="h-[calc(100%-4rem)] overflow-y-auto px-3 py-4 text-sm">
            @include('v2.layouts.nav')
        </nav>
    </aside>

    {{-- Overlay (mobile) --}}
    <div x-show="sidebar" x-cloak @click="sidebar = false" class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"></div>

    {{-- Main --}}
    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-6">
            <button @click="sidebar = !sidebar" class="lg:hidden rounded-lg p-2 hover:bg-slate-100" aria-label="Menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
            </button>
            <h1 class="text-base font-semibold text-slate-900">@yield('heading', 'Dashboard')</h1>
            <div class="ml-auto flex items-center gap-3" x-data="{ open: false }">
                <span class="hidden text-sm text-slate-500 sm:block">{{ auth()->user()?->name }}</span>
                <div class="relative">
                    <button @click="open = !open" class="grid h-9 w-9 place-items-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 mt-2 w-44 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                        <form method="POST" action="{{ route('v2.logout') }}">
                            @csrf
                            <button class="block w-full px-4 py-2 text-left text-sm text-rose-600 hover:bg-slate-50">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        @if (session('status'))
            <div class="mx-4 mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700 sm:mx-6">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mx-4 mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-700 sm:mx-6">{{ $errors->first() }}</div>
        @endif

        <main class="p-4 sm:p-6">
            @yield('content')
        </main>
    </div>
</div>
<style>[x-cloak]{display:none!important}</style>
</body>
</html>
