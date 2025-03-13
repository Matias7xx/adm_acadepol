<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações do Sistema de Alojamento
    |--------------------------------------------------------------------------
    |
    | Este arquivo contém as configurações para o sistema de reservas de alojamento.
    |
    */

    // Email do administrador para receber notificações de novas reservas
    'admin_email' => env('ALOJAMENTO_ADMIN_EMAIL', 'matiasnobrega7@gmail.com'),

    // Email institucional para receber cópia das notificações EMAIL DA ACADEPOL
    'institutional_email' => env('ALOJAMENTO_INSTITUTIONAL_EMAIL', 'nobregamatias7@gmail.com'),

    // Capacidade máxima de ocupação do alojamento
    'capacidade_maxima' => env('ALOJAMENTO_CAPACIDADE', 30),

    // Número de dias máximo para uma reserva
    'dias_maximos' => env('ALOJAMENTO_DIAS_MAXIMOS', 30),

    // Tipos de condição de alojado disponíveis
    'condicoes' => [
        'Professor' => 'Professor',
        'Aluno' => 'Aluno',
        'Visitante' => 'Visitante',
        'Outro' => 'Outro',
    ],

    // Status possíveis para uma reserva
    'status' => [
        'pendente' => 'Pendente',
        'aprovada' => 'Aprovada',
        'rejeitada' => 'Rejeitada',
    ],
];