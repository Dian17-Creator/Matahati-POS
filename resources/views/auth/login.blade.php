<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Matahati POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            background: linear-gradient(135deg, #b63352 0%, #e04a70 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-card card">
        <div class="login-header">
            <h3 class="mb-0 fw-bold">MATAHATI POS</h3>
            <p class="mb-0 opacity-75">Silakan masuk ke akun Anda</p>
        </div>
        <div class="card-body p-4">
            @if ($errors->any())
                <div class="alert alert-danger pb-0">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="cemail" class="form-label">Email</label>
                    <input type="email" class="form-control" id="cemail" name="cemail" value="{{ old('cemail') }}" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="cpassword" class="form-label">Password</label>
                    <input type="password" class="form-control" id="cpassword" name="cpassword" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold py-2" style="background-color: #b63352; border-color: #b63352;">Masuk</button>
            </form>
        </div>
    </div>
</body>
</html>
