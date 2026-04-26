<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { background: #059669; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .booking-details { border: 1px solid #eee; border-radius: 5px; padding: 15px; margin: 20px 0; background: #fafafa; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Booking Confirmed!</h1>
        </div>
        <div class="content">
            <p>Hi {{ $booking->guest->first_name }},</p>
            <p>Your reservation at <strong>{{ $booking->property->name }}</strong> has been successfully confirmed.</p>
            
            <div class="booking-details">
                <p><strong>Property:</strong> {{ $booking->property->name }}</p>
                <p><strong>Address:</strong> {{ $booking->property->address_line_1 }}, {{ $booking->property->city }}</p>
                <p><strong>Check-in:</strong> {{ $booking->check_in_date }}</p>
                <p><strong>Check-out:</strong> {{ $booking->check_out_date }}</p>
                <p><strong>Guests:</strong> {{ $booking->number_of_guests }}</p>
            </div>

            <p>You can manage your booking and chat with us directly via your secure magic link:</p>
            <p style="text-align: center;">
                <a href="https://hosthome.vercel.app/guest/booking/{{ $booking->guest_portal_token }}" 
                   style="background: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    View My Booking
                </a>
            </p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} HostHome. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
