@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ rtrim(config('services.frontend_url'), '/') }}/assets/images/favicon/apple-icon.png" class="logo" alt="{{ config('app.name') }}" style="height: 56px; max-height: 56px; width: 56px;">
</a>
</td>
</tr>
