@php
    /** @var \App\Models\Subscription|null $sub */
    $sub = $sub ?? null;
    $uid = $sub?->id ?? 'new';
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label text-muted fw-bold small text-uppercase">App</label>
        <input type="text" name="app" value="{{ $sub?->app }}" class="form-control" list="app-options" placeholder="e.g. Adobe, Canva" required>
    </div>
    <div class="col-md-6">
        <label class="form-label text-muted fw-bold small text-uppercase">Plan</label>
        <input type="text" name="plan" value="{{ $sub?->plan }}" class="form-control" placeholder="e.g. PS & LR, Pro">
    </div>

    <div class="col-md-8">
        <label class="form-label text-muted fw-bold small text-uppercase">Login Account</label>
        <select name="account_id" class="form-select" required>
            <option value="">-- Select account --</option>
            @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" {{ $sub?->account_id == $acc->id ? 'selected' : '' }}>{{ $acc->email }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label text-muted fw-bold small text-uppercase">Status</label>
        <select name="status" class="form-select">
            @foreach(['Active', 'Inactive'] as $opt)
                <option {{ ($sub?->status ?? 'Active') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-8">
        <label class="form-label text-muted fw-bold small text-uppercase">Password</label>
        <input type="text" name="password" value="{{ $sub?->password }}" class="form-control" autocomplete="off">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="login_with_google" value="0">
            <input class="form-check-input" type="checkbox" name="login_with_google" value="1" id="google-{{ $uid }}" {{ $sub?->login_with_google ? 'checked' : '' }}>
            <label class="form-check-label small fw-semibold" for="google-{{ $uid }}">Login with Google</label>
        </div>
    </div>

    @php
        $selectedUsers = $sub
            ? $sub->allocations->where('assignable_type', \App\Models\User::class)->pluck('assignable_id')->all()
            : [];
    @endphp
    <div class="col-12">
        <label class="form-label text-muted fw-bold small text-uppercase">Used By</label>
        <select name="user_ids[]" class="form-select user-picker" multiple data-placeholder="Search employees…">
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ in_array($emp->id, $selectedUsers) ? 'selected' : '' }}>{{ $emp->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-8">
        <label class="form-label text-muted fw-bold small text-uppercase">Other Users</label>
        <input type="text" name="shared_with" value="{{ $sub?->shared_with }}" class="form-control" placeholder="Not an employee, e.g. Mac Lt. 4, All, Designer">
    </div>
    <div class="col-md-4">
        <label class="form-label text-muted fw-bold small text-uppercase">Division</label>
        <input type="text" name="division" value="{{ $sub?->division }}" class="form-control" placeholder="Designer">
    </div>

    <div class="col-md-4">
        <label class="form-label text-muted fw-bold small text-uppercase">Billing Period</label>
        <select name="period" class="form-select">
            @foreach(\App\Models\Subscription::PERIODS as $opt)
                <option {{ ($sub?->period ?? 'Monthly') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label text-muted fw-bold small text-uppercase">Amount</label>
        <input type="number" step="0.01" min="0" name="bill" value="{{ $sub?->bill }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label text-muted fw-bold small text-uppercase">Currency</label>
        <select name="currency" class="form-select">
            <option value="">-</option>
            @foreach(['IDR', 'USD', 'EUR'] as $opt)
                <option {{ ($sub?->currency ?? 'IDR') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label text-muted fw-bold small text-uppercase">Next Payment / Expiry</label>
        <input type="date" name="next_payment_date" value="{{ $sub?->next_payment_date?->format('Y-m-d') }}" class="form-control">
        <small class="text-muted">Required for Monthly and Yearly plans.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label text-muted fw-bold small text-uppercase">Card (last digits)</label>
        <input type="text" name="card_info" value="{{ $sub?->card_info }}" maxlength="20" class="form-control" placeholder="2070">
    </div>

    <div class="col-12">
        <label class="form-label text-muted fw-bold small text-uppercase">Note</label>
        <textarea name="notes" rows="2" class="form-control">{{ $sub?->notes }}</textarea>
    </div>
</div>
