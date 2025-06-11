@component('mail::message')
# Nova Solicitação de Reserva de Visitante

Olá Administrador,

Uma nova solicitação de reserva de alojamento foi recebida de um visitante externo.

## Dados do Visitante
- **Nome:** {{ $visitante->nome }}
- **Email:** {{ $visitante->email }}
- **Telefone:** {{ $visitante->telefone }}
- **CPF:** {{ $visitante->cpf }}

## Dados Profissionais
- **Órgão de Trabalho:** {{ $visitante->orgao_trabalho }}
- **Cargo:** {{ $visitante->cargo }}
- **Matrícula Funcional:** {{ $visitante->matricula_funcional ?? 'Não informado' }}

## Período Solicitado
- **Data de Entrada:** {{ $dataInicial }}
- **Data de Saída:** {{ $dataFinal }}

## Motivo da Solicitação
{{ $visitante->motivo }}

@component('mail::button', ['url' => $url])
Gerenciar Solicitação
@endcomponent

Esta solicitação aguarda sua análise no sistema administrativo.

Atenciosamente,<br>
{{ config('app.name') }}
@endcomponent