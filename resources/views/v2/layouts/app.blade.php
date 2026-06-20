<!doctype html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · omsetaPOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
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
    <div class="v2-main lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-6">
            <button @click="sidebar = !sidebar" class="lg:hidden rounded-lg p-2 hover:bg-slate-100" aria-label="Menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/></svg>
            </button>
            <h1 class="text-base font-semibold text-slate-900">@yield('heading', 'Dashboard')</h1>
            @php($v2Stores = auth()->user()?->accessibleStores() ?? collect())
            @php($v2Active = \App\Support\ActiveStore::current())
            <div class="ml-auto flex items-center gap-3">
                @if ($v2Stores->count() > 1)
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"/></svg>
                            <span class="max-w-[10rem] truncate">{{ $v2Active?->name ?? 'Pilih Outlet' }}</span>
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-30 mt-2 w-56 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                            @foreach ($v2Stores as $s)
                                <form method="POST" action="{{ route('v2.stores.switch') }}">
                                    @csrf
                                    <input type="hidden" name="store_id" value="{{ $s->id }}">
                                    <button class="flex w-full items-center justify-between px-4 py-2 text-left text-sm hover:bg-slate-50 {{ $v2Active?->id === $s->id ? 'font-semibold text-indigo-700' : 'text-slate-600' }}">
                                        {{ $s->name }}
                                        @if ($v2Active?->id === $s->id)<span>✓</span>@endif
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @elseif ($v2Active)
                    <span class="hidden text-sm text-slate-500 sm:block">{{ $v2Active->name }}</span>
                @endif
                <span class="hidden text-sm text-slate-500 sm:block">{{ auth()->user()?->name }}</span>
                <div class="relative" x-data="{ open: false }">
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
        @if (session('error'))
            <div class="mx-4 mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-700 sm:mx-6">{{ session('error') }}</div>
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
@stack('scripts')
</body>
</html>
