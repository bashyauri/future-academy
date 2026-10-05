<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - {{ config('app.name', 'Future Academy') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 text-red-600 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Something went wrong</h1>
                <p class="text-lg text-gray-600 mb-6">
                    We're sorry, but something unexpected happened on our end. Our team has been notified and is working to fix the issue.
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="font-semibold text-gray-900 mb-3">What you can do:</h2>
                <ul class="text-left text-gray-600 space-y-2">
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 text-green-600 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>Try refreshing the page</span>
                    </li>
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 text-green-600 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>Check your internet connection</span>
                    </li>
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 text-green-600 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        <span>Try again in a few minutes</span>
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

            <p class="mt-6 text-sm text-gray-500">
                If the problem persists, please contact our support team.
            </p>
        </div>
    </div>
</body>
</html>
