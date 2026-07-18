<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

define('MAIL_FROM',     '');
define('MAIL_FROM_NAME','DermaCare');
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', 'YOUR_APP_PASSWORD_HERE');   
define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);


function sendResetEmail(string $toEmail, string $toName, string $resetLink): bool|string {
    $mail = new PHPMailer(true);

    try {
        
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);

        
        $mail->isHTML(true);
        $mail->Subject = 'DermaCare — Reset Your Password';
        $mail->Body    = emailTemplate($toName, $resetLink);
        $mail->AltBody = "Hello $toName,\n\nClick the link below to reset your DermaCare password:\n$resetLink\n\nThis link expires in 1 hour.\n\nIf you did not request a password reset, please ignore this email.\n\nDermaCare Team";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('Mailer error: ' . $mail->ErrorInfo);
        return $mail->ErrorInfo;
    }
}


function emailTemplate(string $name, string $link): string {
    return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"/></head>
<body style="margin:0;padding:0;background:#F5F2EC;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F5F2EC;padding:40px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;border:1px solid #D8E8E3;overflow:hidden;">

        <!-- Header -->
        <tr>
          <td style="background:#0B3D35;padding:28px 40px;text-align:center;">
            <span style="font-size:24px;font-weight:bold;color:#ffffff;letter-spacing:1px;">DermaCare</span>
            <p style="color:#A8D5C8;font-size:13px;margin:4px 0 0;">Remote Dermatology Care System</p>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <p style="font-size:16px;color:#0F2820;margin:0 0 12px;">Hello <strong>' . htmlspecialchars($name) . '</strong>,</p>
            <p style="font-size:14px;color:#4A6258;line-height:1.7;margin:0 0 24px;">
              We received a request to reset the password for your DermaCare account.
              Click the button below to set a new password. This link will expire in <strong>1 hour</strong>.
            </p>

            <!-- Button -->
            <table cellpadding="0" cellspacing="0" style="margin:0 auto 28px;">
              <tr>
                <td style="background:#0B3D35;border-radius:9px;padding:14px 36px;text-align:center;">
                  <a href="' . htmlspecialchars($link) . '" style="color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;">Reset My Password</a>
                </td>
              </tr>
            </table>

            <p style="font-size:13px;color:#7A9990;line-height:1.6;margin:0 0 8px;">
              If the button does not work, copy and paste this link into your browser:
            </p>
            <p style="font-size:12px;word-break:break-all;">
              <a href="' . htmlspecialchars($link) . '" style="color:#1A6B5C;">' . htmlspecialchars($link) . '</a>
            </p>

            <hr style="border:none;border-top:1px solid #D8E8E3;margin:28px 0;"/>

            <p style="font-size:13px;color:#7A9990;margin:0;">
              If you did not request a password reset, you can safely ignore this email.
              Your password will not be changed.
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#EFF7F4;padding:20px 40px;text-align:center;">
            <p style="font-size:12px;color:#7A9990;margin:0;">
              &copy; 2026 DermaCare · Multimedia University ·
              <a href="http://localhost/dermacare/" style="color:#1A6B5C;text-decoration:none;">Visit DermaCare</a>
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>';
}
