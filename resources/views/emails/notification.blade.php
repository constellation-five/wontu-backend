<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['title'] ?? 'Notification from Wontu' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #eaedf5;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #1c1b1f;
        }

        .wrapper {
            width: 100%;
            background-color: #eaedf5;
            padding: 40px 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fbfcff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .header {
            background-color: #dbe1f5;
            padding: 15px;
            text-align: center;
        }

        .header h1 {
            color: #fbfcff;
            margin: 0;
            font-size: 28px;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .content {
            padding: 40px 30px;
        }

        .title-row {
            width: 100%;
            margin-bottom: 20px;
        }

        .icon {
            display: inline-block;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #dbe1f5;
            color: #1c3399;
            text-align: center;
            line-height: 48px;
            font-size: 24px;
            font-weight: bold;
        }

        .title {
            font-size: 20px;
            font-weight: 600;
            color: #1c1b1f;
            margin: 0;
        }

        .description {
            font-size: 16px;
            color: #52555d;
            margin-bottom: 30px;
        }

        .action-button-container {
            text-align: center;
            margin-top: 30px;
        }

        .action-button {
            display: inline-block;
            background-color: #204ece;
            color: #fcfdff !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.2s;
        }

        .action-button:hover {
            background-color: #1c3399;
        }

        .footer {
            background-color: #f0f3fb;
            padding: 24px;
            text-align: center;
            font-size: 14px;
            color: #52555d;
            border-top: 1px solid #b3b8c7;
        }

        .footer p {
            margin: 0 0 10px 0;
        }

        .logo-placeholder {
            font-size: 32px;
            font-weight: 800;
            color: #fcfdff;
            letter-spacing: 2px;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <!-- Wontu Logo -->
                <a href="{{ env('FRONTEND_URL', 'http://localhost:4200') }}" style="text-decoration: none;">
                    <img src="https://wontu.site/assets/img/wontulogo.svg" alt="Wontu"
                        style="height: 100px; display: block; margin: 0 auto; border: 0;">
                </a>
            </div>

            <!-- Content -->
            <div class="content">
                <table border="0" cellpadding="0" cellspacing="0" class="title-row">
                    <tr>
                        <td valign="middle" style="width: 48px; padding-right: 16px;">
                            <!-- Icon Placeholder based on notification type -->
                            <div class="icon">
                                @if (isset($data['notification_type']))
                                    @if ($data['notification_type'] === 'success')
                                        ✓
                                    @elseif($data['notification_type'] === 'warning')
                                        !
                                    @elseif($data['notification_type'] === 'error')
                                        ✕
                                    @else
                                        i
                                    @endif
                                @else
                                    i
                                @endif
                            </div>
                        </td>
                        <td valign="middle">
                            <h2 class="title">{{ $data['title'] ?? 'Notification' }}</h2>
                        </td>
                    </tr>
                </table>

                <div class="description">
                    {{ $data['description'] ?? 'You have a new notification.' }}
                </div>

                @if (isset($data['action_url']))
                    <div class="action-button-container">
                        <a href="{{ env('FRONTEND_URL', 'http://localhost:4200') }}{{ $data['action_url'] }}"
                            class="action-button">
                            View Details
                        </a>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>&copy; {{ date('Y') }} Wontu. All rights reserved.</p>
                <p>You're receiving this email because of your activity on Wontu.</p>
            </div>
        </div>
    </div>
</body>

</html>
