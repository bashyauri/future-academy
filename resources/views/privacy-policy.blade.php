<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy — {{ config('app.name', 'Future Academy') }}</title>

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
                <h1 class="text-3xl font-bold tracking-tight text-neutral-900 sm:text-4xl">Privacy Policy</h1>
                <p class="mt-3 text-sm text-neutral-500">Last updated: {{ now()->format('F d, Y') }}</p>
            </div>

            {{-- Body --}}
            <div class="max-w-none space-y-8 text-neutral-700">

                <section>
                    <p class="text-base leading-relaxed">
                        Welcome to <strong>{{ config('app.name', 'Future Academy') }}</strong>. We are committed to protecting your personal information and your right to privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our platform. Please read this policy carefully. If you disagree with its terms, please discontinue use of the site.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">1. Information We Collect</h2>
                    <p class="mb-3 text-base leading-relaxed">We collect information you provide directly to us when you:</p>
                    <ul class="list-disc space-y-1 pl-6 text-base leading-relaxed">
                        <li>Create an account (name, email address, password)</li>
                        <li>Complete your student or guardian profile (class, subjects, exam goals)</li>
                        <li>Make a subscription payment (processed securely through Paystack — we do not store card details)</li>
                        <li>Contact us for support</li>
                    </ul>
                    <p class="mt-3 text-base leading-relaxed">We also automatically collect certain technical information when you use our platform, including:</p>
                    <ul class="list-disc space-y-1 pl-6 text-base leading-relaxed">
                        <li>Device and browser type</li>
                        <li>IP address and general location</li>
                        <li>Pages visited, quiz attempts, and lesson progress</li>
                        <li>Usage patterns and performance data</li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">2. How We Use Your Information</h2>
                    <p class="mb-3 text-base leading-relaxed">We use the information we collect to:</p>
                    <ul class="list-disc space-y-1 pl-6 text-base leading-relaxed">
                        <li>Create and manage your account</li>
                        <li>Deliver personalised lessons, quizzes, and practice sessions</li>
                        <li>Track academic progress and generate performance analytics</li>
                        <li>Process subscription payments and send receipts</li>
                        <li>Send important service notifications and updates</li>
                        <li>Respond to support enquiries</li>
                        <li>Improve and develop our platform</li>
                        <li>Comply with legal obligations</li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">3. Sharing Your Information</h2>
                    <p class="text-base leading-relaxed">
                        We do not sell, trade, or rent your personal information to third parties. We may share your data with trusted service providers who assist us in operating our platform (e.g., payment processors, video hosting, email delivery), under strict confidentiality agreements. We may also disclose information where required by law or to protect the rights and safety of our users.
                    </p>
                    <p class="mt-3 text-base leading-relaxed">
                        If a student account is linked to a guardian account, the guardian can view that student's progress and performance data within the platform.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">4. Data Security</h2>
                    <p class="text-base leading-relaxed">
                        We implement appropriate technical and organisational measures to protect your personal information against unauthorised access, alteration, disclosure, or destruction. All data is transmitted over encrypted HTTPS connections. Passwords are hashed and never stored in plain text. Payment processing is handled by <strong>Paystack</strong>, a PCI-DSS compliant payment processor.
                    </p>
                    <p class="mt-3 text-base leading-relaxed">
                        While we strive to protect your data, no method of transmission over the internet is 100% secure. We cannot guarantee absolute security.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">5. Children's Privacy</h2>
                    <p class="text-base leading-relaxed">
                        {{ config('app.name', 'Future Academy') }} is designed for secondary school students, which may include individuals under 18. We collect only the minimum information necessary to provide the educational service. Student accounts must be registered or supervised by a parent or guardian. We do not knowingly collect unnecessary personal data from minors. If you believe we have inadvertently collected personal data from a child without proper consent, please contact us immediately.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">6. Cookies</h2>
                    <p class="text-base leading-relaxed">
                        We use cookies and similar tracking technologies to maintain your session, remember your preferences, and analyse platform usage. You can configure your browser to refuse cookies, but doing so may limit some functionality of the platform.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">7. Your Rights</h2>
                    <p class="mb-3 text-base leading-relaxed">You have the right to:</p>
                    <ul class="list-disc space-y-1 pl-6 text-base leading-relaxed">
                        <li>Access the personal data we hold about you</li>
                        <li>Request correction of inaccurate data</li>
                        <li>Request deletion of your account and associated data</li>
                        <li>Withdraw consent where processing is based on consent</li>
                        <li>Lodge a complaint with a relevant data protection authority</li>
                    </ul>
                    <p class="mt-3 text-base leading-relaxed">
                        To exercise any of these rights, please contact us using the details below.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">8. Data Retention</h2>
                    <p class="text-base leading-relaxed">
                        We retain your personal data for as long as your account is active or as needed to provide services. If you delete your account, we will remove your personal information within a reasonable period, except where retention is required by law.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">9. Third-Party Links</h2>
                    <p class="text-base leading-relaxed">
                        Our platform may contain links to third-party websites. We are not responsible for the privacy practices of those sites and encourage you to review their privacy policies.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">10. Changes to This Policy</h2>
                    <p class="text-base leading-relaxed">
                        We may update this Privacy Policy from time to time. We will notify you of significant changes by posting the new policy on this page and updating the "Last updated" date. Your continued use of the platform after changes are posted constitutes your acceptance of the revised policy.
                    </p>
                </section>

                <section>
                    <h2 class="mb-3 text-xl font-semibold text-neutral-900">11. Contact Us</h2>
                    <p class="text-base leading-relaxed">
                        If you have any questions, concerns, or requests regarding this Privacy Policy, please contact us at:
                    </p>
                    <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 p-5">
                        <p class="font-semibold text-neutral-900">{{ config('app.name', 'Future Academy') }}</p>
                        <p class="mt-1 text-sm text-neutral-600">
                            Email:
                            <a href="mailto:{{ config('mail.from.address', 'support@futureacademy.com') }}" class="text-blue-600 underline underline-offset-2">
                                {{ config('mail.from.address', 'support@futureacademy.com') }}
                            </a>
                        </p>
                        <p class="text-sm text-neutral-600">
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
        </p>
    </div>

</body>
</html>
