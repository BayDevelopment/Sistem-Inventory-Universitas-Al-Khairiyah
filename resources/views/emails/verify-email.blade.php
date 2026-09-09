<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Verifikasi Email</title>

    <style>
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                padding: 20px 12px !important;
            }

            .email-card {
                border-radius: 16px !important;
            }

            .email-header {
                padding: 28px 24px !important;
            }

            .email-body {
                padding: 28px 24px !important;
            }

            .email-footer {
                padding: 20px 24px !important;
            }

            .email-title {
                font-size: 24px !important;
                line-height: 1.3 !important;
            }

            .email-text {
                font-size: 14px !important;
            }

            .verify-button {
                display: block !important;
                width: auto !important;
                text-align: center !important;
            }

            .brand-name {
                font-size: 17px !important;
            }
        }
    </style>
</head>

<body
    style="margin:0; padding:0; background-color:#f3f4f6; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#111827;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="width:100%; background-color:#f3f4f6;">
        <tr>
            <td align="center" class="email-wrapper" style="padding:40px 16px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    class="email-card"
                    style="width:100%; max-width:560px; background-color:#ffffff; border-radius:20px; overflow:hidden; box-shadow:0 12px 40px rgba(0,0,0,0.10);">

                    {{-- Header --}}
                    <tr>
                        <td class="email-header" style="padding:32px; background-color:#09090b; position:relative;">

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="position:relative;">

                                        {{-- Decorative red blob --}}
                                        <div
                                            style="position:absolute; top:-35px; right:-25px; width:85px; height:85px; background-color:#dc2626; border-radius:50%; opacity:0.7;">
                                        </div>

                                        {{-- Decorative black blob --}}
                                        <div
                                            style="position:absolute; bottom:-40px; left:-40px; width:75px; height:75px; background-color:#18181b; border-radius:50%; opacity:0.85;">
                                        </div>

                                        <div style="position:relative; z-index:2;">

                                            <div class="brand-name"
                                                style="font-size:19px; line-height:1.4; font-weight:700; color:#ffffff; letter-spacing:-0.3px;">
                                                {{ config('app.name') }}
                                            </div>

                                            <div
                                                style="margin-top:8px; width:42px; height:4px; background-color:#ef4444; border-radius:999px;">
                                            </div>

                                        </div>

                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- Main Content --}}
                    <tr>
                        <td class="email-body" style="padding:40px 40px 36px; background-color:#ffffff;">

                            {{-- Icon --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                style="margin-bottom:24px;">
                                <tr>
                                    <td align="center" valign="middle"
                                        style="width:52px; height:52px; background-color:#fef2f2; border-radius:14px;">
                                        <span style="font-size:25px; line-height:52px; color:#dc2626;">
                                            ✓
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <h1 class="email-title"
                                style="margin:0 0 16px; font-size:27px; line-height:1.3; font-weight:700; letter-spacing:-0.6px; color:#111827;">
                                Verifikasi Email Anda
                            </h1>

                            <p class="email-text"
                                style="margin:0 0 18px; font-size:15px; line-height:1.7; color:#374151;">
                                Halo, <strong style="color:#111827;">{{ $user->name }}</strong>!
                            </p>

                            <p class="email-text"
                                style="margin:0 0 18px; font-size:14px; line-height:1.7; color:#4b5563;">
                                Selamat datang di
                                <strong style="color:#111827;">{{ config('app.name') }}</strong>.
                                Kami menerima permintaan untuk memverifikasi alamat email
                                yang terhubung dengan akun Anda.
                            </p>

                            <p class="email-text"
                                style="margin:0 0 28px; font-size:14px; line-height:1.7; color:#4b5563;">
                                Untuk mengaktifkan akun dan mendapatkan akses ke seluruh fitur,
                                silakan verifikasi alamat email Anda dengan menekan tombol berikut.
                            </p>

                            {{-- Verification Button --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                                style="margin:0 0 28px;">
                                <tr>
                                    <td align="center" style="border-radius:10px; background-color:#dc2626;">
                                        <a href="{{ $url }}" target="_blank" class="verify-button"
                                            style="display:inline-block; padding:14px 28px; font-size:14px; line-height:20px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:10px; background-color:#dc2626;">
                                            Verifikasi Email Sekarang
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            {{-- Expiration Notice --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                                style="margin-bottom:26px;">
                                <tr>
                                    <td
                                        style="padding:16px 18px; background-color:#f9fafb; border-left:4px solid #dc2626; border-radius:8px;">
                                        <p style="margin:0; font-size:13px; line-height:1.6; color:#4b5563;">
                                            Link verifikasi ini hanya berlaku selama
                                            <strong style="color:#111827;">
                                                {{ $expireMinutes }} menit
                                            </strong>.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p class="email-text"
                                style="margin:0 0 24px; font-size:13px; line-height:1.7; color:#6b7280;">
                                Jika Anda tidak merasa membuat akun atau tidak meminta
                                verifikasi ini, Anda dapat mengabaikan email ini dengan aman.
                            </p>

                            <hr style="border:0; border-top:1px solid #e5e7eb; margin:0 0 22px;">

                            {{-- Fallback URL --}}
                            <p style="margin:0 0 8px; font-size:12px; line-height:1.6; color:#9ca3af;">
                                Jika tombol di atas tidak dapat digunakan, salin dan tempel
                                tautan berikut ke browser Anda:
                            </p>

                            <p style="margin:0; font-size:12px; line-height:1.6; word-break:break-all;">
                                <a href="{{ $url }}" target="_blank"
                                    style="color:#dc2626; text-decoration:none;">
                                    {{ $url }}
                                </a>
                            </p>

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="email-footer"
                            style="padding:24px 32px; background-color:#09090b; text-align:center;">

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">

                                        <div
                                            style="width:34px; height:3px; margin:0 auto 12px; background-color:#dc2626; border-radius:999px;">
                                        </div>

                                        <p style="margin:0 0 6px; font-size:12px; line-height:1.5; color:#d1d5db;">
                                            Salam,
                                            <strong style="color:#ffffff;">
                                                {{ config('app.name') }}
                                            </strong>
                                        </p>

                                        <p style="margin:0; font-size:11px; line-height:1.5; color:#71717a;">
                                            Email ini dikirim secara otomatis.
                                            Mohon tidak membalas email ini.
                                        </p>

                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                </table>

                {{-- Bottom Decoration --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                    style="max-width:560px;">
                    <tr>
                        <td align="center" style="padding:18px 16px 0;">

                            <p style="margin:0; font-size:11px; line-height:1.5; color:#9ca3af;">
                                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>

                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>

</html>
