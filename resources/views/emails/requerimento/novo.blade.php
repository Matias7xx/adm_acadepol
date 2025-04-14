@component('mail::message')
# Novo Requerimento Recebido

Foi recebido um novo requerimento com os seguintes dados:

**Dados do Requerente:**
- **Nome:** {{ $requerimento->nome }}
- **Matrícula:** {{ $requerimento->matricula }}
@if($requerimento->cargo)
- **Cargo/Função:** {{ $requerimento->cargo }}
@endif
@if($requerimento->orgao)
- **Órgão/Instituição:** {{ $requerimento->orgao }}
@endif
- **Email:** {{ $requerimento->email }}
- **Telefone:** {{ $requerimento->telefone }}

**Detalhes do Requerimento:**
- **Tipo:** {{ $tipo_formatado }}
- **Data de Solicitação:** {{ $requerimento->created_at->format('d/m/Y H:i') }}

**Conteúdo do Requerimento:**
{{ $requerimento->conteudo }}

@if($requerimento->documento)
O requerente anexou um documento a esta solicitação.
@endif

@component('mail::button', ['url' => $url])
Ver Detalhes do Requerimento
@endcomponent

@endcomponent