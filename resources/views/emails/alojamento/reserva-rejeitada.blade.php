@component('mail::message')
# Reserva de Alojamento Não Aprovada

Prezado(a) {{ $alojamento->nome }},

Lamentamos informar que sua solicitação de reserva de alojamento para o período de {{ $alojamento->data_inicial->format('d/m/Y') }} a {{ $alojamento->data_final->format('d/m/Y') }} não pôde ser aprovada.

@if($alojamento->motivo_rejeicao)
**Motivo:**
{{ $alojamento->motivo_rejeicao }}
@endif

Caso precise de mais informações ou deseje fazer uma nova solicitação para outro período, entre em contato conosco.

Agradecemos sua compreensão.

Atenciosamente,<br>
Administração de Alojamentos<br>
{{ config('app.name') }}
@endcomponent