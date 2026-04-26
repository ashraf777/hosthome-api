<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { background: #4f46e5; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Message</h1>
        </div>
        <div class="content">
            <p>Hi {{ $messageData->booking->guest->first_name }},</p>
            <p>You have received a new message regarding your stay at <strong>{{ $messageData->booking->property->name }}</strong>:</p>
            
            <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #4f46e5; margin: 20px 0;">
                {!! nl2br(e($messageData->content)) !!}
            </div>

            <p>To reply or view your booking details, please visit your guest portal:</p>
            <p style="text-align: center;">
                <a href="https://hosthome.vercel.app/guest/booking/{{ $messageData->booking->guest_portal_token }}" 
                   style="background: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Open Guest Portal
                </a>
            </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} HostHome. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
