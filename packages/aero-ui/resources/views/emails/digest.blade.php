<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>{{ config('app.name') }} Digest</title></head>
<body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;">
<h2>{{ config('app.name') }} {{ ucfirst($digest['frequency']) }} Digest</h2>
<p>Period: {{ $digest['period_start'] }} - {{ $digest['period_end'] }}</p>
<p>Total: {{ $digest['total_items'] }} notifications</p>
@if($digest['total_items'] === 0)
<p>No new notifications.</p>
@else
@foreach($digest['items'] as $category => $items)
<h3>{{ ucfirst($category) }}</h3>
<ul>
@foreach($items as $item)
<li>{{ $item['title'] ?? 'Notification' }} - {{ $item['created_at'] ?? '' }}</li>
@endforeach
</ul>
@endforeach
@endif
<p><a href="{{ url('/') }}">View Dashboard</a></p>
</body>
</html>
