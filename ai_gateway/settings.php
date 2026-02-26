<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage(
        'local_ai_gateway',
        get_string('pluginname', 'local_ai_gateway')
    );

    if ($ADMIN->fulltree) {

        $settings->add(new admin_setting_configtext(
            'local_ai_gateway/apikey',
            'OpenAI API Key',
            'API key for AI provider',
            ''
        ));

        $settings->add(new admin_setting_configselect(
            'local_ai_gateway/provider',
            'AI Provider',
            'Select provider',
            'mock',
            [
                'mock' => 'Mock Provider',
                'openai' => 'OpenAI'
            ]
        ));
    }

    $ADMIN->add('localplugins', $settings);
}