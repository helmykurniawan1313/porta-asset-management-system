<section class="card shadow-sm border-0">
    <div class="card-header bg-transparent border-bottom py-3">
        <h5 class="card-title mb-1 text-uppercase fw-bold" style="font-size: 0.85rem; letter-spacing: 1px;">
            {{ __('Update Password') }}
        </h5>
        <p class="text-muted mb-0" style="font-size: 0.8rem;">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </div>

    <div class="card-body py-4">
        <form method="post" action="{{ route('password.update') }}">
            @csrf
            @method('put')

            <div class="mb-3">
                <label for="update_password_current_password" class="form-label">
                    {{ __('Current Password') }}
                </label>
                <input id="update_password_current_password" name="current_password" type="password" 
                       class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password">
                @if($errors->updatePassword->get('current_password'))
                    <div class="invalid-feedback">@foreach($errors->updatePassword->get('current_password') as $msg) {{ $msg }} @endforeach</div>
                @endif
            </div>

            <div class="mb-3">
                <label for="update_password_password" class="form-label">
                    {{ __('New Password') }}
                </label>
                <input id="update_password_password" name="password" type="password" 
                       class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                @if($errors->updatePassword->get('password'))
                    <div class="invalid-feedback">@foreach($errors->updatePassword->get('password') as $msg) {{ $msg }} @endforeach</div>
                @endif
            </div>

            <div class="mb-4">
                <label for="update_password_password_confirmation" class="form-label">
                    {{ __('Confirm Password') }}
                </label>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password" 
                       class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" autocomplete="new-password">
                @if($errors->updatePassword->get('password_confirmation'))
                    <div class="invalid-feedback">@foreach($errors->updatePassword->get('password_confirmation') as $msg) {{ $msg }} @endforeach</div>
                @endif
            </div>

            <div class="d-flex align-items-center gap-3 border-top pt-3">
                <button type="submit" class="btn btn-primary px-4 fw-bold text-uppercase" style="font-size: 0.8rem;">
                    {{ __('Save Password') }}
                </button>

                @if (session('status') === 'password-updated')
                    <span x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                          class="text-success small fw-bold">
                        <i class="bi bi-check-circle-fill me-1"></i> {{ __('Updated.') }}
                    </span>
                @endif
            </div>
        </form>
    </div>
</section>