<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - {{ config('app.name', 'Future Academy') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-yellow-100 text-yellow-600 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Page not found</h1>
                <p class="text-lg text-gray-600 mb-6">
                    The page you're looking for doesn't exist or has been moved.
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="font-semibold text-gray-900 mb-3">What you can do:</h2>
                <ul class="text-left text-gray-600 space-y-2">
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 text-green-600 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>Check the URL for typos</span>
                    </li>
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 text-green-600 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>Use the navigation menu to find what you need</span>
                    </li>
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 text-green-600 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>Go back to the previous page</span>
                    </li>
                </ul>
            </div>

            <div class="space-y-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        Go to Login
                    </a>
                @endauth

                <a href="{{ route('home') }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Go to Homepage
                </a>
            </div>
        </div>
    </div>
</body>
</html>
