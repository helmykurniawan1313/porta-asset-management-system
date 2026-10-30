{{-- Status tone (or custom bg/ink) for a score/label tier; "Auto" follows the tier's rank. --}}
@php $autoTone = $tones[min($rank, 4)]; @endphp
<div class="d-flex align-items-center gap-1" data-color-picker data-auto="{{ $autoTone }}">
    <span class="sb-swatch"></span>
    <select name="color" form="{{ $formId }}" class="form-select form-select-sm" style="width: 7rem" data-no-search {{ $dis }}>
        <option value="">Auto</option>
        @foreach(['sip' => 'SIP', 'bagus' => 'Bagus', 'cukup' => 'Cukup', 'kurang' => 'Kurang', 'parah' => 'Parah', 'custom' => 'Custom'] as $v => $l)
            <option value="{{ $v }}" @selected(($b['color'] ?? null) === $v)>{{ $l }}</option>
        @endforeach
    </select>
    <span class="d-flex gap-1 d-none" data-custom>
        <input type="color" name="custom_color_bg" form="{{ $formId }}" value="{{ $b['custom_color_bg'] ?? '#e8f5e9' }}" class="form-control form-control-sm form-control-color p-0" title="Background">
        <input type="color" name="custom_color_ink" form="{{ $formId }}" value="{{ $b['custom_color_ink'] ?? '#1b5e20' }}" class="form-control form-control-sm form-control-color p-0" title="Text">
    </span>
</div>
