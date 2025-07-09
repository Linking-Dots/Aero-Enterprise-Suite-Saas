<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

try {
    echo "Testing Job model...\n";
    $job = new \App\Models\HRM\Job();
    echo "✓ Job model loaded successfully\n";

    echo "Testing JobApplication model...\n";
    $application = new \App\Models\HRM\JobApplication();
    echo "✓ JobApplication model loaded successfully\n";

    echo "Testing JobInterview model...\n";
    $interview = new \App\Models\HRM\JobInterview();
    echo "✓ JobInterview model loaded successfully\n";

    echo "\nAll recruitment models are working correctly!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
