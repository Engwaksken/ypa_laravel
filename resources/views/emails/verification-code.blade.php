@extends('emails.layouts.branded')

@section('subject', 'Your verification code')
@section('preheader', 'Your sign-in code is ' . $code . '. It expires in 10 minutes.')
@section('heading', 'Hello ' . $name . ',')
@section('subheading', 'Two-factor sign-in verification')

@section('content')
    <p style="margin:0 0 18px;">Use the code below to finish signing in to your account.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 20px;">
        <tr>
            <td align="center" style="padding:22px 16px;background:#f1f7fc;border:1px dashed #9cc3e3;border-radius:10px;">
                <div style="font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#64748b;">Verification code</div>
                <div style="margin-top:8px;font-family:'Courier New',Courier,monospace;font-size:34px;font-weight:700;letter-spacing:10px;color:#005fa3;">{{ $code }}</div>
            </td>
        </tr>
    </table>

    @include('emails.partials.notice', ['tone' => 'info', 'title' => 'Expires in 10 minutes', 'body' => 'Request a new code from the sign-in page if this one expires.'])
    @include('emails.partials.notice', ['tone' => 'warning', 'title' => 'Did not try to sign in?', 'body' => 'Ignore this email and change your password. Never share this code with anyone, including staff.'])
@endsection
