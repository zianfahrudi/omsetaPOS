<!doctype html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · omsetaPOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
<div class="flex min-h-full items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="mb-6 flex items-center justify-center gap-2">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-600 text-white text-lg font-bold">o</span>
            <span class="text-xl font-bold text-slate-900">omsetaPOS</span>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="mb-1 text-lg font-semibold text-slate-900">Masuk ke Admin</h1>
            <p class="mb-5 text-sm text-slate-500">Gunakan akun terdaftar Anda.</p>

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('v2.login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600"> Ingat saya
                </label>
                <button class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Masuk
                </button>
            </form>
        </div>
        <p class="mt-4 text-center text-xs text-slate-400">omsetaPOS Admin v2</p>
    </div>
</div>
</body>
</html>
