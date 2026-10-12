<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Internal;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\SmartPunct\QuoteParser;
use League\CommonMark\Extension\SmartPunct\QuoteProcessor;
use League\CommonMark\Extension\SmartPunct\ReplaceUnpairedQuotesListener;

/** MyST enables smart quotes without enabling MarkdownIt's replacements rule. */
final readonly class SmartQuotesExtension implements ExtensionInterface
{
    /** @param string|list<string> $quotes */
    public function __construct(private string|array $quotes)
    {
    }

    /** Reuse quote delimiter handling without changing dashes and ellipses. */
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $quotes = is_string($this->quotes) ? mb_str_split($this->quotes) : $this->quotes;
        if (count($quotes) !== 4) {
            throw new \InvalidArgumentException('Smart quote configuration requires four delimiters');
        }
        $environment->addInlineParser(new QuoteParser(), 10);
        $environment->addDelimiterProcessor(QuoteProcessor::createDoubleQuoteProcessor($quotes[0], $quotes[1]));
        $environment->addDelimiterProcessor(QuoteProcessor::createSingleQuoteProcessor($quotes[2], $quotes[3]));
        $environment->addEventListener(DocumentParsedEvent::class, new ReplaceUnpairedQuotesListener());
    }
}
