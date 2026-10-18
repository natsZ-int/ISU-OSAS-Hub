<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify your email — ISU OSAS Hub</title>
</head>
<body style="margin:0;padding:0;background:#f4f7f5;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:2rem 1rem;">
                <table role="presentation" width="100%" style="max-width:480px;background:#ffffff;border:1px solid #e3e8e5;border-radius:10px;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#083f28,#0b5d3b);padding:1.25rem 1.5rem;border-radius:10px 10px 0 0;">
                            <span style="color:#ffffff;font-size:1.1rem;font-weight:bold;">ISU OSAS Hub</span>
                            <span style="display:block;color:#cfe4da;font-size:0.78rem;">Office of Student Affairs and Services</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:1.5rem;">
                            <h1 style="margin:0 0 0.75rem;font-size:1.15rem;color:#111827;">Verify your email address</h1>
                            <p style="margin:0 0 1rem;font-size:0.92rem;line-height:1.5;">
                                Hello {{ $user->name }}, use the verification code below to confirm your email address for your ISU OSAS Hub account.
                            </p>
                            <p style="margin:0 0 1rem;text-align:center;">
                                <span style="display:inline-block;background:#e9f3ee;border:1px solid #cfe4da;border-radius:8px;padding:0.85rem 1.5rem;font-size:1.75rem;font-weight:bold;letter-spacing:0.35rem;color:#083f28;">{{ $otpCode }}</span>
                            </p>
                            <p style="margin:0 0 0.5rem;font-size:0.85rem;color:#6b7280;">
                                This code expires in {{ \App\Services\OtpVerificationService::EXPIRY_MINUTES }} minutes.
                            </p>
                            <p style="margin:0;font-size:0.85rem;color:#6b7280;">
                                If you did not create an account on ISU OSAS Hub, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 1.5rem 1.5rem;font-size:0.75rem;color:#6b7280;">
                            &copy; {{ date('Y') }} Isabela State University — OSAS Hub. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
