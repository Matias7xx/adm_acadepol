@component('mail::message')
# Resposta ao seu contato

Prezado(a) {{ $contato->nome }},

Recebemos sua mensagem enviada em {{ $contato->created_at->format('d/m/Y') }} com o assunto "{{ $contato->assunto }}" e estamos respondendo conforme solicitado.

**Sua mensagem:**

{{ $contato->mensagem }}

**Nossa resposta:**

{!! $contato->resposta !!}

Caso precise de mais informações, sinta-se à vontade para enviar uma nova mensagem através do nosso formulário de contato.

Atenciosamente,<br>
{{ $contato->respondente ? $contato->respondente->name : 'Equipe' }} - {{ config('app.name') }}
@endcomponent