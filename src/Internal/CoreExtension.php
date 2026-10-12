<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Delimiter\Processor\EmphasisDelimiterProcessor;
use League\CommonMark\Extension\CommonMark\Parser\Block;
use League\CommonMark\Extension\CommonMark\Parser\Inline;
use League\CommonMark\Extension\ConfigurableExtensionInterface;
use League\CommonMark\Parser\Inline\NewlineParser;
use League\Config\ConfigurationBuilderInterface;

/** Use CommonMark's grammars with MyST's configurable HTML tokenization. */
final readonly class CoreExtension implements ConfigurableExtensionInterface
{
    /** Bind the raw-HTML parsing switch independently of rendering policy. */
    public function __construct(private bool $html, private bool $markdownExtra = false)
    {
    }

    /** Preserve CommonMark's parser configuration contract. */
    public function configureSchema(ConfigurationBuilderInterface $builder): void
    {
        (new CommonMarkCoreExtension())->configureSchema($builder);
    }

    /** Install the core block, delimiter and inline grammars. */
    public function register(EnvironmentBuilderInterface $environment): void
    {
        foreach ([70 => Block\BlockQuoteStartParser::class, 60 => Block\HeadingStartParser::class, 50 => Block\FencedCodeStartParser::class, 20 => Block\ThematicBreakStartParser::class, 10 => Block\ListBlockStartParser::class, -100 => Block\IndentedCodeStartParser::class] as $priority => $class) {
            $environment->addBlockStartParser($this->markdownExtra && $priority === 10 ? new ExtraListStartParser() : new $class(), $priority);
        }
        foreach ([200 => NewlineParser::class, 150 => Inline\BacktickParser::class, 80 => Inline\EscapableParser::class, 70 => Inline\EntityParser::class, 50 => Inline\AutolinkParser::class, 30 => Inline\CloseBracketParser::class, 20 => Inline\OpenBracketParser::class, 10 => Inline\BangParser::class] as $priority => $class) {
            $environment->addInlineParser(new $class(), $priority);
        }
        if ($this->html) {
            $environment->addBlockStartParser($this->markdownExtra ? new ExtraHtmlStartParser() : new Block\HtmlBlockStartParser(), 40);
            $environment->addInlineParser(new Inline\HtmlInlineParser(), 40);
        }
        $environment->addDelimiterProcessor(new EmphasisDelimiterProcessor('*'));
        $environment->addDelimiterProcessor(new EmphasisDelimiterProcessor('_'));
    }
}
