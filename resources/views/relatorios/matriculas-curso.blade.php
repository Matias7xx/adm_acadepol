<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Servidores Matriculados</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .info {
            margin-bottom: 20px;
        }
        .info p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        .center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">LISTA DE SERVIDORES MATRICULADOS</div>
    </div>

    <div class="info">
        <p><strong>Curso:</strong> {{ $curso->nome }}</p>
        <p><strong>Total de Servidores:</strong> {{ $total }}</p>
        <p><strong>Data de Geração:</strong> {{ $data_geracao }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nome do Servidor</th>
                <th>Matrícula</th>
                <th>Email</th>
                <th>Telefone</th>
                <th>Data de Inscrição</th>
            </tr>
        </thead>
        <tbody>
            @forelse($matriculas as $matricula)
                <tr>
                    <td>{{ $matricula->aluno->name ?? 'N/A' }}</td>
                    <td class="center">{{ $matricula->aluno->matricula ?? 'N/A' }}</td>
                    <td>{{ $matricula->aluno->email ?? 'N/A' }}</td>
                    <td class="center">{{ $matricula->aluno->telefone ?? 'N/A' }}</td>
                    <td class="center">{{ $matricula->created_at->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center">Nenhuma matrícula aprovada encontrada para este curso.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Documento gerado automaticamente pelo sistema em {{ $data_geracao }}</p>
    </div>
</body>
</html>