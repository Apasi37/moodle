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

        // Token limits per role (daily)
        $settings->add(new admin_setting_heading(
            'local_ai_gateway/token_limits_heading',
            'Daily Token Limits by Role',
            'Configure maximum tokens per day for each role. Set to 0 for unlimited.'
        ));

        $settings->add(new admin_setting_configtext(
            'local_ai_gateway/student_token_limit',
            'Student Daily Token Limit',
            'Maximum tokens per day for students',
            '10000'
        ));

        $settings->add(new admin_setting_configtext(
            'local_ai_gateway/teacher_token_limit',
            'Teacher Daily Token Limit',
            'Maximum tokens per day for teachers',
            '50000'
        ));

        $settings->add(new admin_setting_configtext(
            'local_ai_gateway/manager_token_limit',
            'Manager Daily Token Limit',
            'Maximum tokens per day for managers',
            '500000'
        ));

        $settings->add(new admin_setting_configtext(
            'local_ai_gateway/default_token_limit',
            'Default Daily Token Limit',
            'Maximum tokens per day for users with no specific role',
            '5000'
        ));

        // Role-specific prompts
        $settings->add(new admin_setting_heading(
            'local_ai_gateway/prompts_heading',
            'Role-Specific Prompts',
            'Configure default prompts for different user roles. These can be used as templates or defaults.'
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_ai_gateway/admin_prompt',
            'Administrator Prompt Template',
            'Default prompt template for administrators',
            'As an administrator, please provide a clear and professional response.'
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_ai_gateway/manager_prompt',
            'Manager Prompt Template',
            'Default prompt template for managers',
            'As a manager, please provide a strategic and comprehensive response.'
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_ai_gateway/teacher_prompt',
            'Teacher Prompt Template',
            'Default prompt template for teachers',
            'As an educator, please provide an educational and helpful response.'
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_ai_gateway/student_prompt',
            'Student Prompt Template',
            'Default prompt template for students',
            'As a student, please provide a thoughtful and learning-focused response.'
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_ai_gateway/default_prompt',
            'Default Prompt Template',
            'Default prompt template for users with no specific role',
            'Please provide a helpful response.'
        ));

        // Summarization prompts
        $settings->add(new admin_setting_heading(
            'local_ai_gateway/summarization_heading',
            'Role-Specific Summarization Prompts',
            'Configure default summarization prompts for different user roles.'
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_ai_gateway/admin_summarize_prompt',
            'Administrator Summarization Template',
            'Default summarization prompt for administrators',
            'As an administrator, provide a concise executive summary of the following text.'
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_ai_gateway/manager_summarize_prompt',
            'Manager Summarization Template',
            'Default summarization prompt for managers',
            'As a manager, provide a strategic summary highlighting key points and implications.'
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_ai_gateway/teacher_summarize_prompt',
            'Teacher Summarization Template',
            'Default summarization prompt for teachers',
            'As an educator, provide a clear and educational summary suitable for students.'
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_ai_gateway/student_summarize_prompt',
            'Student Summarization Template',
            'Default summarization prompt for students',
            'As a student, provide a simple and easy-to-understand summary.'
        ));

        $settings->add(new admin_setting_configtextarea(
            'local_ai_gateway/default_summarize_prompt',
            'Default Summarization Template',
            'Default summarization prompt for users with no specific role',
            'Please provide a concise summary of the following text.'
        ));
    }

    $ADMIN->add('localplugins', $settings);
}