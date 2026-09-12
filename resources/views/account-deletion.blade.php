<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Delete Future Academy Account</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;
            background: #f7f9f8;
            color: #1f2937;
            line-height: 1.7;
        }

        .header {
            background: #087f5b;
            color: white;
            padding: 45px 20px;
        }

        .header-container {
            max-width: 800px;
            margin: auto;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 34px;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .card {
            background: white;
            padding: 32px;
            border-radius: 14px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
        }

        h2 {
            color: #087f5b;
        }

        .notice {
            background: #ecfdf5;
            border-left: 4px solid #087f5b;
            padding: 18px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .button {
            display: inline-block;
            background: #087f5b;
            color: white;
            padding: 13px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 10px;
        }

        a {
            color: #087f5b;
        }
    </style>
</head>

<body>

<header class="header">
    <div class="header-container">
        <h1>Delete Your Future Academy Account</h1>
        <p>Account and personal data deletion request</p>
    </div>
</header>

<main class="container">

    <div class="card">

        <h2>Request Account Deletion</h2>

        <p>
            If you no longer wish to use Future Academy, you can request
            deletion of your account and associated personal information.
        </p>

        <div class="notice">
            <strong>
                Account deletion is permanent and may not be reversible.
            </strong>
        </div>

        <h2>How to Request Deletion</h2>

        <p>
            Please send an account deletion request from the email address
            associated with your Future Academy account.
        </p>

        <p>
            Include:
        </p>

        <ul>
            <li>Your full name</li>
            <li>The email address associated with your account</li>
            <li>Your student/account ID, if available</li>
            <li>The words "Account Deletion Request" in the subject</li>
        </ul>

        <a
            class="button"
            href="mailto:support@futureacademy-rm.com?subject=Future%20Academy%20Account%20Deletion%20Request"
        >
            Request Account Deletion
        </a>

        <h2>What Happens After Your Request?</h2>

        <p>
            We will review your request and take reasonable steps to
            delete the personal information associated with your account.
        </p>

        <p>
            Certain information may be retained where required by law,
            for legitimate security purposes, fraud prevention,
            financial recordkeeping, dispute resolution, or other
            lawful obligations.
        </p>

        <h2>Questions</h2>

        <p>
            For questions regarding account deletion or privacy, please
            contact:
        </p>

        <p>
            <strong>Future Academy</strong><br>

            <a href="mailto:support@futureacademy-rm.com">
                support@futureacademy-rm.com
            </a>
        </p>

        <p>
            You can also review our
            <a href="{{ url('/privacy-policy') }}">
                Privacy Policy
            </a>.
        </p>

    </div>

</main>

</body>
</html>