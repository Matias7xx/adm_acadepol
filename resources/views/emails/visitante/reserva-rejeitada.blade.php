@component('mail::message')
# Reserva de Alojamento Não Aprovada

Olá {{ $visitante->nome }},

Infelizmente, não foi possível aprovar sua solicitação de reserva de alojamento na ACADEPOL.

## Detalhes da Solicitação
- **Nome:** {{ $visitante->nome }}
- **Período solicitado:** {{ $dataInicial }} a {{ $dataFinal }}
- **Órgão:** {{ $visitante->orgao_trabalho }}

## Motivo da Não Aprovação
{{ $motivoRejeicao }}

---

Se você acredita que houve algum engano ou gostaria de esclarecer alguma informação, não hesite em entrar em contato conosco.

Você pode fazer uma nova solicitação quando as condições permitirem.

Atenciosamente,<br>
**Equipe ACADEPOL**
@endcomponent