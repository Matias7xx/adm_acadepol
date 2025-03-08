@component('mail::message')
# Nova Solicitação de Reserva de Alojamento

Foi recebida uma nova solicitação de reserva de alojamento com os seguintes dados:

**Dados do Solicitante:**
- **Nome:** {{ $alojamento->nome }}
- **Matrícula:** {{ $alojamento->matricula }}
- **Cargo/Função:** {{ $alojamento->cargo }}
- **Órgão/Instituição:** {{ $alojamento->orgao }}
- **CPF:** {{ $alojamento->cpf }}
- **Condição:** {{ $alojamento->condicao }}
- **Email:** {{ $alojamento->email }}
- **Telefone:** {{ $alojamento->telefone }}

**Dados da Reserva:**
- **Período:** {{ $alojamento->data_inicial->format('d/m/Y') }} a {{ $alojamento->data_final->format('d/m/Y') }}
- **Duração:** {{ $alojamento->duracao }} dia(s)
- **Motivo:** {{ $alojamento->motivo }}

@if($alojamento->endereco)
**Endereço:**
{{ $alojamento->endereco_formatado }}
@endif

@component('mail::button', ['url' => $url])
Ver Detalhes da Solicitação
@endcomponent

Esta solicitação aguarda análise e aprovação.

Atenciosamente,<br>
{{ config('app.name') }}
@endcomponent