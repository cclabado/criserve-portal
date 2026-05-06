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
@elseif($definition['key'] === 'document-requirements')
    <div class="modal-grid two">
        <div>
            <label class="label">Assistance Subtype</label>
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
            <label class="label">Assistance Detail</label>
            <select name="assistance_detail_id" class="input">
                <option value="">Subtype-wide requirement</option>
                @foreach($formOptions['assistanceDetails'] as $detail)
                    <option value="{{ $detail->id }}" @selected(old('assistance_detail_id', $item->assistance_detail_id ?? null) == $detail->id)>
                        {{ $detail->subtype?->type?->name }} - {{ $detail->subtype?->name }} - {{ $detail->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Requirement Name</label>
            <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Medical Certificate">
        </div>
        <div>
            <label class="label">Sort Order</label>
            <input type="number" min="0" name="sort_order" class="input" value="{{ old('sort_order', $item->sort_order ?? 0) }}">
        </div>
        <div>
            <label class="label">Applies When Amount Exceeds</label>
            <input type="number" min="0" step="0.01" name="applies_when_amount_exceeds" class="input" value="{{ old('applies_when_amount_exceeds', $item->applies_when_amount_exceeds ?? '') }}" placeholder="10000">
        </div>
        <div>
            <label class="label">Requirement Mode</label>
            <select name="is_required" class="input">
                <option value="1" @selected((string) old('is_required', isset($item) ? (int) ($item->is_required ?? false) : 1) === '1')>Required</option>
                <option value="0" @selected((string) old('is_required', isset($item) ? (int) ($item->is_required ?? false) : 1) === '0')>Optional</option>
            </select>
        </div>
    </div>

    <div>
        <label class="label">Description / Upload Guidance</label>
        <textarea name="description" class="input min-h-[110px]" placeholder="Explain what the client should upload and any date/signature requirements.">{{ old('description', $item->description ?? '') }}</textarea>
    </div>
@elseif($definition['key'] === 'modes-of-assistance')
    <div class="modal-grid two">
        <div>
            <label class="label">Mode Name</label>
            <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Guarantee Letter">
        </div>
        <div>
            <label class="label">Minimum Amount</label>
            <input type="number" min="0" step="0.01" name="minimum_amount" class="input" value="{{ old('minimum_amount', $item->minimum_amount ?? '') }}" placeholder="1.00">
        </div>
        <div>
            <label class="label">Maximum Amount</label>
            <input type="number" min="0" step="0.01" name="maximum_amount" class="input" value="{{ old('maximum_amount', $item->maximum_amount ?? '') }}" placeholder="10000.00">
        </div>
    </div>
@elseif($definition['key'] === 'service-points')
    <div>
        <label class="label">Service Point Name</label>
        <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Online">
    </div>
@elseif($definition['key'] === 'service-providers')
    <div class="modal-grid two">
        <div>
            <label class="label">Provider Name</label>
            <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="ABC Hospital">
        </div>
        <div>
            <label class="label">Addressee</label>
            <input type="text" name="addressee" class="input" value="{{ old('addressee', $item->addressee ?? '') }}" placeholder="Billing Manager / Administrator">
        </div>
        <div>
            <label class="label">Contact Number</label>
            <input type="text" name="contact_number" class="input" value="{{ old('contact_number', $item->contact_number ?? '') }}" placeholder="0917 000 0000">
        </div>
        <div>
            <label class="label">Office Email</label>
            <input type="email" name="email" class="input" value="{{ old('email', $item->user?->email ?? $item->email ?? '') }}" placeholder="provider@example.com">
        </div>
    </div>

    <div>
        <label class="label">Provider Categories</label>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach(\App\Models\ServiceProvider::CATEGORY_OPTIONS as $categoryOption)
                @php
                    $selectedCategories = old('categories', $item?->categories ?? []);
                @endphp
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                    <input type="checkbox"
                           name="categories[]"
                           value="{{ $categoryOption }}"
                           class="h-4 w-4 rounded border-slate-300 text-sky-700 focus:ring-sky-500"
                           @checked(in_array($categoryOption, $selectedCategories, true))>
                    <span>{{ $categoryOption }}</span>
                </label>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-slate-500">These categories control which providers appear for matching assistance subtype or detail selections.</p>
    </div>

    <div>
        <label class="label">Address</label>
        <textarea name="address" class="input min-h-[110px]" placeholder="Provider address">{{ old('address', $item->address ?? '') }}</textarea>
    </div>
@elseif($definition['key'] === 'positions')
    <div class="modal-grid two">
        <div>
            <label class="label">Position Title</label>
            <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Social Welfare Officer I">
        </div>
        <div>
            <label class="label">Position Code</label>
            <input type="text" name="position_code" class="input" value="{{ old('position_code', $item->position_code ?? '') }}" placeholder="SOCWO1">
        </div>
        <div>
            <label class="label">Salary Grade</label>
            <input type="number" min="1" max="33" name="salary_grade" class="input" value="{{ old('salary_grade', $item->salary_grade ?? '') }}" placeholder="11">
        </div>
        <div>
            <label class="label">License Requirement</label>
            <select name="requires_license_number" class="input">
                <option value="0" @selected((string) old('requires_license_number', isset($item) ? (int) ($item->requires_license_number ?? false) : 0) === '0')>No license required</option>
                <option value="1" @selected((string) old('requires_license_number', isset($item) ? (int) ($item->requires_license_number ?? false) : 0) === '1')>License required</option>
            </select>
        </div>
    </div>
@elseif($definition['key'] === 'relationships')
    <div>
        <label class="label">Relationship Name</label>
        <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Sibling">
    </div>
@elseif($definition['key'] === 'client-types')
    <div>
        <label class="label">Client Type Name</label>
        <input type="text" name="name" class="input" value="{{ old('name', $item->name ?? '') }}" placeholder="Returning">
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
