<?php

$files = glob("app/Notifications/*.php");

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // We want to replace 'title' => __('Something'), 'description' => __('Something Else'),
    // with 'template_key' => 'NOTIF_...', 'params' => [...]
    
    // First, let's extract the title and description to build the lang file later!
    // Actually, I'll just do manual preg_replace for each since there are only 18.
}
