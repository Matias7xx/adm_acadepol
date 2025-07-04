@extends('errors.layout')

@section('code', '404')
@section('title', 'Página Não Encontrada')
@section('message', 'A página que você está procurando não existe.')
@section('icon', 'fas fa-search')
@section('icon_class', 'error-404')

@section('details')
    <div class="error-details">
        <h3>O que aconteceu?</h3>
        <p>O recurso solicitado não foi encontrado no servidor. Verifique se o endereço está correto ou se a página foi movida para outro local.</p>
    </div>
@endsection