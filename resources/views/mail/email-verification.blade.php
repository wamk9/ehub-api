<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: sans-serif; background: #f4f4f4; margin: 0; padding: 32px; }
    .card { background: #fff; border-radius: 8px; max-width: 480px; margin: 0 auto; padding: 40px; }
    .logo { font-size: 28px; font-weight: bold; color: #0098D8; margin-bottom: 24px; }
    .code { font-size: 42px; font-weight: bold; letter-spacing: 10px; color: #111; text-align: center;
            background: #f0f8ff; border: 2px solid #0098D8; border-radius: 8px; padding: 16px; margin: 24px 0; }
    .note { font-size: 13px; color: #888; margin-top: 24px; }
  </style>
</head>
<body>
  <div class="card">
    <div class="logo">eHub</div>
    <p>Olá! Use o código abaixo para verificar seu e-mail no eHub.</p>
    <div class="code">{{ $code }}</div>
    <p>O código expira em <strong>10 minutos</strong>.</p>
    <p class="note">Se você não solicitou este código, ignore este e-mail.</p>
  </div>
</body>
</html>
