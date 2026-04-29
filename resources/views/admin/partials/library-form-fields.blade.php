@php
    $item = $item ?? null;
    $fieldPrefix = $prefix ?? 'create';
@endphp

@if($definition['key'] === 'assistance-types')
    <div>
        <label class="label">Type Name</label>
        <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Medical Assistance">
    </div>
@elseif($definition['key'] === 'assistance-subtypes')
    <div class="modal-grid two">
        <div>
            <label class="label">Parent Assistance Type</label>
            <select name="assistance_type_id" class="input">
                <option value="">Select type</option>
                @foreach($formOptions['assistanceTypes'] as $type)
                    <option value="{{ $type->id }}" @selected(old('assistance_type_id', $item->assistance_type_id ?? null) == $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Subtype Name</label>
            <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Hospital Bill">
        </div>
    </div>
@elseif($definition['key'] === 'assistance-details')
    <div class="modal-grid two">
        <div>
            <label class="label">Parent Assistance Subtype</label>
            <select name="assistance_subtype_id" class="input">
                <option value="">Select subtype</option>
                @foreach($formOptions['assistanceSubtypes'] as $subtype)
                    <option value="{{ $subtype->id }}" @selected(old('assistance_subtype_id', $item->assistance_subtype_id ?? null) == $subtype->id)>
                        {{ $subtype->type?->name }} - {{ $subtype->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Detail Name</label>
            <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Payment for Hospital Bill">
        </div>
    </div>
@elseif($definition['key'] === 'modes-of-assistance')
    <div>
        <label class="label">Mode Name</label>
        <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Guarantee Letter">
    </div>
@elseif($definition['key'] === 'relationships')
    <div>
        <label class="label">Relationship Name</label>
        <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Sibling">
    </div>
@elseif($definition['key'] === 'referral-institutions')
    <div class="modal-grid two">
        <div>
            <label class="label">Institution / Agency Name</label>
            <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Department of Health">
        </div>
        <div>
            <label class="label">Addressee</label>
            <input type="text" name="addressee" class="input" value="{{ old('addressee', $item->addressee ?? '') }}" placeholder="Regional Director">
        </div>
        <div>
            <label class="label">Email</label>
            <input type="email" name="email" class="input" value="{{ old('email', $item->email ?? '') }}" placeholder="office@example.gov.ph">
        </div>
        <div>
            <label class="label">Contact Number</label>
            <input type="text" name="contact_number" class="input" value="{{ old('contact_number', $item->contact_number ?? '') }}" placeholder="(02) 8123 4567">
        </div>
    </div>

    <div>
        <label class="label">Address</label>
        <textarea name="address" class="input min-h-[110px]" placeholder="Office address">{{ old('address', $item->address ?? '') }}</textarea>
    </div>
@endif
