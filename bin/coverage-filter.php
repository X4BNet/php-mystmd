<?php

declare(strict_types=1);

// Xdebug applies filters when compiling files. Install this before Composer or
// PHPUnit loads vendor code, so branch graphs cover only the library itself.
if (function_exists('xdebug_set_filter')) {
    xdebug_set_filter(XDEBUG_FILTER_CODE_COVERAGE, XDEBUG_PATH_INCLUDE, [dirname(__DIR__) . '/src/']);
}
