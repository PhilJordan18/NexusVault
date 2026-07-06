@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (in_array(trim($slot), ['Laravel', config('app.name')], true))
<img src="{{ asset('logo/LogoMonogramme.svg') }}" class="logo" alt="{{ config('app.name') }} Logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
