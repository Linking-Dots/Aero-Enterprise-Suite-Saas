<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
@php
    // White-label: footer carries the brand's name and support contact.
    try {
        $aeroBrand = app(\Aero\Notifications\Contracts\BrandingResolver::class)->resolve();
    } catch (\Throwable $e) {
        $aeroBrand = null;
    }
@endphp
@if ($aeroBrand)
© {{ date('Y') }} {{ $aeroBrand['company_name'] }}. All rights reserved.
@if (!empty($aeroBrand['support_email']))
<br>Need help? <a href="mailto:{{ $aeroBrand['support_email'] }}">{{ $aeroBrand['support_email'] }}</a>
@endif
@else
{{ Illuminate\Mail\Markdown::parse($slot) }}
@endif
</td>
</tr>
</table>
</td>
</tr>
