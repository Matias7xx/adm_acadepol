<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações do Sistema de Requerimentos
    |--------------------------------------------------------------------------
    |
    | Este arquivo contém as configurações para o sistema de requerimentos.
    |
    */

    // Email do administrador para receber notificações de novos requerimentos
    //'admin_email' => env('REQUERIMENTO_ADMIN_EMAIL', 'matiasnobrega7@gmail.com'),

    // Email institucional para receber cópia das notificações EMAIL DA ACADEPOL
    'institutional_email' => env('EMAIL_INSTITUCIONAL', 'acadepol@gmail.com'),

    // Status possíveis para um requerimento
    'status' => [
        'pendente' => 'Pendente',
        'deferido' => 'Deferido',
        'indeferido' => 'Indeferido',
    ],

    // Tipos de requerimentos disponíveis
    'tipos' => [
        'segunda_via_certificado' => '2ª Via de Certificado',
        'declaracao_participacao' => 'Declaração de Participação em Curso',
        'outros' => 'Outros',
    ],
];
