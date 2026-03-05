<?php

require('../../config.php');
require_login();

// Set page context and URL
$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/ai_gateway/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_ai_gateway') . ' - Test');
$PAGE->set_heading(get_string('pluginname', 'local_ai_gateway') . ' - Test');

echo $OUTPUT->header();

// Get current user info
global $USER;
$user_role = \local_ai_gateway\manager::get_user_role_name();
$token_info = \local_ai_gateway\manager::get_remaining_tokens();

// Get role-specific prompts
$generate_prompt = \local_ai_gateway\manager::get_role_prompt();
$summarize_prompt = \local_ai_gateway\manager::get_role_summarize_prompt();

// Get role-based token limits for reference
$student_limit = (int)get_config('local_ai_gateway', 'student_token_limit');
$teacher_limit = (int)get_config('local_ai_gateway', 'teacher_token_limit');
$manager_limit = (int)get_config('local_ai_gateway', 'manager_token_limit');
$default_limit = (int)get_config('local_ai_gateway', 'default_token_limit');

// Inline styles for better presentation
?>
<style>
    .ai-gateway-container {
        max-width: 900px;
        margin: 20px auto;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    
    .user-info-panel {
        background: #f5f5f5;
        border-left: 4px solid #0066cc;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .user-info-panel h3 {
        margin-top: 0;
        color: #333;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        margin: 8px 0;
        font-size: 14px;
    }
    
    .info-label {
        font-weight: 600;
        color: #555;
    }
    
    .info-value {
        color: #333;
    }
    
    .token-limits-panel {
        background: #e3f2fd;
        border: 1px solid #90caf9;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .token-limits-panel h4 {
        margin-top: 0;
        color: #1565c0;
        margin-bottom: 10px;
    }
    
    .limit-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        font-size: 13px;
        border-bottom: 1px solid #bbdefb;
    }
    
    .limit-row:last-child {
        border-bottom: none;
    }
    
    .limit-role {
        font-weight: 500;
        color: #0d47a1;
    }
    
    .limit-value {
        color: #333;
    }
    
    .progress-container {
        margin-bottom: 15px;
    }
    
    .progress-label {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .progress-bar {
        width: 100%;
        height: 25px;
        background: #e0e0e0;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #bdbdbd;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #4caf50 0%, #81c784 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 11px;
        font-weight: bold;
        transition: width 0.3s ease;
    }
    
    .progress-fill.warning {
        background: linear-gradient(90deg, #ff9800 0%, #ffb74d 100%);
    }
    
    .progress-fill.critical {
        background: linear-gradient(90deg, #f44336 0%, #ef5350 100%);
    }
    
    .progress-fill.unlimited {
        background: linear-gradient(90deg, #2196f3 0%, #64b5f6 100%);
    }
    
    .message-box {
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .message-success {
        background: #c8e6c9;
        color: #2e7d32;
        border-left: 4px solid #4caf50;
    }
    
    .message-warning {
        background: #fff3cd;
        color: #856404;
        border-left: 4px solid #ffc107;
    }
    
    .message-error {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #f44336;
    }
    
    .test-form-container {
        background: white;
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 4px;
        margin-top: 20px;
    }
    
    .test-form-container h3 {
        margin-top: 0;
        color: #333;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #333;
    }
    
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: monospace;
        font-size: 13px;
        min-height: 80px;
        box-sizing: border-box;
    }
    
    .form-group button {
        background: #0066cc;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
    }
    
    .form-group button:hover {
        background: #0052a3;
    }
    
    .form-group button:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    
    .result-container {
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
        padding: 15px;
        border-radius: 4px;
        margin-top: 15px;
        display: none;
    }
    
    .result-container.show {
        display: block;
    }
    
    .result-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }
    
    .result-text {
        background: white;
        padding: 12px;
        border-radius: 4px;
        border-left: 3px solid #0066cc;
        font-family: monospace;
        font-size: 12px;
        white-space: pre-wrap;
        word-wrap: break-word;
        line-height: 1.5;
    }
    
    .result-stats {
        margin-top: 10px;
        font-size: 13px;
        color: #666;
    }

    .tabs {
        display: flex;
        border-bottom: 2px solid #ddd;
        margin-bottom: 20px;
    }

    .tab-button {
        padding: 12px 20px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #666;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.3s ease;
    }

    .tab-button:hover {
        color: #0066cc;
    }

    .tab-button.active {
        color: #0066cc;
        border-bottom-color: #0066cc;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }
</style>

<div class="ai-gateway-container">
    <h2>My AI Plugin Testing</h2>
    
    <!-- User Information Panel -->
    <div class="user-info-panel">
        <h3>Your Information</h3>
        <div class="info-row">
            <span class="info-label">User:</span>
            <span class="info-value"><?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Role/Group:</span>
            <span class="info-value"><?php echo htmlspecialchars($user_role); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value"><?php echo htmlspecialchars($USER->email); ?></span>
        </div>
    </div>
    
    <!-- Token Usage Panel -->
    <div class="user-info-panel">
        <h3>Token Usage - Today</h3>
        
        <?php if ($token_info['unlimited']): ?>
            <div class="progress-container">
                <div class="progress-label">
                    <span>Unlimited Tokens</span>
                    <span>Used: <?php echo number_format($token_info['used']); ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill unlimited" style="width: 100%;">UNLIMITED</div>
                </div>
            </div>
        <?php else: ?>
            <?php
                $limit = $token_info['limit'];
                $used = $token_info['used'];
                $remaining = $token_info['remaining'];
                $percentage = $limit > 0 ? ($used / $limit) * 100 : 0;
                $percentage = min(100, $percentage);
                
                $fill_class = 'progress-fill';
                if ($percentage >= 90) {
                    $fill_class .= ' critical';
                } elseif ($percentage >= 70) {
                    $fill_class .= ' warning';
                }
            ?>
            <div class="progress-container">
                <div class="progress-label">
                    <span>Daily Limit: <?php echo number_format($limit); ?></span>
                    <span>Used: <?php echo number_format($used); ?> / Remaining: <?php echo number_format($remaining); ?></span>
                </div>
                <div class="progress-bar">
                    <div class="<?php echo $fill_class; ?>" style="width: <?php echo $percentage; ?>%;">
                        <?php echo (int)$percentage; ?>%
                    </div>
                </div>
            </div>
            
            <?php if ($percentage >= 90): ?>
                <div class="message-box message-error">
                    ⚠️ You have used <?php echo (int)$percentage; ?>% of your daily token limit. Please be careful.
                </div>
            <?php elseif ($percentage >= 70): ?>
                <div class="message-box message-warning">
                    ℹ️ You have used <?php echo (int)$percentage; ?>% of your daily token limit.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Token Limits by Role Panel -->
    <div class="token-limits-panel">
        <h4>Token Limits by User Group</h4>
        <div class="limit-row">
            <span class="limit-role">Manager</span>
            <span class="limit-value"><?php echo $manager_limit === 0 ? '∞ Unlimited' : number_format($manager_limit) . ' tokens/day'; ?></span>
        </div>
        <div class="limit-row">
            <span class="limit-role">Teacher</span>
            <span class="limit-value"><?php echo $teacher_limit === 0 ? '∞ Unlimited' : number_format($teacher_limit) . ' tokens/day'; ?></span>
        </div>
        <div class="limit-row">
            <span class="limit-role">Student</span>
            <span class="limit-value"><?php echo $student_limit === 0 ? '∞ Unlimited' : number_format($student_limit) . ' tokens/day'; ?></span>
        </div>
        <div class="limit-row">
            <span class="limit-role">Other Users</span>
            <span class="limit-value"><?php echo $default_limit === 0 ? '∞ Unlimited' : number_format($default_limit) . ' tokens/day'; ?></span>
        </div>
    </div>
    
    <!-- Role-Specific Prompts Panel -->
    <div class="token-limits-panel">
        <h4>Your Role-Specific Prompts</h4>
        <div style="margin-bottom: 15px;">
            <h5 style="margin: 0 0 8px 0; color: #1565c0;">Text Generation Prompt:</h5>
            <div style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; color: #333; border-left: 3px solid #1565c0;">
                <?php echo htmlspecialchars($generate_prompt); ?>
            </div>
        </div>
        <div>
            <h5 style="margin: 0 0 8px 0; color: #1565c0;">Summarization Prompt:</h5>
            <div style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px; color: #333; border-left: 3px solid #1565c0;">
                <?php echo htmlspecialchars($summarize_prompt); ?>
            </div>
        </div>
        <div style="margin-top: 10px; font-size: 12px; color: #666;">
            <em>Note: These prompts are sent as system messages to provide context to the AI. Your input is sent as a separate user message.</em>
        </div>
    </div>
    
    <!-- Test Form -->
    <div class="test-form-container">
        <h3>Test AI Functionality</h3>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-button active" onclick="switchTab(event, 'generate-tab')">Generate Text</button>
            <button class="tab-button" onclick="switchTab(event, 'summarize-tab')">Summarize Text</button>
        </div>
        
        <!-- Generate Tab -->
        <div id="generate-tab" class="tab-content active">
            <h4 style="margin-top: 0;">Generate Text</h4>
            
            <?php if ($_POST && isset($_POST['action']) && $_POST['action'] === 'generate'): ?>
                <?php
                    try {
                        $custom_prompt = !empty($_POST['custom_generate_prompt']) ? $_POST['custom_generate_prompt'] : null;
                        $result = \local_ai_gateway\manager::generate($_POST['prompt'], $custom_prompt);
                        $token_info = \local_ai_gateway\manager::get_remaining_tokens();
                ?>
                        <div class="message-box message-success">
                            ✓ Text generated successfully!
                        </div>
                        
                        <div class="result-container show">
                            <div class="result-label">System Prompt Used:</div>
                            <div class="result-text"><?php echo htmlspecialchars($result['system_prompt']); ?></div>
                            <div class="result-label">Generated Text:</div>
                            <div class="result-text"><?php echo htmlspecialchars($result['text']); ?></div>
                            <div class="result-stats">
                                Input Tokens: <?php echo number_format($result['input_tokens']); ?> |
                                Output Tokens: <?php echo number_format($result['output_tokens']); ?> |
                                Cost: $<?php echo number_format($result['cost'], 4); ?> |
                                Remaining Today: <?php echo $token_info['unlimited'] ? '∞' : number_format($token_info['remaining']); ?>
                            </div>
                        </div>
                <?php
                    } catch (Exception $e) {
                        $error_msg = $e->getMessage();
                ?>
                        <div class="message-box message-error">
                            ✗ Error: <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                <?php
                    }
                ?>
            <?php endif; ?>
            
            <form method="post" style="margin-top: 15px;">
                <input type="hidden" name="action" value="generate">
                <div class="form-group">
                    <label for="prompt">Enter Your Prompt:</label>
                    <textarea name="prompt" id="prompt" required placeholder="Type your prompt here..."></textarea>
                </div>
                <div class="form-group">
                    <label for="custom_generate_prompt">Custom System Prompt (optional):</label>
                    <textarea name="custom_generate_prompt" id="custom_generate_prompt" placeholder="Leave empty to use your role's default system prompt. This will be sent as a system message to provide context."></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" <?php echo $token_info['remaining'] === 0 && !$token_info['unlimited'] ? 'disabled' : ''; ?>>
                        <?php echo $token_info['remaining'] === 0 && !$token_info['unlimited'] ? 'Token Limit Reached' : 'Generate'; ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Summarize Tab -->
        <div id="summarize-tab" class="tab-content">
            <h4 style="margin-top: 0;">Summarize Text</h4>
            
            <?php if ($_POST && isset($_POST['action']) && $_POST['action'] === 'summarize'): ?>
                <?php
                    try {
                        $custom_prompt = !empty($_POST['custom_summarize_prompt']) ? $_POST['custom_summarize_prompt'] : null;
                        $result = \local_ai_gateway\manager::summarize($_POST['text_to_summarize'], $custom_prompt);
                        $token_info = \local_ai_gateway\manager::get_remaining_tokens();
                ?>
                        <div class="message-box message-success">
                            ✓ Text summarized successfully!
                        </div>
                        
                        <div class="result-container show">
                            <div class="result-label">System Prompt Used:</div>
                            <div class="result-text"><?php echo htmlspecialchars($result['system_prompt']); ?></div>
                            <div class="result-label">Summary:</div>
                            <div class="result-text"><?php echo htmlspecialchars($result['text']); ?></div>
                            <div class="result-stats">
                                Input Tokens: <?php echo number_format($result['input_tokens']); ?> |
                                Output Tokens: <?php echo number_format($result['output_tokens']); ?> |
                                Cost: $<?php echo number_format($result['cost'], 4); ?> |
                                Remaining Today: <?php echo $token_info['unlimited'] ? '∞' : number_format($token_info['remaining']); ?>
                            </div>
                        </div>
                <?php
                    } catch (Exception $e) {
                        $error_msg = $e->getMessage();
                ?>
                        <div class="message-box message-error">
                            ✗ Error: <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                <?php
                    }
                ?>
            <?php endif; ?>
            
            <form method="post" style="margin-top: 15px;">
                <input type="hidden" name="action" value="summarize">
                <div class="form-group">
                    <label for="text_to_summarize">Enter Text to Summarize:</label>
                    <textarea name="text_to_summarize" id="text_to_summarize" required placeholder="Paste the text you want to summarize here..." style="min-height: 120px;"></textarea>
                </div>
                <div class="form-group">
                    <label for="custom_summarize_prompt">Custom System Prompt (optional):</label>
                    <textarea name="custom_summarize_prompt" id="custom_summarize_prompt" placeholder="Leave empty to use your role's default summarization system prompt. This will be sent as a system message to provide context."></textarea>
                </div>
                <div class="form-group">
                    <button type="submit" <?php echo $token_info['remaining'] === 0 && !$token_info['unlimited'] ? 'disabled' : ''; ?>>
                        <?php echo $token_info['remaining'] === 0 && !$token_info['unlimited'] ? 'Token Limit Reached' : 'Summarize'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function switchTab(event, tabName) {
        event.preventDefault();
        
        // Hide all tab contents
        const contents = document.getElementsByClassName('tab-content');
        for (let i = 0; i < contents.length; i++) {
            contents[i].classList.remove('active');
        }
        
        // Remove active class from all buttons
        const buttons = document.getElementsByClassName('tab-button');
        for (let i = 0; i < buttons.length; i++) {
            buttons[i].classList.remove('active');
        }
        
        // Show the current tab and mark button as active
        document.getElementById(tabName).classList.add('active');
        event.target.classList.add('active');
    }
</script>

<?php echo $OUTPUT->footer(); ?>