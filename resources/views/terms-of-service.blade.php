<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service — {{ config('app.name', 'Future Academy') }}</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/icons/logo-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/icons/logo-16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/logo-180.png') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @php($viteManifest = public_path('build/manifest.json'))
    @if (file_exists($viteManifest))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 font-sans text-neutral-900">

    {{-- Navigation --}}
    <div class="sticky top-0 z-40 border-b border-blue-100/80 bg-white/90 backdrop-blur-md">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                <img
                    src="{{ asset('images/icons/logo-64.png') }}"
                    alt="{{ config('app.name', 'Future Academy') }} logo"
                    class="size-9 rounded-md object-contain"
                />
                <span class="text-sm font-bold tracking-wide text-neutral-900 sm:text-base">
                    {{ config('app.name', 'Future Academy') }}
                </span>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <a
                    href="{{ route('login') }}"
                    class="rounded-lg px-3 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-100 hover:text-neutral-900 sm:px-4"
                >
                    Log in
                </a>
                <a
                    href="{{ route('register') }}"
                    class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 sm:px-4"
                >
                    Get Started
                </a>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-neutral-200 bg-white px-8 py-10 shadow-sm sm:px-12 sm:py-14">

            {{-- Header --}}
            <div class="mb-10 border-b border-neutral-100 pb-8">
                <h1 class="text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">Terms of Service & Refund Policy</h1>
                <p class="mt-3 text-sm text-neutral-500">Last updated: {{ now()->format('F d, Y') }}</p>
            </div>

            {{-- Body --}}
            <div class="max-w-none space-y-8 text-neutral-700">

                <section>
                    <p class="text-base leading-relaxed">
                        Welcome to <strong>{{ config('app.name', 'Future Academy') }}</strong>, an educational platform operated by <strong>Ramenshuka Global Resources (RMS GL)</strong> (RC: 1919844). These Terms of Service govern your access to and use of our website, web application, mobile application, and related learning services. By registering, accessing, or subscribing to our services, you agree to be bound by these terms. If you do not agree to these terms, please do not use the platform.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">1. Acceptance of Terms</h2>
                    <p class="text-base leading-relaxed">
                        By accessing or using {{ config('app.name', 'Future Academy') }}, whether via web browser or our mobile application on Google Play Store, you confirm that you have read, understood, and agreed to these Terms of Service and our Privacy Policy. If you are under 18 years old, you agree that your parent or legal guardian has reviewed and accepted these terms on your behalf.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">2. Educational Content & Permitted Use</h2>
                    <p class="text-base leading-relaxed">
                        All materials made available on {{ config('app.name', 'Future Academy') }}—including JAMB, WAEC, and NECO past examination questions, timed CBT mock examinations, practice quizzes, curriculum video lessons, topic summaries, and detailed explanations—are provided solely for personal, non-commercial educational study and exam preparation.
                    </p>
                    <p class="mt-3 text-base leading-relaxed">You may not:</p>
                    <ul class="list-disc space-y-1 pl-6 text-base leading-relaxed">
                        <li>Scrape, download in bulk, redistribute, sell, or commercially exploit any content from the platform;</li>
                        <li>Reverse-engineer, decompile, or tamper with the exam delivery or scoring software;</li>
                        <li>Share account login credentials with unauthorized individuals or institutions.</li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">3. User Accounts & Responsibilities</h2>
                    <p class="text-base leading-relaxed">
                        To access personalized features, study streaks, and progress tracking, you must create an account with accurate and up-to-date information. You are solely responsible for maintaining the confidentiality of your credentials and for all activities conducted under your account. You agree to notify us immediately if you suspect unauthorized access.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">4. Subscriptions, Fees & Billing</h2>
                    <p class="text-base leading-relaxed">
                        While basic introductory lessons and initial practice sessions may be offered free of charge or on trial, full access to our extensive question archives, timed mock exams, video lessons, and analytics requires an active subscription.
                    </p>
                    <p class="mt-3 text-base leading-relaxed">
                        Subscription fees and duration (monthly or yearly) are clearly disclosed prior to payment. Subscriptions are billed in accordance with the selected plan.
                    </p>
                </section>

                <section id="refund-policy">
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">5. Refund & Cancellation Policy</h2>
                    <p class="text-base leading-relaxed">
                        Due to the immediate digital delivery and instantaneous access to our proprietary curriculum, question banks, video lessons, and automated examination systems, <strong>all subscription fees are generally non-refundable once activated</strong>.
                    </p>
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-5">
                        <h3 class="font-semibold text-amber-900">Conditions Under Which Refunds Are Made:</h3>
                        <p class="mt-2 text-sm leading-relaxed text-amber-900">
                            We offer refunds <strong>only</strong> after technical errors, platform bugs, or major service disruptions directly originating from our platform prevent you from accessing your paid subscription services, <strong>AND</strong> our technical support team is unable to solve or rectify the problem within a reasonable timeframe after you notify us.
                        </p>
                    </div>

                    <p class="mt-4 text-base leading-relaxed"><strong>Non-Refundable Scenarios:</strong> Refunds will NOT be issued under any of the following circumstances:</p>
                    <ul class="list-disc space-y-1 pl-6 text-base leading-relaxed">
                        <li>Change of mind, lack of usage, or deciding not to prepare for an examination after subscribing;</li>
                        <li>Failure of the user to log in or use the platform during their active subscription period;</li>
                        <li>Inability to access content due to user device incompatibility, lack of internet connection, power outage, or third-party telecommunication network failures;</li>
                        <li>Academic performance, examination grades, or university admission outcomes.</li>
                    </ul>

                    <div class="mt-4 rounded-xl border border-neutral-200 bg-neutral-50 p-5 text-sm leading-relaxed text-neutral-700">
                        <p class="font-semibold text-neutral-900">How to Submit a Refund Request:</p>
                        <p class="mt-1">
                            If you believe you qualify for a refund due to an unresolved technical error from our service, you must contact our support desk within <strong>seven (7) days</strong> of payment:
                        </p>
                        <ul class="mt-2 list-disc pl-5 space-y-1">
                            <li><strong>Email:</strong> Send a message to <a href="mailto:futureacademy-rm@gmail.com" class="text-blue-600 underline">futureacademy-rm@gmail.com</a> with the subject line <em>"Refund Request - [Your Registered Email]"</em>.</li>
                            <li><strong>WhatsApp Support:</strong> Message our official helpdesk at <a href="https://wa.me/2347071100037" class="text-blue-600 underline">+234 707 110 0037</a>.</li>
                            <li><strong>Required Details:</strong> Your registered email, proof of transaction (Paystack reference or payment receipt), and clear descriptions/screenshots of the technical error encountered.</li>
                        </ul>
                        <p class="mt-2 text-xs text-neutral-500">
                            Our technical team will review the issue and attempt a resolution. If the problem cannot be solved, an approved refund will be credited back to your original payment channel within 5 to 10 business days.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">6. Account Deletion & Data Rights</h2>
                    <p class="text-base leading-relaxed">
                        You have the right to permanently delete your account and associated personal data at any time. Mobile application users can delete their account directly within the app under <strong>Settings &gt; Delete Account</strong>. You may also submit a deletion request by contacting our support team. Upon deletion, your personal details, exam records, and progress history are permanently purged in compliance with applicable data protection regulations.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">7. Intellectual Property</h2>
                    <p class="text-base leading-relaxed">
                        {{ config('app.name', 'Future Academy') }}, its logo, software code, user interface, questions database, and all multimedia content are the exclusive intellectual property of <strong>Ramenshuka Global Resources (RMS GL)</strong> (RC: 1919844) and are protected by Nigerian and international intellectual property laws.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">8. Limitation of Liability</h2>
                    <p class="text-base leading-relaxed">
                        While we strive to provide the most accurate examination practice questions, explanations, and reliable uptime, {{ config('app.name', 'Future Academy') }} provides its services on an "as is" and "as available" basis. We do not warrant that the service will be uninterrupted or error-free, and we assume no liability for indirect, incidental, or consequential damages resulting from your use of the platform.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">9. Governing Law & Jurisdiction</h2>
                    <p class="text-base leading-relaxed">
                        These Terms of Service are governed by and construed in accordance with the laws of the Federal Republic of Nigeria. Any disputes arising in connection with these terms shall be subject to the exclusive jurisdiction of the competent courts of Nigeria.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">10. Contact Us</h2>
                    <p class="text-base leading-relaxed">
                        If you have questions, feedback, or require assistance regarding these Terms of Service or our Refund Policy, please contact us:
                    </p>
                    <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 p-5">
                        <p class="font-semibold text-neutral-900">{{ config('app.name', 'Future Academy') }} (RMS GL)</p>
                        <p class="mt-1 text-sm text-neutral-600">Company Registration: RC: 1919844</p>
                        <p class="mt-1 text-sm text-neutral-600">
                            Email:
                            <a href="mailto:futureacademy-rm@gmail.com" class="text-blue-600 underline underline-offset-2">
                                futureacademy-rm@gmail.com
                            </a>
                            &nbsp;&bull;&nbsp;
                            <a href="mailto:noreply@futureacademy-rm.com" class="text-blue-600 underline underline-offset-2">
                                noreply@futureacademy-rm.com
                            </a>
                        </p>
                        <p class="mt-1 text-sm text-neutral-600">
                            WhatsApp Helpdesk:
                            <a href="https://wa.me/2347071100037" class="text-blue-600 underline underline-offset-2">
                                +234 707 110 0037
                            </a>
                        </p>
                        <p class="mt-1 text-sm text-neutral-600">
                            Website:
                            <a href="{{ route('home') }}" class="text-blue-600 underline underline-offset-2">
                                {{ config('app.url') }}
                            </a>
                        </p>
                    </div>
                </section>

            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="mx-auto max-w-4xl px-4 py-8 text-center text-sm text-neutral-500 sm:px-6 lg:px-8">
        <p>
            &copy; {{ now()->year }} {{ config('app.name', 'Future Academy') }}. All rights reserved.
            &nbsp;&bull;&nbsp;
            <a href="{{ route('home') }}" class="underline underline-offset-2 hover:text-neutral-700">Home</a>
            &nbsp;&bull;&nbsp;
            <a href="{{ route('privacy-policy') }}" class="underline underline-offset-2 hover:text-neutral-700">Privacy Policy</a>
        </p>
    </div>

</body>
</html>

