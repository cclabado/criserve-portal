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
        .input {
            @apply border border-gray-300 rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 outline-none;
        }

        .section {
            @apply bg-white rounded-2xl shadow p-6 mb-6;
        }

        .label {
            @apply text-sm font-semibold text-gray-600;
        }
        .primary-bg {
            background-color: #234E70;
        }
        .primary-text {
            color: #234E70;
        }
        .primary-light {
            background-color: #E6EEF5;
        }
        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .input {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
            width: 100%;
            outline: none;
        }
        .input:focus {
            border-color: #234E70;
        }
        .btn-primary {
            background: #234E70;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
        }
        .btn-primary:hover {
            background: #1d3f5c;
        }
        .btn-secondary {
            background: #E6EEF5;
            color: #234E70;
            padding: 12px 20px;
            border-radius: 12px;
        }
        .step-active {
            background: #234E70;
            color: white;
        }
        .step-inactive {
            background: #f3f4f6;
            color: #6b7280;
        }
    </style>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
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