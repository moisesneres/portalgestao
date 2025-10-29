<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Executado apenas na instalação do plugin.
 */
function xmldb_local_portalgestao_install(): bool {
    // Zera caches gerais e de tema.
    purge_all_caches();
    theme_reset_all_caches();

    return true;
}
