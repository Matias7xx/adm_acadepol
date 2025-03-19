<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações do Sistema de Matrículas
    |--------------------------------------------------------------------------
    |
    | Este arquivo contém as configurações para o sistema de matrículas em cursos.
    |
    */

    // Email do administrador para receber notificações de novas matrículas
    'admin_email' => env('MATRICULA_ADMIN_EMAIL', 'matiasnobrega7@gmail.com'),

    // Email institucional para receber cópia das notificações EMAIL DA ACADEPOL
    'institutional_email' => env('MATRICULA_INSTITUTIONAL_EMAIL', 'nobregamatias7@gmail.com'),

    // Status possíveis para uma matrícula
    'status' => [
        'pendente' => 'Pendente',
        'aprovada' => 'Aprovada',
        'rejeitada' => 'Rejeitada',
    ],
    
    // Tempo máximo (em dias) para analisar uma matrícula
    'tempo_analise' => env('MATRICULA_TEMPO_ANALISE', 5),
];