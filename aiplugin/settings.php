<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'aiprovider_aiplugin',
        get_string('pluginname', 'aiprovider_aiplugin')
    );

    if ($ADMIN->fulltree) {

        $settings->add(new admin_setting_configselect(
            'aiprovider_aiplugin/underlyingprovider',
            get_string('underlyingprovider', 'aiprovider_aiplugin'),
            get_string('underlyingprovider_desc', 'aiprovider_aiplugin'),
            '',
            $provideroptions
        ));
    }

    $ADMIN->add('localplugins', $settings);
}