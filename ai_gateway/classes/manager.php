<?php
namespace local_ai_gateway;

class manager {

    public static function generate(string $prompt): array {

        $providername = get_config('local_ai_gateway', 'provider');

        if ($providername === 'mock') {
            $provider = new mock_provider();
        } else {
            $provider = new openai_provider();
        }

        $result = $provider->generate_text($prompt);

        self::log_usage($result);

        return $result;
    }

    private static function log_usage(array $result) {
        global $DB, $USER;

        $record = new \stdClass();
        $record->userid = $USER->id;
        $record->cost = $result['cost'];
        $record->input_tokens = $result['input_tokens'];
        $record->output_tokens = $result['output_tokens'];
        $record->timecreated = time();

        $DB->insert_record('local_ai_gateway_log', $record);
    }
}