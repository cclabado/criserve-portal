@php
    $fieldPrefix = $prefix ?? 'create';
    $isCreate = $fieldPrefix === 'create';
@endphp

<div class="modal-grid two">
    <div>
        <label class="label">Assistance Type</label>
        <select name="assistance_type_id" class="input">
            <option value="">Select type</option>
            @foreach($formOptions['assistanceTypes'] as $type)
                <option value="{{ $type->id }}" @selected($isCreate && old('assistance_type_id') == $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="label">Assistance Subtype</label>
        <select name="assistance_subtype_id" class="input">
            <option value="">Select subtype</option>
        </select>
    </div>
</div>

<div class="modal-grid two">
    <div>
        <label class="label">Assistance Detail</label>
        <select name="assistance_detail_id" class="input">
            <option value="">Subtype-level only</option>
        </select>
    </div>

    <div>
        <label class="label">Rule Type</label>
        <select name="rule_type" class="input">
            <option value="">Select rule type</option>
            @foreach($ruleTypes as $value => $label)
                <option value="{{ $value }}" @selected($isCreate && old('rule_type') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div data-interval-wrap>
    <label class="label">Interval Months</label>
    <input type="number" name="interval_months" class="input" min="1" max="120" placeholder="3" value="{{ $isCreate ? old('interval_months') : '' }}">
</div>

<div>
    <label class="label">Rule Notes</label>
    <textarea name="notes" class="input min-h-[120px]" placeholder="Explain the rule, review conditions, or operational notes.">{{ $isCreate ? old('notes') : '' }}</textarea>
</div>

<div class="checkbox-grid">
    <label class="checkbox-card">
        <input type="checkbox" name="requires_reference_date" value="1" @checked($isCreate && old('requires_reference_date'))>
        <span>
            <span class="block font-semibold text-slate-800">Reference Date</span>
            <span class="block text-sm text-slate-500">Require a specific reference date for validation.</span>
        </span>
    </label>

    <label class="checkbox-card">
        <input type="checkbox" name="requires_case_key" value="1" @checked($isCreate && old('requires_case_key'))>
        <span>
            <span class="block font-semibold text-slate-800">Case Key</span>
            <span class="block text-sm text-slate-500">Require an incident, admission, or case reference.</span>
        </span>
    </label>

    <label class="checkbox-card">
        <input type="checkbox" name="allows_exception_request" value="1" @checked($isCreate && old('allows_exception_request'))>
        <span>
            <span class="block font-semibold text-slate-800">Override Request</span>
            <span class="block text-sm text-slate-500">Allow social workers to add a justification when needed.</span>
        </span>
    </label>
</div>
