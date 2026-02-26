<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_ai_gateway_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026022301) {

        $table = new xmldb_table('local_ai_gateway_log');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('input_tokens', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('output_tokens', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('cost', XMLDB_TYPE_NUMBER, '10,5', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026022301, 'local', 'ai_gateway');
    }

    return true;
}