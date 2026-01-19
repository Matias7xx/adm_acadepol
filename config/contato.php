<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações do Sistema de Contato
    |--------------------------------------------------------------------------
    |
    | Este arquivo contém as configurações para o sistema de mensagens de contato.
    |
    */

    // Email do administrador para receber notificações de novos contatos
    //'admin_email' => env('CONTATO_ADMIN_EMAIL', 'matiasnobrega7@gmail.com'),

    // Email institucional para receber cópia das notificações EMAIL DA ACADEPOL
    'institutional_email' => env('EMAIL_INSTITUCIONAL', 'acadepol@gmail.com'),

    // Status possíveis para uma mensagem
    'status' => [
        'pendente' => 'Pendente',
        'respondido' => 'Respondido',
        'arquivado' => 'Arquivado',
    ],

    // Assuntos predefinidos
    'assuntos' => [
        'Informações sobre cursos',
        'Dúvidas sobre matrícula',
        'Problemas no sistema',
        'Alojamento',
        'Certificados',
        'Outros assuntos'
    ],

    // Tempo máximo (em dias) estimado para resposta
    'tempo_resposta' => env('CONTATO_TEMPO_RESPOSTA', 3),
];
