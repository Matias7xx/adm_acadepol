@component('mail::message')
# Requerimento Indeferido

Prezado(a) {{ $requerimento->nome }},

Lamentamos informar que seu requerimento não pôde ser deferido neste momento.

**Detalhes do Requerimento:**
- **Tipo:** {{ $tipo_formatado }}
- **Data de Solicitação:** {{ $requerimento->created_at->format('d/m/Y') }}
- **Data da Resposta:** {{ $requerimento->data_resposta->format('d/m/Y') }}

**Motivo do Indeferimento:**

{{ $motivo }}

Caso precise de mais informações ou deseje fazer um novo requerimento, entre em contato conosco pelo portal da ACADEPOL.

Agradecemos sua compreensão.

Atenciosamente,<br>
{{ config('app.name') }}
@endcomponent