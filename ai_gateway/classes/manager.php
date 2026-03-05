<?php
namespace local_ai_gateway;

class manager {

    public static function generate(string $prompt, ?string $custom_prompt = null): array {

        global $USER;

        // Check token limits before generating
        self::check_token_limit($USER->id);

        // Use custom prompt or get role-specific prompt
        $system_prompt = $custom_prompt ?: self::get_role_prompt($USER->id);
        $user_prompt = $prompt;

        $providername = get_config('local_ai_gateway', 'provider');

        if ($providername === 'mock') {
            $provider = new mock_provider();
        } else {
            $provider = new openai_provider();
        }

        $result = $provider->generate_text($system_prompt, $user_prompt);

        self::log_usage($result);

        return $result + ['system_prompt' => $system_prompt];
    }

    public static function summarize(string $text, ?string $custom_prompt = null): array {

        global $USER;

        // Check token limits before summarizing
        self::check_token_limit($USER->id);

        // Use custom prompt or get role-specific prompt
        $system_prompt = $custom_prompt ?: self::get_role_summarize_prompt($USER->id);

        $providername = get_config('local_ai_gateway', 'provider');

        if ($providername === 'mock') {
            $provider = new mock_provider();
        } else {
            $provider = new openai_provider();
        }

        $result = $provider->summarize_text($system_prompt, $text);

        self::log_usage($result);

        return $result + ['system_prompt' => $system_prompt];
    }

    /**
     * Check if user has exceeded their daily token limit
     * 
     * @param int $userid User ID
     * @throws \moodle_exception If limit exceeded
     */
    private static function check_token_limit(int $userid): void {
        global $DB;

        $limit = self::get_token_limit($userid);

        if ($limit === 0) {
            // 0 means unlimited
            return;
        }

        // Get today's usage (since midnight)
        $today_start = strtotime('today');
        $today_end = strtotime('tomorrow') - 1;

        $sql = "SELECT SUM(input_tokens + output_tokens) as total_tokens
                FROM {local_ai_gateway_log}
                WHERE userid = ?
                AND timecreated >= ?
                AND timecreated <= ?";

        $result = $DB->get_record_sql($sql, [$userid, $today_start, $today_end]);
        $today_usage = $result->total_tokens ?? 0;

        if ($today_usage >= $limit) {
            throw new \moodle_exception(
                'Daily token limit exceeded',
                'local_ai_gateway',
                '',
                null,
                "You have used {$today_usage} tokens today. Your daily limit is {$limit}."
            );
        }
    }

