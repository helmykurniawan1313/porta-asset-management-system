<section class="card shadow-sm border-0">
    <div class="card-header bg-transparent border-bottom py-3">
        <h5 class="card-title mb-1 text-uppercase fw-bold" style="font-size: 0.85rem; letter-spacing: 1px;">
            {{ __('Profile Information') }}
        </h5>
        <p class="text-muted mb-0" style="font-size: 0.8rem;">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </div>

    <div class="card-body py-4">
        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
            @csrf
        </form>

        <form method="post" action="{{ route('profile.update') }}">
            @csrf
            @method('patch')

            <div class="mb-3">
                <label for="name" class="form-label fw-bold text-muted small text-uppercase">{{ __('Name') }}</label>
                <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                       value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                @if($errors->get('name'))
                    <div class="invalid-feedback">@foreach($errors->get('name') as $msg) {{ $msg }} @endforeach</div>
                @endif
            </div>

            <div class="mb-4">
                <label for="email" class="form-label fw-bold text-muted small text-uppercase">{{ __('Email') }}</label>
                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" 
                       value="{{ old('email', $user->email) }}" required autocomplete="username">
                
                @if($errors->get('email'))
                    <div class="invalid-feedback">@foreach($errors->get('email') as $msg) {{ $msg }} @endforeach</div>
                @endif

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-3 p-3 bg-light rounded shadow-sm">
                        <p class="text-sm text-dark mb-2">
                            {{ __('Your email address is unverified.') }}
                        </p>
                        <button form="send-verification" class="btn btn-sm btn-outline-primary">
                            {{ __('Re-send verification email') }}
                        </button>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-success small fw-bold">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="d-flex align-items-center gap-3 border-top pt-3">
                <button type="submit" class="btn btn-primary px-4 fw-bold text-uppercase" style="font-size: 0.8rem;">
                    {{ __('Save Changes') }}
                </button>

                @if (session('status') === 'profile-updated')
                    <span x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                          class="text-success small fw-bold">
                        <i class="bi bi-check-circle-fill me-1"></i> {{ __('Saved.') }}
                    </span>
                @endif
            </div>
        </form>
    </div>
</section>