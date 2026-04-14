<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrIServe Portal</title>

    <!-- FONTS -->
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <!-- TAILWIND -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-gradient {
            background: linear-gradient(135deg, #003857 0%, #1b4f72 100%);
        }
        .soft-shadow {
            box-shadow: 0 24px 40px -10px rgba(0, 56, 87, 0.06);
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800">

<!-- NAVBAR -->
<nav class="fixed top-0 w-full bg-white/80 backdrop-blur-md shadow-sm z-50">
    <div class="max-w-7xl mx-auto flex justify-between items-center px-8 py-4">

        <div class="text-xl font-bold text-blue-900">CrIServe Portal</div>

        <div class="hidden md:flex gap-8">
            <a class="text-blue-700 font-semibold border-b-2 border-blue-700">How it Works</a>
            <a class="text-gray-600 hover:text-blue-900">Our Impact</a>
            <a class="text-gray-600 hover:text-blue-900">Partners</a>
            <a class="text-gray-600 hover:text-blue-900">Resources</a>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('login') }}" class="px-4 py-2 text-gray-600 font-semibold hover:bg-gray-100 rounded-lg">
                Login
            </a>

            <a href="/client/application"
               class="px-5 py-2 bg-blue-900 text-white font-bold rounded-lg">
                Apply Now
            </a>
        </div>
    </div>
</nav>

<main class="pt-24">

<!-- HERO -->
<section class="px-8 py-24">
<div class="max-w-7xl mx-auto grid md:grid-cols-2 gap-12 items-center">

<div class="space-y-8">

<span class="text-xs font-bold uppercase tracking-widest text-blue-700">
    Official Government Portal
</span>

<h1 class="text-5xl md:text-6xl font-extrabold text-blue-900 leading-tight">
    Bridging Support in <br>
    <span class="text-blue-700">Times of Need</span>
</h1>

<p class="text-lg text-gray-600">
    The Department of Social Welfare and Development's (DSWD) commitment to digital-first crisis intervention.
</p>

<div class="flex gap-4">
    <a href="/client/application"
       class="px-6 py-3 bg-blue-900 text-white rounded-lg font-bold">
        Apply for Assistance
    </a>

    <button class="px-6 py-3 bg-gray-200 rounded-lg font-bold">
        Learn More
    </button>
</div>

</div>

<div>
<img src="https://lh3.googleusercontent.com/aida-public/AB6AXuBqDsz-bdjKE28fFlXYV6qcPxAVtpniO4T_OBLPV0FDlLbpCJfIsTNv5DwB8xK_7EHb5VhfcYYL-9xnzonZ82ghcokpcrZTMaQtMp6smoljjzZwULg1xsomLci2SbAMed1ML7Rkrmqp2_H2xII_IEHt_E9Le0m5r-OzvJeafqnikhkXlbg-moQ_-DNMPO2ygZfPztFDnCT0LE-16p3969OBTetIdw2j_V7hK2BZLi3dP8XHY0bkiLj2SPhbdFebkzA-Yu9IYanb9FQ"
     class="rounded-xl shadow w-full h-[450px] object-cover">
</div>

</div>
</section>

<!-- SERVICES -->
<section class="px-8 py-20 bg-gray-100">
<div class="max-w-7xl mx-auto">

<h2 class="text-3xl font-bold text-blue-900 mb-10">
    Comprehensive Support Channels
</h2>

<div class="grid md:grid-cols-3 gap-6">

<div class="bg-white p-6 rounded-xl shadow">
<h3 class="font-bold text-lg">Financial Assistance</h3>
<p class="text-gray-600 mt-2">Immediate monetary support for crises.</p>
</div>

<div class="bg-white p-6 rounded-xl shadow">
<h3 class="font-bold text-lg">Medical Support</h3>
<p class="text-gray-600 mt-2">Hospitalization and medicines support.</p>
</div>

<div class="bg-white p-6 rounded-xl shadow">
<h3 class="font-bold text-lg">Psychosocial Support</h3>
<p class="text-gray-600 mt-2">Counseling and emotional support.</p>
</div>

</div>

</div>
</section>

<!-- CTA -->
<section class="px-8 py-24 text-center">
<h2 class="text-4xl font-bold text-blue-900 mb-6">
    Ready to get started?
</h2>

<a href="/client/application"
   class="px-8 py-4 bg-blue-900 text-white rounded-lg font-bold">
    Apply Now
</a>
</section>

</main>

</body>
</html>