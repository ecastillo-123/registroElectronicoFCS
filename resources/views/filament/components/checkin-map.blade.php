@php
    $record = $record ?? ($getRecord() ?? null);
@endphp
@if ($record && $record->lat !== null && $record->lng !== null)
    <div
        id="checkin-map-{{ $record->id }}"
        class="overflow-hidden rounded-xl border border-gray-300"
        style="height: 280px; width: 100%;"
        data-lat="{{ $record->lat }}"
        data-lng="{{ $record->lng }}"
    ></div>
@endif
