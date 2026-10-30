<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Set Your Password | Porta IT</title>
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/app.css') }}">
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
        <div class="card shadow-lg border-0" style="max-width: 420px; width: 100%;">
            <div class="card-body p-4 p-md-5">
                <h4 class="fw-bold mb-1">Set Your Password</h4>
                <p class="text-muted small mb-4">Welcome, {{ $user['name'] }}. Choose a password to activate your account ({{ $user['email'] }}).</p>

                @if($errors->any())
                    <div class="alert alert-light-danger color-danger mb-4">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ $signedFormUrl }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Set Password</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
