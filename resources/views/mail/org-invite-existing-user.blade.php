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
          Novo convite para você 👋
        </h1>
        <p style="font-size: 15px; color: #4b5563; margin: 0 0 28px; line-height: 1.6;">
          <strong style="color: #111827;">{{ $inviterName }}</strong> convidou você para gerenciar
          a organização <strong style="color: #0098D8;">{{ $orgName }}</strong> no eHub.
        </p>

        <!-- Role badge -->
        <div style="margin-bottom: 28px;">
          <p style="font-size: 13px; color: #6b7280; margin: 0 0 8px;">Seu papel na organização:</p>
          <span style="display: inline-block; background: #eff6ff; color: #1d4ed8; font-size: 13px;
                        font-weight: 600; padding: 5px 14px; border-radius: 20px; border: 1px solid #bfdbfe;">
            {{ $role }}
          </span>
        </div>

        <!-- What you can do -->
        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px;
                    padding: 16px 20px; margin-bottom: 32px;">
          <p style="font-size: 14px; color: #0c4a6e; margin: 0 0 10px; font-weight: 600;">
            O que você poderá fazer em <span style="color: #0098D8;">{{ $orgName }}</span>:
          </p>
          <ul style="font-size: 13px; color: #374151; margin: 0; padding-left: 18px; line-height: 2;">
            <li>Acessar o painel de gerenciamento da organização</li>
            <li>Gerenciar eventos e torneios</li>
            <li>Colaborar com outros membros da equipe</li>
          </ul>
        </div>

        <!-- CTA button -->
        <div style="text-align: center; margin-bottom: 16px;">
          <a href="{{ $acceptUrl }}"
             style="display: inline-block; background: #0098D8; color: #ffffff; text-decoration: none;
                    font-size: 15px; font-weight: 600; padding: 14px 36px; border-radius: 8px;
                    letter-spacing: 0.2px;">
            Aceitar convite
          </a>
        </div>

        <!-- Secondary: decline -->
        <p style="text-align: center; margin: 0 0 24px;">
          <a href="{{ $acceptUrl }}?action=decline"
             style="font-size: 13px; color: #9ca3af; text-decoration: underline;">
            Recusar convite
          </a>
        </p>

        <!-- Fallback link -->
        <p style="font-size: 12px; color: #9ca3af; text-align: center; margin: 0 0 4px;">
          Se o botão não funcionar, copie e cole este link no navegador:
        </p>
        <p style="font-size: 11px; color: #0098D8; text-align: center; word-break: break-all; margin: 0;">
          {{ $acceptUrl }}
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
