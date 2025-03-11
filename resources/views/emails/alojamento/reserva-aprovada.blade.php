@component('mail::message')
# Reserva de Alojamento Aprovada

Prezado(a) {{ $alojamento->nome }},

Temos o prazer de informar que sua solicitação de reserva de alojamento foi **APROVADA**.

**Detalhes da Reserva:**
- **Período:** {{ $alojamento->data_inicial->format('d/m/Y') }} a {{ $alojamento->data_final->format('d/m/Y') }}
- **Duração:** {{ $alojamento->duracao }} dia(s)

**Informações Importantes:**
- Horário de check-in: entre 8h e 18h
- Horário de check-out: até 12h do dia de saída
- Apresentar documento de identificação na chegada
- Lembre-se que a ACADEPOL NÃO FORNECE materiais de higiene pessoal, lençóis, cobertores, travesseiros e toalhas
- Os quartos são compartilhados (beliches) e separados por sexo

Em caso de necessidade de cancelamento, favor entrar em contato com antecedência.

Agradecemos sua compreensão e desejamos uma boa estadia.

Atenciosamente,<br>
Administração de Alojamentos<br>
{{ config('app.name') }}
@endcomponent