<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 p-8 font-sans text-gray-900">
    <main class="mx-auto max-w-2xl space-y-4">
        <h1 class="text-2xl font-semibold">{{ config('app.name') }}</h1>
        <button id="ping" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
            GET /api/v1/ping
        </button>
        <pre id="output" class="rounded bg-gray-900 p-4 text-sm text-green-300"></pre>
    </main>
</body>
</html>
