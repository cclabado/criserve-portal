<!DOCTYPE html>
<html>
<head>
    <title>CrIServe</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --field-border: #d4dde6;
            --field-border-strong: #234E70;
            --field-bg: #ffffff;
            --field-bg-muted: #f8fafc;
            --field-text: #0f172a;
            --field-placeholder: #94a3b8;
            --field-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            --field-focus: 0 0 0 4px rgba(35, 78, 112, 0.12);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 1;
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

        .input,
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="number"],
        input[type="date"],
        input[type="datetime-local"],
        input[type="tel"],
        select,
        textarea {
            appearance: none;
            border: 1px solid var(--field-border);
            background: var(--field-bg);
            color: var(--field-text);
            border-radius: 14px;
            padding: 12px 14px;
            width: 100%;
            outline: none;
            box-shadow: var(--field-shadow);
            transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
            font-size: 14px;
            line-height: 1.4;
        }

        .input::placeholder,
        input::placeholder,
        textarea::placeholder {
            color: var(--field-placeholder);
        }

        .input:focus,
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        input[type="datetime-local"]:focus,
        input[type="tel"]:focus,
        select:focus,
        textarea:focus {
            border-color: var(--field-border-strong);
            box-shadow: var(--field-focus);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        select {
            padding-right: 46px;
            background:
                linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            background-image:
                linear-gradient(45deg, transparent 50%, #64748b 50%),
                linear-gradient(135deg, #64748b 50%, transparent 50%);
            background-position:
                calc(100% - 19px) calc(50% - 4px),
                calc(100% - 13px) calc(50% - 4px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            cursor: pointer;
        }

        select:hover {
            border-color: #b8c7d6;
            background:
                linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%);
        }

        .input[readonly],
        input[readonly],
        textarea[readonly] {
            background: var(--field-bg-muted);
        }

        .input:disabled,
        input:disabled,
        select:disabled,
        textarea:disabled {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        select option {
            color: var(--field-text);
            background: #ffffff;
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
