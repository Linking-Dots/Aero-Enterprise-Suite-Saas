@props(['url'])
@php
    // White-label: header renders the workspace's brand through the
    // BrandingResolver chain (tenant → platform → Meridian) at send time.
    try {
        $aeroBrand = app(\Aero\Notifications\Contracts\BrandingResolver::class)->resolve();
    } catch (\Throwable $e) {
        $aeroBrand = ['company_name' => trim($slot), 'logo_url' => null];
    }
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (!empty($aeroBrand['logo_url']))
<img src="{{ $aeroBrand['logo_url'] }}" class="logo" alt="{{ $aeroBrand['company_name'] }}" style="height: 42px; max-width: 220px; object-fit: contain;">
@else
{!! $aeroBrand['company_name'] ?: $slot !!}
@endif
</a>
</td>
</tr>
