<?php

return [
    'target_php_version' => '8.4',
    'directory_list' => ['src', 'vendor'],
    'exclude_analysis_directory_list' => ['vendor/'],
    'exclude_file_regex' => '@^vendor/(?:.*/\\.phpstan/vendor/|symfony/polyfill-[^/]+/Resources/stubs/)@',
    'plugins' => ['AlwaysReturnPlugin', 'DuplicateArrayKeyPlugin', 'PregRegexCheckerPlugin'],
];
