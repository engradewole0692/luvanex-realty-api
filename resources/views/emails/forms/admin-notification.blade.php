<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>New form submission</title>
</head>
<body style="margin:0;padding:0;background:#f6f4ef;font-family:Georgia,serif;color:#1a1a1a;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f4ef;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" style="max-width:640px;background:#ffffff;border:1px solid #e5e0d6;">
          <tr>
            <td style="padding:28px 32px;border-bottom:1px solid #e5e0d6;">
              <p style="margin:0;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#b08d57;">Luvanex Realty</p>
              <h1 style="margin:12px 0 0;font-size:24px;font-weight:normal;">New {{ strtoupper(str_replace('_', ' ', $submission->form_type)) }} enquiry</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 32px;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:15px;line-height:1.55;">
                <tr>
                  <td style="padding:6px 0;color:#6b655c;width:140px;">Form type</td>
                  <td style="padding:6px 0;">{{ e($submission->form_type) }}</td>
                </tr>
                <tr>
                  <td style="padding:6px 0;color:#6b655c;">Name</td>
                  <td style="padding:6px 0;">{{ e($submission->name) }}</td>
                </tr>
                <tr>
                  <td style="padding:6px 0;color:#6b655c;">Email</td>
                  <td style="padding:6px 0;"><a href="mailto:{{ e($submission->email) }}" style="color:#b08d57;">{{ e($submission->email) }}</a></td>
                </tr>
                @if ($submission->phone)
                <tr>
                  <td style="padding:6px 0;color:#6b655c;">Phone</td>
                  <td style="padding:6px 0;">{{ e($submission->phone) }}</td>
                </tr>
                @endif
                @if ($submission->company)
                <tr>
                  <td style="padding:6px 0;color:#6b655c;">Company</td>
                  <td style="padding:6px 0;">{{ e($submission->company) }}</td>
                </tr>
                @endif
                @if ($submission->country)
                <tr>
                  <td style="padding:6px 0;color:#6b655c;">Country</td>
                  <td style="padding:6px 0;">{{ e($submission->country) }}</td>
                </tr>
                @endif
                @if ($submission->subject)
                <tr>
                  <td style="padding:6px 0;color:#6b655c;">Subject</td>
                  <td style="padding:6px 0;">{{ e($submission->subject) }}</td>
                </tr>
                @endif
                <tr>
                  <td style="padding:6px 0;color:#6b655c;">Submitted</td>
                  <td style="padding:6px 0;">{{ optional($submission->submitted_at)->toDayDateTimeString() }}</td>
                </tr>
              </table>

              @if ($submission->message)
              <div style="margin-top:24px;padding-top:20px;border-top:1px solid #e5e0d6;">
                <p style="margin:0 0 8px;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#6b655c;">Message</p>
                <p style="margin:0;white-space:pre-wrap;">{{ e($submission->message) }}</p>
              </div>
              @endif

              @php
                $extra = collect($payload ?? [])
                  ->except(['first_name', 'last_name'])
                  ->filter(fn ($value) => filled($value));
              @endphp
              @if ($extra->isNotEmpty())
              <div style="margin-top:24px;padding-top:20px;border-top:1px solid #e5e0d6;">
                <p style="margin:0 0 12px;font-size:11px;letter-spacing:0.22em;text-transform:uppercase;color:#6b655c;">Additional details</p>
                @foreach ($extra as $key => $value)
                  <p style="margin:0 0 8px;"><strong style="text-transform:capitalize;">{{ e(str_replace('_', ' ', (string) $key)) }}:</strong> {{ e(is_scalar($value) ? (string) $value : json_encode($value)) }}</p>
                @endforeach
              </div>
              @endif
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
