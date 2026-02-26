<?php

require('../../config.php');
require_login();

echo $OUTPUT->header();

var_dump(class_exists('\local_ai_gateway\manager'));

if ($_POST) {
    $result = \local_ai_gateway\manager::generate($_POST['prompt']);
    echo "<pre>".$result['text']."</pre>";
}

echo '<form method="post">
<textarea name="prompt"></textarea>
<button type="submit">Generate</button>
</form>';

echo $OUTPUT->footer();