@component('mail::message')
# Matrícula Aprovada

Olá **{{ $aluno->name }}**,

Temos o prazer de informar que sua matrícula no curso **{{ $curso->nome }}** foi **APROVADA**.

## Detalhes do curso:
- **Nome do curso:** {{ $curso->nome }}
- **Data de início:** {{ (new \DateTime($curso->data_inicio))->format('d/m/Y') }}
- **Data de término:** {{ (new \DateTime($curso->data_fim))->format('d/m/Y') }}
- **Carga horária:** {{ $curso->carga_horaria }} horas
- **Local:** {{ $curso->localizacao }}

@if(!empty(json_decode($curso->enxoval, true)))
## Material necessário:
@foreach(json_decode($curso->enxoval, true) as $item)
- {{ $item }}
@endforeach
@endif

Lembre-se de comparecer no local indicado com **15 minutos de antecedência** no primeiro dia. Não se esqueça de trazer sua identidade funcional.

Se tiver alguma dúvida ou precisar de mais informações, entre em contato conosco pelo portal da ACADEPOL.

Atenciosamente,<br>
{{ config('app.name') }}
@endcomponent