<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Hospedagem - ACADEPOL</title>
    <style>
        @page {
            size: A4;
            margin: 1.2cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.1;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
        }
        .header-table {
            width: 100%;
            border: 2px solid #000;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .header-table td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: middle;
        }
        .logo-cell {
            width: 100px;
            text-align: center;
        }
        .logo-cell img {
            max-width: 90px;
            max-height: 40px;
        }
        .title-cell {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
        }
        .alojamento-cell {
            width: 120px;
            text-align: center;
            font-weight: bold;
        }
        .alojamento-label {
            font-size: 9pt;
            margin-bottom: 5px;
        }
        .alojamento-input {
            border-bottom: 1px solid #000;
            width: 80px;
            height: 20px;
            display: inline-block;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        td, th {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 8pt;
        }
        .field-value {
            min-height: 16px;
            font-size: 9pt;
        }
        .obs-section {
            border: 1px solid #000;
            padding: 8px;
            margin: 10px 0;
            text-align: center;
            font-size: 8pt;
        }
        .signature-section {
            margin-top: 15px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 300px;
            margin: 20px auto 3px auto;
        }
        .checkout-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .checkout-table td {
            width: 50%;
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
        }
        .checkout-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
            font-size: 10pt;
        }
        .checkout-data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .checkout-data-table td, 
        .checkout-data-table th {
            border: 1px solid #000;
            padding: 4px;
        }
        .checkout-data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 8pt;
        }
        .checkout-signature {
            text-align: center;
            margin-top: 10px;
        }
        .checkout-signature-line {
            border-top: 1px solid #000;
            width: 100%;
            margin: 10px 0 3px 0;
        }
        .checkout-signature-text {
            font-size: 7pt;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho com layout de tabela -->
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <img src="data:image/png;base64,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" alt="ACADEPOL">
                </td>
                <td class="title-cell">
                    FICHA DE HOSPEDAGEM
                </td>
                <td class="alojamento-cell">
                    <div class="alojamento-label">ALOJAMENTO</div>
                    <div class="alojamento-input">{{ $apartamento ?? '' }}</div>
                </td>
            </tr>
        </table>

        <!-- Dados Pessoais -->
        <table>
            <tr>
                <th style="width: 50%;">NOME:</th>
                <th style="width: 50%;">CARGO:</th>
            </tr>
            <tr>
                <td class="field-value">{{ $nome ?? '' }}</td>
                <td class="field-value">{{ $cargo ?? '' }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <th style="width: 25%;">MATRÍCULA:</th>
                <th style="width: 35%;">CPF:</th>
                <th style="width: 40%;">FONE/WHATSAPP:</th>
            </tr>
            <tr>
                <td class="field-value">{{ $matricula ?? '' }}</td>
                <td class="field-value">{{ $cpf ?? '' }}</td>
                <td class="field-value">{{ $telefone ?? '' }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <th style="width: 80%;">ENDEREÇO:</th>
                <th style="width: 20%;">Nº</th>
            </tr>
            <tr>
                <td class="field-value">{{ $endereco ?? '' }}</td>
                <td class="field-value">{{ $numero ?? '' }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <th style="width: 40%;">BAIRRO:</th>
                <th style="width: 60%;">CIDADE:</th>
            </tr>
            <tr>
                <td class="field-value">{{ $bairro ?? '' }}</td>
                <td class="field-value">{{ $cidade ?? '' }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <th style="width: 70%;">E-MAIL:</th>
                <th style="width: 30%;">CEP:</th>
            </tr>
            <tr>
                <td class="field-value">{{ $email ?? '' }}</td>
                <td class="field-value">{{ $cep ?? '' }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <th>MOTIVO DA RESERVA:</th>
            </tr>
            <tr>
                <td class="field-value" style="height: 20px;">{{ $motivo ?? '' }}</td>
            </tr>
        </table>

        <table>
            <tr>
                <th style="width: 25%;">DATA DE ENTRADA:</th>
                <th style="width: 25%;">DATA DE SAÍDA:</th>
            </tr>
            <tr>
                <td class="field-value">{{ $data_inicial ?? '' }}</td>
                <td class="field-value">{{ $data_final ?? '' }}</td>
            </tr>
        </table>

        <!-- Observação sobre termos -->
        <div class="obs-section">
            <strong>Obs.:</strong> Estou de acordo com os termos de uso do alojamento, conforme ciência 
            manifestada na ficha de pré-inscrição preenchida no site da Acadepol.
        </div>

        <!-- Assinatura do alojado -->
        <div class="signature-section">
            <div class="signature-line"></div>
            <div style="font-size: 8pt; margin-top: 3px;">ASSINATURA DO ALOJADO</div>
        </div>

        <!-- Seção de Check-in e Check-out lado a lado -->
        <table class="checkout-table">
            <tr>
                <td>
                    <div class="checkout-title">CHECK-IN</div>
                    <table class="checkout-data-table">
                        <tr>
                            <th>DATA E HORA</th>
                        </tr>
                        <tr>
                            <td class="field-value" style="height: 20px;">{{ $check_in_data ?? '' }} {{ $check_in_hora ?? '' }}</td>
                        </tr>
                    </table>
                    <div class="checkout-signature">
                        <div class="checkout-signature-line"></div>
                        <div class="checkout-signature-text">ASS. RESPONSÁVEL PELO CHECK-IN</div>
                    </div>
                </td>
                <td>
                    <div class="checkout-title">CHECK-OUT</div>
                    <table class="checkout-data-table">
                        <tr>
                            <th>DATA E HORA</th>
                        </tr>
                        <tr>
                            <td class="field-value" style="height: 20px;">{{ $check_out_data ?? '' }} {{ $check_out_hora ?? '' }}</td>
                        </tr>
                    </table>
                    <div class="checkout-signature">
                        <div class="checkout-signature-line"></div>
                        <div class="checkout-signature-text">ASS. RESPONSÁVEL PELO CHECK-OUT</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>