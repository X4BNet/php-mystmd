<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use Splitice\Mystmd\Document;
use Splitice\Mystmd\LanguageTransforms;
use Splitice\Mystmd\Parser;
use Splitice\Mystmd\ReferenceState;
use Splitice\Mystmd\Transforms;

/** Structural and local-reference preparation shared by all rendering entrypoints. */
final class RenderPreparation
{
    public static function prepare(Document $document, bool $semantic = false): void
    {
        if ($semantic) {
            $parser = new Parser();
            Transforms::containerChildren($document->ast, $parser);
            LanguageTransforms::admonitionHeaders($document->ast);
            array_push($document->diagnostics, ...$parser->diagnostics());
            (new SemanticReferences())->resolve($document);
        } else {
            Transforms::propagateTargets($document->ast);
            (new ReferenceState())->resolve($document->ast);
        }
    }
}
