@component('mail::message')
# Requerimento Deferido

Prezado(a) {{ $requerimento->nome }},

Temos o prazer de informar que seu requerimento foi **DEFERIDO**.

**Detalhes do Requerimento:**
- **Tipo:** {{ $tipo_formatado }}
- **Data de Solicitação:** {{ $requerimento->created_at->format('d/m/Y') }}
- **Data da Resposta:** {{ $requerimento->data_resposta->format('d/m/Y') }}

**Resposta ao Requerimento:**

{!! $requerimento->resposta !!}

@if($requerimento->documento_resposta)
Em anexo, enviamos o documento solicitado. Caso não consiga visualizar o anexo, entre em contato conosco.
@endif

Se tiver alguma dúvida ou precisar de mais informações, entre em contato conosco pelo portal da ACADEPOL.

Atenciosamente,<br>
{{ config('app.name') }}
@endcomponent