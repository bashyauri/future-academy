<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 dark:from-neutral-900 dark:via-neutral-900 dark:to-neutral-800">
    {{-- Hero Section --}}
    <div class="relative overflow-hidden">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-5">
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <circle cx="20" cy="20" r="1" fill="currentColor"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)"/>
            </svg>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
            {{-- Header Text --}}
            <div class="text-center mb-12 md:mb-16">
                <flux:heading size="2xl" class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold mb-4 md:mb-6">
                    Welcome to <span class="text-blue-600 dark:text-blue-400">Future Academy</span>
                </flux:heading>
                <flux:text class="text-lg sm:text-xl md:text-2xl text-neutral-700 dark:text-neutral-300 max-w-3xl mx-auto leading-relaxed px-4">
                    Your comprehensive learning platform for JAMB, WAEC, and NECO exam preparation
                </flux:text>
            </div>

            {{-- Access Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 max-w-6xl mx-auto">
                {{-- Student Access --}}
                <div class="group">
                    <div class="relative rounded-2xl border-2 border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6 md:p-8 transition-all hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-xl transform hover:-translate-y-1">
                        {{-- Decorative Element --}}
                        <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/20 rounded-bl-full opacity-50"></div>

                        <div class="relative">
                            {{-- Icon --}}
                            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>

                            <flux:heading size="lg" class="text-xl md:text-2xl mb-3 font-bold">Students</flux:heading>
                            <flux:text class="text-sm md:text-base text-neutral-600 dark:text-neutral-400 mb-5 leading-relaxed">
                                Access video lessons, practice questions, and take mock exams to prepare for your exams.
                            </flux:text>

                            {{-- Features List --}}
                            <div class="space-y-2.5 mb-6">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Video Lessons</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Past Questions</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Mock Exams</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Progress Tracking</flux:text>
                                </div>
                            </div>

                            {{-- CTA Button --}}
                            <flux:button
                                href="{{ route('login', ['type' => 'student']) }}"
                                variant="primary"
                                class="w-full text-base min-h-[52px] font-semibold bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white"
                            >
                                Student Login
                            </flux:button>
                        </div>
                    </div>
                </div>

                {{-- Teacher Access --}}
                <div class="group">
                    <div class="relative rounded-2xl border-2 border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6 md:p-8 transition-all hover:border-purple-500 dark:hover:border-purple-500 hover:shadow-xl transform hover:-translate-y-1">
                        {{-- Decorative Element --}}
                        <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900/30 dark:to-purple-800/20 rounded-bl-full opacity-50"></div>

                        <div class="relative">
                            {{-- Icon --}}
                            <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>

                            <flux:heading size="lg" class="text-xl md:text-2xl mb-3 font-bold">Teachers</flux:heading>
                            <flux:text class="text-sm md:text-base text-neutral-600 dark:text-neutral-400 mb-5 leading-relaxed">
                                Upload content, create questions, and monitor student progress and performance.
                            </flux:text>

                            {{-- Features List --}}
                            <div class="space-y-2.5 mb-6">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Upload Videos</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Create Questions</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">View Student Progress</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Manage Content</flux:text>
                                </div>
                            </div>

                            {{-- CTA Button --}}
                            <flux:button
                                href="{{ route('login', ['type' => 'teacher']) }}"
                                variant="primary"
                                class="w-full text-base min-h-[52px] font-semibold bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white"
                            >
                                Teacher Login
                            </flux:button>
                        </div>
                    </div>
                </div>

                {{-- Parent Access --}}
                <div class="group">
                    <div class="relative rounded-2xl border-2 border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-6 md:p-8 transition-all hover:border-green-500 dark:hover:border-green-500 hover:shadow-xl transform hover:-translate-y-1">
                        {{-- Decorative Element --}}
                        <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-green-100 to-green-200 dark:from-green-900/30 dark:to-green-800/20 rounded-bl-full opacity-50"></div>

                        <div class="relative">
                            {{-- Icon --}}
                            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>

                            <flux:heading size="lg" class="text-xl md:text-2xl mb-3 font-bold">Parents</flux:heading>
                            <flux:text class="text-sm md:text-base text-neutral-600 dark:text-neutral-400 mb-5 leading-relaxed">
                                Monitor your children's learning progress and manage their educational journey.
                            </flux:text>

                            {{-- Features List --}}
                            <div class="space-y-2.5 mb-6">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Enroll Children</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Track Progress</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">View Performance</flux:text>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <flux:text class="text-sm md:text-base">Manage Enrollment</flux:text>
                                </div>
                            </div>

                            {{-- CTA Button --}}
                            <flux:button
                                href="{{ route('login', ['type' => 'parent']) }}"
                                variant="primary"
                                class="w-full text-base min-h-[52px] font-semibold bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white"
                            >
                                Parent Login
                            </flux:button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Features Section --}}
            <div class="mt-16 md:mt-24">
                <div class="text-center mb-10 md:mb-12">
                    <flux:heading size="xl" class="text-2xl md:text-3xl font-bold mb-3">Why Choose Future Academy?</flux:heading>
                    <flux:text class="text-base md:text-lg text-neutral-600 dark:text-neutral-400">Everything you need to excel in your exams</flux:text>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
                    {{-- Feature 1 --}}
                    <div class="text-center p-5 md:p-6 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 transition-all hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-lg">
                        <div class="w-14 h-14 md:w-16 md:h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 md:w-8 md:h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <flux:heading size="sm" class="font-semibold mb-2">Comprehensive Coverage</flux:heading>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">JAMB, WAEC & NECO past questions (5 years)</flux:text>
                    </div>

                    {{-- Feature 2 --}}
                    <div class="text-center p-5 md:p-6 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 transition-all hover:border-purple-500 dark:hover:border-purple-500 hover:shadow-lg">
                        <div class="w-14 h-14 md:w-16 md:h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 md:w-8 md:h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <flux:heading size="sm" class="font-semibold mb-2">Timed Mock Exams</flux:heading>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">Practice under exam conditions</flux:text>
                    </div>

                    {{-- Feature 3 --}}
                    <div class="text-center p-5 md:p-6 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 transition-all hover:border-green-500 dark:hover:border-green-500 hover:shadow-lg">
                        <div class="w-14 h-14 md:w-16 md:h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 md:w-8 md:h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <flux:heading size="sm" class="font-semibold mb-2">Progress Tracking</flux:heading>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">Monitor performance and improvement</flux:text>
                    </div>

                    {{-- Feature 4 --}}
                    <div class="text-center p-5 md:p-6 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 transition-all hover:border-orange-500 dark:hover:border-orange-500 hover:shadow-lg">
                        <div class="w-14 h-14 md:w-16 md:h-16 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 md:w-8 md:h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <flux:heading size="sm" class="font-semibold mb-2">Video Lessons</flux:heading>
                        <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">Learn from expert teachers</flux:text>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
