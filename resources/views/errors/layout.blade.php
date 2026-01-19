{{-- resources/views/errors/layout.blade.php - VERSÃO CORRIGIDA --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erro @yield('code', '500') - ACADEPOL</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            max-width: 600px;
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(190, 165, 90, 0.2);
            animation: fadeInUp 0.6s ease-out;
        }

        .error-icon {
            font-size: 6rem;
            margin-bottom: 1.5rem;
            display: block;
            animation: bounce 2s infinite;
        }

        .error-403 { color: #dc2626; }
        .error-404 { color: #bea55a; }
        .error-500 { color: #ef4444; }
        .error-419 { color: #8b5cf6; }
        .error-429 { color: #0ea5e9; }
        .error-503 { color: #6b7280; }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .error-code {
            font-size: 8rem;
            font-weight: 900;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, #000000, #bea55a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .error-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .error-message {
            font-size: 1.2rem;
            color: #4b5563;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .error-details {
            background: #f9fafb;
            border-radius: 12px;
            padding: 1.5rem;
            margin: 2rem 0;
            border-left: 4px solid #bea55a;
        }

        .error-details h3 {
            color: #1f2937;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .error-details p {
            color: #4b5563;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background:#bea55a;
            color: white;
            box-shadow: 0 4px 15px rgba(190, 165, 90, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(190, 165, 90, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #1f2937;
            border: 2px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #1f2937;
            color: white;
            transform: translateY(-2px);
            border-color: #1f2937;
        }

        .footer-info {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            color: #95a5a6;
            font-size: 0.9rem;
        }

        .footer-info a {
            color: #bea55a;
            text-decoration: none;
        }

        .footer-info a:hover {
            text-decoration: underline;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .error-container {
                padding: 2rem;
                margin: 1rem;
            }

            .error-code {
                font-size: 5rem;
            }

            .error-title {
                font-size: 2rem;
            }

            .error-message {
                font-size: 1.1rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 280px;
            }
        }

        /* Tema escuro */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            }

            .error-container {
                background: rgba(26, 26, 26, 0.98);
                color: white;
                border: 1px solid rgba(190, 165, 90, 0.3);
            }

            .error-title {
                color: #f3f4f6;
            }

            .error-message {
                color: #d1d5db;
            }

            .error-details {
                background: rgba(55, 65, 81, 0.7);
                border-left-color: #bea55a;
            }

            .error-details h3 {
                color: #f3f4f6;
            }

            .error-details p {
                color: #d1d5db;
            }

            .btn-secondary {
                background: rgba(255, 255, 255, 0.1);
                color: #d1d5db;
                border-color: #4b5563;
            }

            .btn-secondary:hover {
                background: #374151;
                color: white;
                border-color: #bea55a;
            }

            .footer-info {
                color: #9ca3af;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <!-- Ícone do erro -->
        <i class="error-icon @yield('icon', 'fas fa-exclamation-triangle') @yield('icon_class', 'error-500')"></i>

        <!-- Código do erro -->
        <div class="error-code">@yield('code', '500')</div>

        <!-- Título do erro -->
        <h1 class="error-title">@yield('title', 'Erro do Servidor')</h1>

        <!-- Mensagem do erro -->
        <p class="error-message">@yield('message', 'Ocorreu um erro inesperado. Tente novamente mais tarde.')</p>

        <!-- Detalhes específicos do erro -->
        @hasSection('details')
            @yield('details')
        @else
            <div class="error-details">
                <h3>O que aconteceu?</h3>
                <p>Um erro inesperado ocorreu durante o processamento da sua solicitação.</p>
            </div>
        @endif

        <!-- Botões de ação -->
        <div class="action-buttons">
            @yield('extra_buttons')

            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Voltar
            </a>

            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Página Inicial
            </a>
        </div>

        <!-- Informações do rodapé -->
        <div class="footer-info">
            <p class="text-xs text-gray-500">
                        Em caso de dúvidas, solicite atendimento à Diretoria de Tecnologia da Informação <a href="https://helpdesk.apps.pc.pb.gov.br/login">por meio de um chamado.</a>
                    </p>
            <p>© {{ date('Y') }} ACADEPOL - Academia de Polícia Civil da Paraíba</p>
        </div>
    </div>
</body>
</html>
