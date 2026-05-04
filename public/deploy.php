<?php

$secret = 'deployGitmtq2026';

if (!isset($_GET['token']) || $_GET['token'] !== $secret) {
    http_response_code(403);
    exit('Forbidden');
}

$output = [];
exec('cd /www/wwwroot/mtq.kemenagtanahdatar.id && git pull origin main 2>&1', $output);

echo "<pre>";
print_r($output);
echo "</pre>";
