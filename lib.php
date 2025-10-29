<?php
defined('MOODLE_INTERNAL') || die();

function local_portalgestao_extend_navigation(global_navigation $nav) {
    $context = context_system::instance();
    if (!has_capability('local/portalgestao:viewpanelgestor', $context)) {
        return;
    }

    $node = $nav->add(get_string('pluginname', 'local_portalgestao'), new moodle_url('/local/portalgestao/index.php'));
    $node->showinflatnavigation = true;
}

function local_portalgestao_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    if (!has_capability('local/portalgestao:viewpanelgestor', context_system::instance())) {
        return;
    }

    $node = navigation_node::create(
        get_string('pluginname', 'local_portalgestao'),
        new moodle_url('/local/portalgestao/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_portalgestao'
    );
    $settingsnav->add_node($node);
}