    /**
     * Get daily token limit for a user based on their role
     * 
     * @param int $userid User ID
     * @return int Token limit (0 = unlimited)
     */
    private static function get_token_limit(int $userid): int {
        global $DB;

        // Admins have unlimited tokens
        if (is_siteadmin($userid)) {
            return 0;
        }

        // Check for manager role (archetype 'manager')
        if (user_has_role_assignment($userid, 1)) { // Manager role ID is typically 1
            $limit = get_config('local_ai_gateway', 'manager_token_limit');
            return (int)$limit;
        }

        // Check for teacher role in any course
        if ($DB->record_exists_sql(
            "SELECT 1 FROM {role_assignments} ra
             JOIN {role} r ON ra.roleid = r.id
             WHERE ra.userid = ? AND (r.shortname = 'teacher' OR r.shortname = 'editingteacher')",
            [$userid]
        )) {
            $limit = get_config('local_ai_gateway', 'teacher_token_limit');
            return (int)$limit;
        }

        // Check for student role
        if ($DB->record_exists_sql(
            "SELECT 1 FROM {role_assignments} ra
             JOIN {role} r ON ra.roleid = r.id
             WHERE ra.userid = ? AND r.shortname = 'student'",
            [$userid]
        )) {
            $limit = get_config('local_ai_gateway', 'student_token_limit');
            return (int)$limit;
        }
        // Default limit
        $limit = get_config('local_ai_gateway', 'default_token_limit');
        return (int)$limit;
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

    /**
     * Get public token limit for a user based on their role
     * 
     * @param int $userid User ID
     * @return int Token limit (0 = unlimited)
     */
    public static function get_token_limit_for_user(?int $userid = null): int {
        if ($userid === null) {
            global $USER;
            $userid = $USER->id;
        }
        return self::get_token_limit($userid);
    }

    /**
     * Get today's token usage for a user
     * 
     * @param int $userid User ID
     * @return int Total tokens used today
     */
    public static function get_today_tokens_used(?int $userid = null): int {
        global $DB;
        
        if ($userid === null) {
            global $USER;
            $userid = $USER->id;
        }

        // Get today's usage (since midnight)
        $today_start = strtotime('today');
        $today_end = strtotime('tomorrow') - 1;

        $sql = "SELECT SUM(input_tokens + output_tokens) as total_tokens
                FROM {local_ai_gateway_log}
                WHERE userid = ?
                AND timecreated >= ?
                AND timecreated <= ?";

        $result = $DB->get_record_sql($sql, [$userid, $today_start, $today_end]);
        return (int)($result->total_tokens ?? 0);
    }

    /**
     * Get remaining tokens for a user
     * 
     * @param int $userid User ID
     * @return array['remaining' => int tokens remaining, 'limit' => int total limit, 'used' => int tokens used today]
     */
    public static function get_remaining_tokens(?int $userid = null): array {
        if ($userid === null) {
            global $USER;
            $userid = $USER->id;
        }

        $limit = self::get_token_limit_for_user($userid);
        $used = self::get_today_tokens_used($userid);
        
        if ($limit === 0) {
            // Unlimited
            return [
                'remaining' => -1,
                'limit' => 0,
                'used' => $used,
                'unlimited' => true
            ];
        }

        $remaining = max(0, $limit - $used);
        
        return [
            'remaining' => $remaining,
            'limit' => $limit,
            'used' => $used,
            'unlimited' => false
        ];
    }

    /**
     * Get user's role name/group
     * 
     * @param int $userid User ID
     * @return string Role name
     */
    public static function get_user_role_name(?int $userid = null): string {
        global $DB;
        
        if ($userid === null) {
            global $USER;
            $userid = $USER->id;
        }

        // Check for site admin first (highest priority)
        if (is_siteadmin($userid)) {
            return 'Administrator';
        }

        // Check for manager role
        if (user_has_role_assignment($userid, 1)) {
            return 'Manager';
        }

        // Check for teacher role
        if ($DB->record_exists_sql(
            "SELECT 1 FROM {role_assignments} ra
             JOIN {role} r ON ra.roleid = r.id
             WHERE ra.userid = ? AND (r.shortname = 'teacher' OR r.shortname = 'editingteacher')",
            [$userid]
        )) {
            return 'Teacher';
        }

        // Check for student role
        if ($DB->record_exists_sql(
            "SELECT 1 FROM {role_assignments} ra
             JOIN {role} r ON ra.roleid = r.id
             WHERE ra.userid = ? AND r.shortname = 'student'",
            [$userid]
        )) {
            return 'Student';
        }

        return 'User';
    }

    /**
     * Get role-specific prompt template for text generation
     * 
     * @param int $userid User ID
     * @return string Prompt template
     */
    public static function get_role_prompt(?int $userid = null): string {
        global $DB;
        
        if ($userid === null) {
            global $USER;
            $userid = $USER->id;
        }

        // Check for site admin first (highest priority)
        if (is_siteadmin($userid)) {
            $prompt = get_config('local_ai_gateway', 'admin_prompt');
            if (!empty($prompt)) {
                return $prompt;
            }
        }

        // Check for manager role
        if (user_has_role_assignment($userid, 1)) {
            $prompt = get_config('local_ai_gateway', 'manager_prompt');
            if (!empty($prompt)) {
                return $prompt;
            }
        }

        // Check for teacher role
        if ($DB->record_exists_sql(
            "SELECT 1 FROM {role_assignments} ra
             JOIN {role} r ON ra.roleid = r.id
             WHERE ra.userid = ? AND (r.shortname = 'teacher' OR r.shortname = 'editingteacher')",
            [$userid]
        )) {
            $prompt = get_config('local_ai_gateway', 'teacher_prompt');
            if (!empty($prompt)) {
                return $prompt;
            }
        }

        // Check for student role
        if ($DB->record_exists_sql(
            "SELECT 1 FROM {role_assignments} ra
             JOIN {role} r ON ra.roleid = r.id
             WHERE ra.userid = ? AND r.shortname = 'student'",
            [$userid]
        )) {
            $prompt = get_config('local_ai_gateway', 'student_prompt');
            if (!empty($prompt)) {
                return $prompt;
            }
        }

        // Default prompt
        $prompt = get_config('local_ai_gateway', 'default_prompt');
        return $prompt ?: 'Please provide a helpful response to: {prompt}';
    }

    /**
     * Get role-specific prompt template for summarization
     * 
     * @param int $userid User ID
     * @return string Summarization prompt template
     */
    public static function get_role_summarize_prompt(?int $userid = null): string {
        global $DB;
        
        if ($userid === null) {
            global $USER;
            $userid = $USER->id;
        }

        // Check for site admin first (highest priority)
        if (is_siteadmin($userid)) {
            $prompt = get_config('local_ai_gateway', 'admin_summarize_prompt');
            if (!empty($prompt)) {
                return $prompt;
            }
        }

        // Check for manager role
        if (user_has_role_assignment($userid, 1)) {
            $prompt = get_config('local_ai_gateway', 'manager_summarize_prompt');
            if (!empty($prompt)) {
                return $prompt;
            }
        }

        // Check for teacher role
        if ($DB->record_exists_sql(
            "SELECT 1 FROM {role_assignments} ra
             JOIN {role} r ON ra.roleid = r.id
             WHERE ra.userid = ? AND (r.shortname = 'teacher' OR r.shortname = 'editingteacher')",
            [$userid]
        )) {
            $prompt = get_config('local_ai_gateway', 'teacher_summarize_prompt');
            if (!empty($prompt)) {
                return $prompt;
            }
        }

        // Check for student role
        if ($DB->record_exists_sql(
            "SELECT 1 FROM {role_assignments} ra
             JOIN {role} r ON ra.roleid = r.id
             WHERE ra.userid = ? AND r.shortname = 'student'",
            [$userid]
        )) {
            $prompt = get_config('local_ai_gateway', 'student_summarize_prompt');
            if (!empty($prompt)) {
                return $prompt;
            }
        }

        // Default summarization prompt
        $prompt = get_config('local_ai_gateway', 'default_summarize_prompt');
        return $prompt ?: 'Please provide a concise summary of the following text: {text}';
    }
}