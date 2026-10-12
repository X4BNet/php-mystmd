<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Component;

/** Whether a trusted component belongs inside or outside an HTML paragraph. */
enum OutputKind: string
{
    case Inline = 'inline';
    case Block = 'block';
}
