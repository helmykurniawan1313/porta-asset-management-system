<section class="card shadow-sm border-0">
    <div class="card-header bg-transparent border-bottom py-3">
        <h5 class="card-title mb-1 text-danger fw-bold text-uppercase" style="font-size: 0.85rem; letter-spacing: 1px;">
            {{ __('Delete Account') }}
        </h5>
        <p class="text-muted mb-0" style="font-size: 0.8rem;">
            {{ __('Permanently delete your account and all associated data.') }}
        </p>
    </div>

    <div class="card-body py-4">
        <p class="text-muted small mb-4">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting, please download any information that you wish to retain.') }}
        </p>

        <button type="button" class="btn btn-danger px-4 fw-bold text-uppercase" style="font-size: 0.8rem;"
                data-bs-toggle="modal" data-bs-target="#confirmUserDeletionModal">
            {{ __('Delete Account') }}
        </button>
    </div>

    <div class="modal fade" id="confirmUserDeletionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow hr-modal">
                <form method="post" action="{{ route('profile.destroy') }}">
                    @csrf
                    @method('delete')

                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">{{ __('Are you sure?') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <p class="text-muted small mb-4">
                            {{ __('Once your account is deleted, all data will be permanently lost. Please enter your password to confirm.') }}
                        </p>

                        <div class="mb-0">
                            <label for="password" class="form-label fw-bold text-muted small text-uppercase">{{ __('Password') }}</label>
                            <input id="password" name="password" type="password" 
                                   class="form-control @error('password', 'userDeletion') is-invalid @enderror" 
                                   placeholder="{{ __('Enter password to confirm') }}">
                            @if($errors->userDeletion->get('password'))
                                <div class="invalid-feedback">@foreach($errors->userDeletion->get('password') as $msg) {{ $msg }} @endforeach</div>
                            @endif
                        </div>
                    </div>

                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger ms-2 px-4 fw-bold">
                            {{ __('Delete Permanently') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>