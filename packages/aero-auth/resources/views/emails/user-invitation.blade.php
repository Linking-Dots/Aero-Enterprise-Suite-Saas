@component('mail::message')
# You've been invited to {{ $appName }}

Hello,

@if ($invitedByName)
**{{ $invitedByName }}** has invited you to join **{{ $appName }}**.
@else
You have been invited to join **{{ $appName }}**.
@endif

Click the button below to accept your invitation and set up your account.

@component('mail::button', ['url' => $acceptUrl])
Accept Invitation
@endcomponent

This invitation expires on **{{ $expiresAt }}**. If it expires, please contact your administrator to request a new one.

If you did not expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ $appName }}

@slot('subcopy')
If you're having trouble clicking the "Accept Invitation" button, copy and paste the URL below into your web browser:
[{{ $acceptUrl }}]({{ $acceptUrl }})
@endslot
@endcomponent
