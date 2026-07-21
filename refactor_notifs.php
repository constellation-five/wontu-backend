<?php
$files = glob("app/Notifications/*.php");

$en = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $basename = basename($file, '.php');
    
    // NOTIF_BUYER_JOINED
    $templateKey = 'NOTIF_' . strtoupper(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Notification', '', $basename)));
    
    // Find title
    if (preg_match("/'title'\s*=>\s*__\('([^']+)'\)/", $content, $matches)) {
        $title = $matches[1];
    } else {
        continue; // skip if no title
    }
    
    // Find description
    if (preg_match("/'description'\s*=>\s*__\('([^']+)'(?:,\s*(\[[^\]]+\]))?\)/", $content, $matches)) {
        $description = $matches[1];
        $paramsArrayStr = isset($matches[2]) ? $matches[2] : '[]';
    } else {
        continue;
    }
    
    $en[$templateKey] = [
        'title' => $title,
        'description' => $description
    ];
    
    // Replace in file
    $newContent = preg_replace("/'title'\s*=>\s*__\('[^']+'\),/", "'template_key' => '$templateKey',", $content);
    $newContent = preg_replace("/'description'\s*=>\s*__\('[^']+'(?:,\s*\[[^\]]+\])?\),/", "'params' => $paramsArrayStr,", $newContent);
    
    file_put_contents($file, $newContent);
}

// Generate lang/en/notifications.php
$exportEn = "<?php\n\nreturn [\n";
foreach ($en as $key => $values) {
    $exportEn .= "    '$key' => [\n";
    $exportEn .= "        'title' => '{$values['title']}',\n";
    $exportEn .= "        'description' => '{$values['description']}',\n";
    $exportEn .= "    ],\n";
}
$exportEn .= "];\n";

if (!is_dir('lang/en')) mkdir('lang/en', 0777, true);
file_put_contents('lang/en/notifications.php', $exportEn);

echo "Done.\n";
