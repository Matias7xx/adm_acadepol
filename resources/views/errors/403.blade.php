@extends('errors.layout')

@section('code', '403')
@section('title', 'Acesso Negado')
@section('message', 'Você não tem permissão para acessar este recurso.')
@section('icon', 'fas fa-shield-alt')
@section('icon_class', 'error-403')

@section('details')
    <div class="error-details">
        <h3>O que aconteceu?</h3>
        <p>Sua solicitação foi negada devido a permissões insuficientes para acessar este recurso. Verifique se você possui as credenciais adequadas.</p>
    </div>
@endsection