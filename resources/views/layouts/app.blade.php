<!DOCTYPE html>
<html>
<head>
    <title>CrIServe</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .material-symbols-outlined {
        font-variation-settings: 'FILL' 1;
        }
    </style>
</head>
<body class="bg-surface text-on-surface">

    @include('components.sidebar')

    <div class="ml-64">
        @include('components.navbar')

        <main class="p-6">
            @yield('content')
        </main>
    </div>

</body>
</html>