@extends('errors.layout')

@section('code', '500')
@section('title', 'Erro Interno do Servidor')
@section('message', 'Ocorreu um erro interno no servidor.')
@section('icon', 'fas fa-server')
@section('icon_class', 'error-500')

@section('details')
    <div class="error-details">
        <h3>O que aconteceu?</h3>
        <p>Um erro inesperado ocorreu durante o processamento da sua solicitação.</p>
    </div>
@endsection

@section('extra_buttons')
    <a href="javascript:location.reload()" class="btn btn-secondary">
        <i class="fas fa-sync-alt"></i>
        Tentar Novamente
    </a>
@endsection