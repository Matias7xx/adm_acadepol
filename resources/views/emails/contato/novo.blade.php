@component('mail::message')
# Nova Mensagem de Contato

Você recebeu uma nova mensagem de contato através do site da ACADEPOL.

**Dados da mensagem:**

- **Nome:** {{ $contato->nome }}
- **Email:** {{ $contato->email }}
@if($contato->telefone)
- **Telefone:** {{ $contato->telefone }}
@endif
- **Assunto:** {{ $contato->assunto }}
- **Data:** {{ $contato->created_at->format('d/m/Y H:i') }}

**Mensagem:**

{{ $contato->mensagem }}

@component('mail::button', ['url' => $url])
Responder Mensagem
@endcomponent

*Este é um email automático, por favor não responda diretamente.*

Atenciosamente,<br>
{{ config('app.name') }}
@endcomponent