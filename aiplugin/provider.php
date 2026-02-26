<?php
namespace aiprovider_aiplugin;
use core_ai\manager;

class provider extends \core_ai\provider {
    /**
     * Get the list of actions that this provider supports.
     *
     * @return array An array of action class names.
     */
    public static function get_action_list(): array {
        return [];
    }

    private function get_real_provider(){
        $providername = get_config('aiprovider_aiplugin', 'underlyingprovider');

        if (empty($providername)) {
            throw new moodle_exception('nounderlyingprovider', 'aiprovider_aiplugin');
        }

        if ($providername === 'governance') {
            throw new moodle_exception('recursiveprovider', 'aiprovider_aiplugin');
        }

        $manager = \core_ai\manager::instance();

        $provider = $manager->get_provider($providername);

        if (!$provider) {
            throw new moodle_exception('invalidprovider', 'aiprovider_aiplugin');
        }

        return $provider;
    }

    public function generate_text($prompt, $options) {

        global $USER;

        $userid = $USER->id;

        // Check limits
        //$this->enforce_limits($userid, $prompt);

        // Call real provider
        $provider = $this->get_real_provider();
        $response = $provider->generate_text($prompt, $options);

        // Extract usage
        $usage = $response->get_usage();

        // Log usage
        //$this->log_usage($userid, $usage);

        return $response;
    }
}