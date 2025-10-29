<?php
defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_portalgestao_list_company_users' => [
        'classname'   => 'local_portalgestao\\external\\alunos_api',
        'methodname'  => 'list_company_users',
        'classpath'   => '',
        'description' => 'Lista usuários vinculados à empresa do gestor.',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'local_portalgestao_create_batch' => [
        'classname'   => 'local_portalgestao\\external\\alunos_api',
        'methodname'  => 'create_batch',
        'classpath'   => '',
        'description' => 'Cria usuários em lote (JSON) e matricula por shortname.',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'local_portalgestao_toggle_suspend' => [
        'classname'   => 'local_portalgestao\\external\\alunos_api',
        'methodname'  => 'toggle_suspend',
        'classpath'   => '',
        'description' => 'Ativa/Inativa usuário (suspended 0/1).',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'local_portalgestao_delete_user' => [
        'classname'   => 'local_portalgestao\\external\\alunos_api',
        'methodname'  => 'delete_user',
        'classpath'   => '',
        'description' => 'Exclui usuário (hard delete).',
        'type'        => 'write',
        'ajax'        => true,
    ],
];

$services = [
    'local_portalgestao_services' => [
        'functions' => array_keys($functions),
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
