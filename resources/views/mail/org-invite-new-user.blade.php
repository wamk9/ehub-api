<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Convite para gerenciar {{ $orgName }}</title>
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 40px 16px;">

  <div style="max-width: 520px; margin: 0 auto;">

    <!-- Logo -->
    <div style="text-align: center; margin-bottom: 28px;">
      <span style="font-size: 30px; font-weight: 800; color: #0098D8; letter-spacing: -1px;">eHub</span>
    </div>

    <!-- Card -->
    <div style="background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">

      <!-- Top accent bar -->
      <div style="height: 4px; background: linear-gradient(90deg, #0098D8, #00bfff);"></div>

      <div style="padding: 40px 40px 36px;">

        <!-- Heading -->
        <h1 style="font-size: 22px; font-weight: 700; color: #111827; margin: 0 0 8px;">
          Você foi convidado! 🎉
        </h1>
        <p style="font-size: 15px; color: #4b5563; margin: 0 0 28px; line-height: 1.6;">
          <strong style="color: #111827;">{{ $inviterName }}</strong> convidou você para gerenciar
          a organização <strong style="color: #0098D8;">{{ $orgName }}</strong> no eHub.
        </p>

        <!-- Role badge -->
        <div style="margin-bottom: 28px;">
          <span style="display: inline-block; background: #eff6ff; color: #1d4ed8; font-size: 13px;
                        font-weight: 600; padding: 5px 14px; border-radius: 20px; border: 1px solid #bfdbfe;">
            {{ $role }}
          </span>
        </div>

        <!-- Info box -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
                    padding: 16px 20px; margin-bottom: 32px;">
          <p style="font-size: 14px; color: #374151; margin: 0 0 6px; font-weight: 600;">
            Você ainda não tem uma conta no eHub?
          </p>
          <p style="font-size: 13px; color: #6b7280; margin: 0; line-height: 1.55;">
            Sem problemas. Clique no botão abaixo para criar sua conta gratuitamente.
            Ao concluir o cadastro, você terá acesso imediato à organização
            <strong>{{ $orgName }}</strong>.
          </p>
        </div>

        <!-- CTA button -->
        <div style="text-align: center; margin-bottom: 28px;">
          <a href="{{ $registerUrl }}"
             style="display: inline-block; background: #0098D8; color: #ffffff; text-decoration: none;
                    font-size: 15px; font-weight: 600; padding: 14px 36px; border-radius: 8px;
                    letter-spacing: 0.2px;">
            Criar conta e aceitar convite
          </a>
        </div>

        <!-- Fallback link -->
        <p style="font-size: 12px; color: #9ca3af; text-align: center; margin: 0 0 4px;">
          Se o botão não funcionar, copie e cole este link no navegador:
        </p>
        <p style="font-size: 11px; color: #0098D8; text-align: center; word-break: break-all; margin: 0;">
          {{ $registerUrl }}
        </p>

      </div>

      <!-- Footer -->
      <div style="background: #f8fafc; border-top: 1px solid #e5e7eb; padding: 20px 40px; text-align: center;">
        <p style="font-size: 12px; color: #9ca3af; margin: 0 0 4px;">
          Este convite foi enviado por <strong>{{ $inviterName }}</strong> via plataforma eHub.
        </p>
        <p style="font-size: 12px; color: #9ca3af; margin: 0;">
          Se você não esperava este e-mail, pode ignorá-lo com segurança.
        </p>
      </div>

    </div>

    <!-- Bottom note -->
    <p style="font-size: 11px; color: #9ca3af; text-align: center; margin-top: 20px;">
      © {{ date('Y') }} eHub — Plataforma de e-sports
    </p>

  </div>
</body>
</html>
