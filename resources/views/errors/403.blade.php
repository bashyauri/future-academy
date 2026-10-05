<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - {{ config('app.name', 'Future Academy') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-orange-100 text-orange-600 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Access denied</h1>
                <p class="text-lg text-gray-600 mb-6">
                    You don't have permission to access this page or resource.
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h2 class="font-semibold text-gray-900 mb-3">This might happen because:</h2>
                <ul class="text-left text-gray-600 space-y-2">
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 text-orange-600 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <span>You need to log in to access this page</span>
                    </li>
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 text-orange-600 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <span>Your account doesn't have the required permissions</span>
                    </li>
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2 mt-0.5 text-orange-600 flex-shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <span>Your subscription may have expired</span>
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
                        Log In
                    </a>
                @endauth

                <a href="{{ route('home') }}" class="inline-flex items-center justify-center w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    Go to Homepage
                </a>
            </div>

            <p class="mt-6 text-sm text-gray-500">
                If you believe this is an error, please contact support.
            </p>
        </div>
    </div>
</body>
</html>
