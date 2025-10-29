<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/portalgestao:viewpanelgestor' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    'local/portalgestao:manageusers' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
];
