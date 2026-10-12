<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Splitice\Mystmd\Extension\FirstPartyExtension;
use Splitice\Mystmd\Internal\BuiltInDirectives;
use Splitice\Mystmd\Internal\BuiltInRoles;
use Splitice\Mystmd\Internal\DirectiveUtils;
use Splitice\Mystmd\LanguageTransforms;
use Splitice\Mystmd\Parser;
use Splitice\Mystmd\Transforms;

/** Ports the upstream helper and transformation tests, including diagnostics. */
final class UpstreamBehaviorTest extends TestCase
{
    /** @return iterable<string, array{array<string, mixed>}> */
    public static function cases(): iterable
    {
        $fixture = json_decode((string)file_get_contents(__DIR__ . '/Fixtures/reference-behavior.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($fixture['cases'] as $index => $case) {
            yield $index . ': ' . $case['file'] . ': ' . $case['title'] . ': ' . $case['name'] => [$case];
        }
    }

    /** @param array<string, mixed> $case */
    #[DataProvider('cases')]
    public function testHandwrittenBehavior(array $case): void
    {
        $parser = new Parser();
        $args = $case['input'];
        $name = $case['name'];
        if (isset($case['exception'])) {
            self::assertContains($name, ['inlineOptionsToTokens', 'toHTML']);
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage($case['exception']);
            if ($name === 'toHTML') {
                \Splitice\Mystmd\HtmlTemplate::toHtml($args[0], $args[1] ?? []);
            } else {
                \Splitice\Mystmd\InlineAttributes::parse($args[0]);
            }
            return;
        }
        if ($name === 'mystToHtml') {
            // Upstream mutates this argument; the public PHP renderer intentionally copies it.
            (new \Splitice\Mystmd\ReferenceState())->resolve($args[0]);
        }
        $result = str_contains($name, 'Transformer.') ? $this->linkTransform($name, $args, $parser) : match ($name) {
            'isRecognizedDoi' => \Splitice\Mystmd\LinkTransform::isRecognizedDoi(...$args),
            'formatLinkText' => \Splitice\Mystmd\LinkTransform::formatText($args[0]),
            'fillNumbering' => \Splitice\Mystmd\Numbering::fill(...$args),
            'initializeTargetCounts' => \Splitice\Mystmd\Numbering::initializeCounts(...$args),
            'enumerateTargetsTransform' => $this->enumerate($args, $parser),
            'toText' => \Splitice\Mystmd\AstUtils::toText($args[0] ?? null),
            'normalizeLabel' => \Splitice\Mystmd\AstUtils::normalizeLabel($args[0] ?? null),
            'slugToUrl' => \Splitice\Mystmd\AstUtils::slugToUrl($args[0] ?? null),
            'plural' => \Splitice\Mystmd\AstUtils::plural(...$args),
            'liftChildren' => \Splitice\Mystmd\AstUtils::liftChildren($args[0], $args[1]),
            'mergeTextNodes' => \Splitice\Mystmd\AstUtils::mergeTextNodes($args[0]),
            'parseIndexLine' => \Splitice\Mystmd\IndexEntries::parseLine($args[0], $args[1], $parser),
            'splitEntryValue' => \Splitice\Mystmd\IndexEntries::splitEntryValue($args[0]),
            'createIndexEntries' => \Splitice\Mystmd\IndexEntries::create($args[0], $parser),
            'getFrontmatter' => \Splitice\Mystmd\Frontmatter::extract($args[1], $parser, $args[2] ?? ['propagateTargets' => true]),
            'mystToHtml' => (new \Splitice\Mystmd\HtmlRenderer($args[1] ?? []))->render(new \Splitice\Mystmd\Document($args[0]))->html,
            'formatTag' => \Splitice\Mystmd\HtmlTemplate::formatTag(...$args),
            'toHTML' => \Splitice\Mystmd\HtmlTemplate::toHtml($args[0], $args[1] ?? []),
            'filterIncludedContent' => \Splitice\Mystmd\Resolver\IncludeFilter::apply($args[1], $args[2], $parser),
            'tokenizeInlineAttributes' => \Splitice\Mystmd\InlineAttributes::tokenize($args[0]),
            'incrementHeadingCounts' => \Splitice\Mystmd\ReferenceState::incrementHeadingCounts($args[0], $args[1]),
            'formatHeadingEnumerator' => \Splitice\Mystmd\ReferenceState::formatHeadingEnumerator($args[0]),
            'getCodeBlockOptions' => DirectiveUtils::codeOptions($args[0]['options'], $parser, $args[2] ?? null),
            'parseTags' => DirectiveUtils::parseTags($args[0], $parser),
            'parseLinesString' => DirectiveUtils::parseLinesString($args[2], $parser),
            'buttonRole.run' => FirstPartyExtension::role($args[0], $parser),
            'cardDirective.run' => FirstPartyExtension::directive($args[0] + ['options' => []], $parser),
            'splitParagraphNode' => FirstPartyExtension::splitParagraphNode($args[0]),
            'citeRole.run' => BuiltInRoles::apply(['type' => 'mystRole', 'name' => $args[0]['name'], 'value' => $args[0]['body']], $parser)['children'],
            'codeCellDirective.run' => BuiltInDirectives::apply(['type' => 'mystDirective', 'name' => $args[0]['name'], 'args' => $args[0]['arg'], 'value' => $args[0]['body'], 'options' => $args[0]['options']], $parser)['children'],
            default => LanguageTransforms::apply($name, $args, $parser),
        };
        foreach ($args as &$argument) {
            if (is_array($argument) && ($argument['__vfile'] ?? false)) {
                array_push($argument['messages'], ...array_map(static function ($diagnostic): array {
                    return ['message' => $diagnostic->message, 'fatal' => $diagnostic->severity === 'error', 'ruleId' => $diagnostic->ruleId !== '' ? $diagnostic->ruleId : null];
                }, $parser->diagnostics()));
            }
        }
        unset($argument);
        $expectedResult = $case['result'];
        if ($name === 'codeCellDirective.run') {
            // The source test does not constrain the randomly generated output ID.
            self::assertIsString($result[0]['children'][1]['id']);
            $expectedResult[0]['children'][1]['id'] = $result[0]['children'][1]['id'];
        }
        if ($name === 'indexIdentifierTransform') {
            foreach (['identifier', 'label', 'html_id'] as $key) {
                self::assertNotEmpty($args[0]['children'][1][$key]);
                $case['after'][0]['children'][1][$key] = $args[0]['children'][1][$key];
            }
        }
        self::assertEquals($case['after'], $args, 'Mutated arguments and warning assertions');
        self::assertEquals($expectedResult, $result, 'Returned value');
    }

    /** Recreate upstream state while checking its serialized mutations.
     * @param list<mixed> $args
     * @return array<string, mixed>
     */
    private function enumerate(array &$args, Parser $parser): array
    {
        $serialized = &$args[1]['state'];
        $state = new \Splitice\Mystmd\Numbering(['numbering' => $serialized['numbering']], identifiers: $serialized['identifiers']);
        $state->counts = $serialized['targetCounts'];
        $state->apply($args[0], $parser, $args[1]['hidden'] ?? false);
        $serialized['targetCounts'] = $state->counts;
        $serialized['targets'] = $state->targets;
        return $args[0];
    }

    /** Port class construction and method calls without exposing JavaScript types.
     * @param list<mixed> $args
     */
    private function linkTransform(string $name, array &$args, Parser $parser): mixed
    {
        [$class, $method] = explode('.', $name);
        $protocol = ['DOITransformer' => 'doi', 'WikiTransformer' => 'wiki', 'GithubTransformer' => 'github'][$class];
        if ($method === 'constructor') {
            return (new \Splitice\Mystmd\LinkTransform($protocol, $args[0] ?? []))->configuration();
        }
        $state = $args[0];
        $options = $protocol === 'wiki' ? array_filter(['url' => $state['wikiUrl'], 'lang' => $state['lang'] ?? null], static fn ($value): bool => $value !== null) : ($state['doiOpts'] ?? []);
        $transformer = new \Splitice\Mystmd\LinkTransform($protocol, $options);
        if ($method === 'transform') {
            $link = $args[1];
            $result = $transformer->transform($link, $parser);
            array_splice($args, 1, 1, [$link]);
            return $result;
        }
        $result = match ($method) {
            'test' => $transformer->test($args[1] ?? null),
            'pageName' => $transformer->pageName($args[1]),
            default => throw new \LogicException('Unported link method: ' . $name),
        };
        return $result;
    }
}
