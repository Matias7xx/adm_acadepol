@component('mail::message')
# Reserva de Alojamento Aprovada! ✅

Olá {{ $visitante->nome }},

Temos o prazer de informar que sua solicitação de reserva de alojamento na ACADEPOL foi **APROVADA**.

## Detalhes da sua Reserva
- **Nome:** {{ $visitante->nome }}
- **Período:** {{ $dataInicial }} a {{ $dataFinal }}
- **Órgão:** {{ $visitante->orgao_trabalho }}
- **Cargo:** {{ $visitante->cargo }}

## ⚠️ Informações Importantes para Check-in

### Documentação Obrigatória
- Documento de identificação oficial com foto
- Documento funcional (se aplicável)

### Horários
- **Check-in:** Entre 8h e 18h
- **Check-out:** Até 12h do dia de saída

### O que levar
A ACADEPOL **NÃO FORNECE**:
- Materiais de higiene pessoal
- Lençóis e cobertores
- Toalhas
- Travesseiros

### Acomodações
- Quartos compartilhados (beliches)
- Separação por sexo
- Ambiente coletivo

## Contato
Em caso de dúvidas ou necessidade de reagendamento, entre em contato conosco.

Aguardamos sua chegada!

Atenciosamente,<br>
**Equipe ACADEPOL**
@endcomponent