@extends('emails.layouts.branded')

@section('subject', 'Password reset request')
@section('preheader', 'Reset your password. The link expires in 1 hour.')
@section('heading', 'Hello ' . $name . ',')
@section('subheading', 'Password reset request')

@section('content')
    <p style="margin:0 0 22px;">We received a request to reset your password. Select the button below to choose a new one.</p>

    <table role="presentation" cellpadding="0" cellspacing="0" align="center" class="email-button" style="margin:0 auto 22px;">
        <tr>
            <td align="center" style="border-radius:10px;background:#005fa3;">
                <a href="{{ $resetLink }}" style="display:inline-block;padding:13px 30px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:10px;">Reset password</a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 6px;font-size:13px;color:#64748b;">If the button does not work, copy this link into your browser:</p>
    <p style="margin:0;font-size:13px;word-break:break-all;"><a href="{{ $resetLink }}" style="color:#005fa3;">{{ $resetLink }}</a></p>

    @include('emails.partials.notice', ['tone' => 'warning', 'title' => 'Did not request this?', 'body' => 'The link expires in 1 hour. If you did not ask to reset your password, you can safely ignore this email.'])
@endsection
