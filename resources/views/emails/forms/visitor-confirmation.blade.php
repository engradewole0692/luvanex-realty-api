<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>We received your enquiry</title>
</head>
<body style="margin:0;padding:0;background:#f6f4ef;font-family:Georgia,serif;color:#1a1a1a;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f4ef;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width:640px;background:#ffffff;border:1px solid #e5e0d6;">
          <tr>
            <td style="padding:28px 32px;border-bottom:1px solid #e5e0d6;">
              <p style="margin:0;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#b08d57;">Luvanex Realty Group</p>
              <h1 style="margin:12px 0 0;font-size:26px;font-weight:normal;">Thank you for contacting us.</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 32px;font-size:16px;line-height:1.65;">
              <p style="margin:0 0 16px;">Dear {{ e($submission->name) }},</p>
              <p style="margin:0 0 16px;">
                Thank you for contacting Luvanex Realty. We have received your request and a member of our team will get back to you.
              </p>
              <p style="margin:0 0 16px;">
                If you need to share additional details, reply to this email or write to
                <a href="mailto:{{ e($inbox) }}" style="color:#b08d57;">{{ e($inbox) }}</a>.
              </p>
              <p style="margin:24px 0 0;">Warm regards,<br>Luvanex Realty Group</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
