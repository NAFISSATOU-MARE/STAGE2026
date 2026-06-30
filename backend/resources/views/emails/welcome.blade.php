<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur DGB Gestion des Congés</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .email-wrap {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .email-card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .header {
            background: linear-gradient(135deg, #0D2157 0%, #1a3a8f 100%);
            padding: 30px 40px;
            text-align: center;
        }
        .header img {
            height: 80px;
            width: auto;
        }
        .header h1 {
            color: #ffffff;
            font-size: 22px;
            margin: 15px 0 0 0;
            font-weight: 700;
        }
        .body {
            padding: 35px 40px;
        }
        .body p {
            color: #333;
            font-size: 15px;
            line-height: 1.7;
            margin: 0 0 15px 0;
        }
        .credentials {
            background: #f0f4ff;
            border-left: 4px solid #0D2157;
            border-radius: 8px;
            padding: 20px 25px;
            margin: 20px 0;
        }
        .credentials dt {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 12px;
        }
        .credentials dt:first-child { margin-top: 0; }
        .credentials dd {
            font-size: 16px;
            color: #0D2157;
            font-weight: 600;
            margin: 3px 0 0 0;
            font-family: 'Courier New', monospace;
        }
        .alert-box {
            background: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 20px 0;
            font-size: 14px;
            color: #6d5200;
        }
        .alert-box strong {
            color: #333;
        }
        .btn {
            display: inline-block;
            background: #0D2157;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            margin: 10px 0 5px 0;
        }
        .btn:hover {
            background: #1a3a8f;
        }
        .footer {
            text-align: center;
            padding: 20px 40px;
            font-size: 12px;
            color: #aaa;
            border-top: 1px solid #eee;
        }
        .footer strong {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="email-wrap">
        <div class="email-card">
            {{-- Header --}}
            <div class="header">
                <img src="{{ $message->embed(public_path('images/dgb-logo.png')) }}"
                     alt="Logo DGB">
                <h1>Direction Générale du Budget</h1>
            </div>

            {{-- Body --}}
            <div class="body">
                <p>Bonjour <strong>{{ $agent->prenom }} {{ $agent->nom }}</strong>,</p>

                <p>Votre compte a été créé avec succès sur la plateforme
                <strong>DGB Gestion des Congés</strong>. Vous trouverez ci-dessous
                vos identifiants de connexion.</p>

                <dl class="credentials">
                    <dt>Adresse email</dt>
                    <dd>{{ $agent->email }}</dd>

                    <dt>Mot de passe temporaire</dt>
                    <dd>{{ $password }}</dd>
                </dl>

                <div class="alert-box">
                    <strong>⚠️ Sécurité :</strong> Pour des raisons de sécurité,
                    vous devrez <strong>changer votre mot de passe</strong> dès
                    votre première connexion.
                </div>

                <p style="text-align: center;">
                    <a href="{{ config('app.url') }}/login" class="btn">
                        Se connecter
                    </a>
                </p>

                <p style="font-size: 13px; color: #888;">
                    Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
                    <a href="{{ config('app.url') }}/login" style="color: #0D2157;">
                        {{ config('app.url') }}/login
                    </a>
                </p>
            </div>

            {{-- Footer --}}
            <div class="footer">
                <p><strong>Direction Générale du Budget (DGB)</strong><br>
                Ce message est généré automatiquement, merci de ne pas y répondre.</p>
            </div>
        </div>
    </div>
</body>
</html>
