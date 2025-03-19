@component('mail::message')
# Matrícula Não Aprovada

Olá **{{ $aluno->name }}**,

Gostaríamos de informar que sua matrícula no curso **{{ $curso->nome }}** não foi aprovada neste momento.

## Detalhes da solicitação:
- **Nome do curso:** {{ $curso->nome }}
- **Data de solicitação:** {{ $matricula->created_at->format('d/m/Y') }}

@if($motivo)
## Motivo:
{{ $motivo }}
@endif

Você pode tentar uma nova inscrição em uma próxima oferta deste curso ou em outros cursos disponíveis em nosso portal.

Se tiver alguma dúvida ou precisar de esclarecimentos adicionais, entre em contato conosco pelo portal da ACADEPOL.

Atenciosamente,<br>
{{ config('app.name') }}
@endcomponent