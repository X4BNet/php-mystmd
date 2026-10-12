<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Extension;

use Splitice\Mystmd\Document;
use Splitice\Mystmd\Parser;
use Splitice\Mystmd\Registry;
use Splitice\Mystmd\Syntax\BlockMatch;
use Splitice\Mystmd\Transforms;

/** Opt-in compatibility for existing Markdown Extra documents. */
final class MarkdownExtraExtension implements ExtensionInterface
{
    /** Install legacy heading syntax and output conventions in one isolated profile. */
    public function register(Registry $registry): void
    {
        $registry->configure([
            'extensions' => ['smartquotes' => false, 'citations' => false, 'math' => false, 'frontmatter' => false],
            'mdast' => ['listItemParagraphs' => false, 'markdownExtra' => true, 'hoistSingleImagesOutofParagraphs' => false],
            'html' => ['legacyCodeClasses' => true, 'tableCellAlignment' => 'style'],
        ]);
        $registry->blockParser(static function (string $source, Parser $parser): ?BlockMatch {
            if (!preg_match('/^(#{1,6})([^#\s].*?)\s*#*\s*$/u', $source, $match)) {
                return null;
            }
            return new BlockMatch(['type' => 'heading', 'depth' => strlen($match[1]), 'children' => $parser->parseInline($match[2])]);
        });
        $registry->transform(static function (Document $document): void {
            Transforms::walk($document->ast, static function (array &$node): void {
                if ($node['type'] === 'image') {
                    $node['alt'] ??= '';
                }
                if ($node['type'] !== 'heading' || empty($node['children'])) {
                    return;
                }
                $last = array_key_last($node['children']);
                $tail = &$node['children'][$last];
                if ($tail['type'] !== 'text' || !preg_match('/\s*\{((?:[#.][\w:-]+\s*)+)\}\s*$/u', $tail['value'], $match)) {
                    return;
                }
                $tail['value'] = substr($tail['value'], 0, -strlen($match[0]));
                if ($tail['value'] === '') {
                    array_pop($node['children']);
                }
                foreach (preg_split('/\s+/', trim($match[1])) ?: [] as $attribute) {
                    if ($attribute[0] === '#') {
                        $node['identifier'] = substr($attribute, 1);
                        $node['label'] = substr($attribute, 1);
                    } else {
                        $node['class'] = trim(($node['class'] ?? '') . ' ' . substr($attribute, 1));
                    }
                }
            });
        });
    }
}
