<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 500 — Error Interno</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background: #020617;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            text-align: center;
        }
        .code {
            font-size: 8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #ef4444, #f97316);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: .75rem;
        }
        p {
            font-size: 1rem;
            color: #94a3b8;
            max-width: 400px;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn {
            display: inline-block;
            padding: .75rem 2rem;
            background: #1e293b;
            color: #e2e8f0;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: .9rem;
            transition: background .2s, transform .1s;
        }
        .btn:hover {
            background: #334155;
            transform: translateY(-1px);
        }
        .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: .6;
        }
        .error-id {
            margin-top: 2rem;
            font-size: .75rem;
            color: #475569;
        }
        @media (max-width: 480px) {
            .code { font-size: 5rem; }
            h1 { font-size: 1.25rem; }
        }
    </style>
</head>
<body>
    <div class="icon">⚠️</div>
    <div class="code">500</div>
    <h1>Error Interno del Servidor</h1>
    <p>Algo salió mal. Nuestro equipo ha sido notificado y vamos a revisarlo cuanto antes.</p>
    <a href="{{ url('') }}" class="btn">Volver al inicio</a>
    <div class="error-id">Si el problema persiste, contactanos con la fecha y hora en que ocurrió.</div>
</body>
</html>
