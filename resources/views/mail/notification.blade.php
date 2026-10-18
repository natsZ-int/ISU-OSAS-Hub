<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f5;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:2rem 1rem;">
                <table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border:1px solid #e3e8e5;border-radius:10px;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#083f28,#0b5d3b);padding:1.25rem 1.5rem;border-radius:10px 10px 0 0;">
                            <span style="color:#ffffff;font-size:1.1rem;font-weight:bold;">ISU OSAS Hub</span>
                            <span style="display:block;color:#cfe4da;font-size:0.78rem;">Office of Student Affairs and Services</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:1.5rem;">
                            <p style="margin:0 0 0.35rem;font-size:0.72rem;letter-spacing:0.08rem;text-transform:uppercase;color:#0b5d3b;font-weight:bold;">{{ $categoryLabel }} Update</p>
                            <h1 style="margin:0 0 0.75rem;font-size:1.2rem;color:#111827;">{{ $heading }}</h1>
                            <p style="margin:0 0 1rem;font-size:0.95rem;line-height:1.55;">Hello {{ $recipientName }},</p>
                            <p style="margin:0 0 1.25rem;font-size:0.92rem;line-height:1.55;">{{ $intro }}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e3e8e5;border-radius:8px;margin-bottom:1.25rem;">
                                <tr>
                                    <td style="padding:1rem 1.15rem;font-size:0.88rem;line-height:1.7;">
                                        @if ($reference)
                                            <div><span style="color:#6b7280;">{{ $referenceLabel ?: 'Reference' }}:</span>
                                                <strong style="color:#111827;">{{ $reference }}</strong></div>
                                        @endif
                                        @if ($status)
                                            <div><span style="color:#6b7280;">Current status:</span>
                                                <strong style="color:#111827;">{{ $status }}</strong></div>
                                        @endif
                                        @if ($updatedAt)
                                            <div><span style="color:#6b7280;">Date of update:</span>
                                                <strong style="color:#111827;">{{ $updatedAt }}</strong></div>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            @if ($reason)
                                <p style="margin:0 0 1.25rem;padding:0.85rem 1rem;background:#fdf6e3;border:1px solid #f0e0b0;border-radius:8px;font-size:0.88rem;line-height:1.55;color:#5c4a12;">
                                    <strong>Staff explanation:</strong> {{ $reason }}
                                </p>
                            @endif

                            @if ($nextStep)
                                <p style="margin:0 0 1.25rem;font-size:0.88rem;line-height:1.55;color:#374151;">
                                    <strong>Next step:</strong> {{ $nextStep }}
                                </p>
                            @endif

                            @if ($actionUrl)
                                <p style="margin:0 0 1.25rem;text-align:center;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;background:#0b5d3b;color:#ffffff;text-decoration:none;font-size:0.9rem;font-weight:bold;padding:0.7rem 1.4rem;border-radius:8px;">View Details</a>
                                </p>
                            @endif

                            <p style="margin:0;font-size:0.82rem;color:#6b7280;line-height:1.55;">
                                Please log in to the official ISU OSAS Hub system for complete details and to take any required action.
                                Do not share your account credentials with anyone.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 1.5rem 1.5rem;font-size:0.75rem;color:#6b7280;line-height:1.5;">
                            This is an automated message from ISU OSAS Hub. Please do not reply to this email.<br>
                            &copy; {{ date('Y') }} Isabela State University — OSAS Hub. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
