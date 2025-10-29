<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_portalgestao',
        get_string('pluginname', 'local_portalgestao'),
        new moodle_url('/local/portalgestao/index.php'),
        'local/portalgestao:viewpanelgestor'
    ));
}
