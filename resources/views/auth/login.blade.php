<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Intenergy</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            transition: transform 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
        }
        .brand-logo {
            font-weight: 800;
            font-size: 28px;
            color: #00d2ff;
            text-align: center;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        .brand-sub {
            font-size: 13px;
            color: #b0bec5;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 300;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 12px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #00d2ff;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(0, 210, 255, 0.25);
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        .form-label {
            font-weight: 600;
            font-size: 14px;
            color: #e0e0e0;
            margin-bottom: 8px;
        }
        .btn-login {
            background: linear-gradient(45deg, #00d2ff 0%, #3a7bd5 100%);
            border: none;
            color: white;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: linear-gradient(45deg, #3a7bd5 0%, #00d2ff 100%);
            box-shadow: 0 4px 15px rgba(0, 210, 255, 0.4);
            transform: scale(1.02);
        }
        .alert-custom {
            background: rgba(244, 67, 54, 0.2);
            border: 1px solid rgba(244, 67, 54, 0.3);
            color: #ff8a80;
            border-radius: 8px;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-logo">INTENERGY</div>
        <div class="brand-sub">Inteligencia en Energía</div>

        @if ($errors->any())
            <div class="alert alert-custom mb-4" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="email" class="form-label">Usuario</label>
                <input type="text" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="Ingresa tu usuario..." required autofocus>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required autocomplete="current-password">
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-login">Ingresar al Sistema</button>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
