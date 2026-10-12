<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Extension;

use Splitice\Mystmd\Registry;

interface ExtensionInterface
{
    /** Register syntax, transformations and renderers before parsing starts. */
    public function register(Registry $registry): void;
}
