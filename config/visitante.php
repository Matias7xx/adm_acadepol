<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações do Sistema de Visitantes
    |--------------------------------------------------------------------------
    |
    |
    */

    'admin_email' => env('VISITANTE_ADMIN_EMAIL', env('ALOJAMENTO_ADMIN_EMAIL', 'matiasnobrega7@gmail.com')),

    'institutional_email' => env('VISITANTE_INSTITUTIONAL_EMAIL', env('ALOJAMENTO_INSTITUTIONAL_EMAIL', 'nobregamatias7@gmail.com')),

    // Status possíveis para uma reserva de visitante
    'status' => [
        'pendente' => 'Pendente',
        'aprovada' => 'Aprovada',
        'rejeitada' => 'Rejeitada',
    ],

    // Tipos de órgãos disponíveis
    'tipos_orgao' => [
        'policia_civil' => 'Polícia Civil',
        'policia_militar' => 'Polícia Militar',
        'bombeiros' => 'Corpo de Bombeiros',
        'policia_federal' => 'Polícia Federal',
        'policia_rodoviaria' => 'Polícia Rodoviária Federal',
        'guarda_municipal' => 'Guarda Municipal',
        'poder_judiciario' => 'Poder Judiciário',
        'ministerio_publico' => 'Ministério Público',
        'defensoria_publica' => 'Defensoria Pública',
        'outro' => 'Outro'
    ],

    // Condições de reserva disponíveis
    'condicoes' => [
        'curso' => 'Participação em Curso',
        'trabalho' => 'Trabalho/Reunião',
        'Instrutor Convidado' => 'Instrutor Convidado',
        'Palestrante' => 'Palestrante',
        'visita_tecnica' => 'Visita Técnica',
        'evento' => 'Evento/Capacitação',
        'outro' => 'Outro'
    ],

    // Capacidade máxima de ocupação do alojamento
    //'capacidade_maxima' => env('VISITANTE_CAPACIDADE', 30),

    // Número de dias máximo para uma reserva
    //'dias_maximos' => env('VISITANTE_DIAS_MAXIMOS', 30),

    'documentos_obrigatorios' => [
        'documento_identidade' => 'Documento de Identidade',
        'documento_funcional' => 'Documento Funcional (Opcional)',
        'documento_comprobatorio' => 'Documento Comprobatório da Atividade',
    ],
];