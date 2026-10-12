<?php

declare(strict_types=1);

namespace Splitice\Mystmd\Tests\Support;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Yaml\Yaml;

/** Verify mapping identities against the same providers PHPUnit actually executes. */
final class CoverageMappings
{
    /** @var array<string, array<string, mixed>> */
    private array $files = [];
    /** @var array<string, array<string|int, mixed>> */
    private array $providers = [];

    /** @param array<string, mixed> $original */
    public function verify(array $original, string $mapping): void
    {
        $parts = explode(' / ', $mapping);
        [$shortClass, $method] = explode('::', $parts[0]);
        $class = 'Splitice\\Mystmd\\Tests\\' . $shortClass;
        if (!method_exists($class, $method)) {
            throw new \RuntimeException('Missing method: ' . $mapping);
        }
        $attributes = (new \ReflectionMethod($class, $method))->getAttributes(DataProvider::class);
        if (count($parts) === 1) {
            if ($attributes !== []) {
                throw new \RuntimeException('Missing dataset identity: ' . $mapping);
            }
            return;
        }
        if (!isset($this->providers[$parts[0]])) {
            $rows = [];
            foreach ($attributes as $attribute) {
                $provider = $attribute->newInstance()->methodName();
                foreach ($class::$provider() as $key => $row) {
                    if (array_key_exists($key, $rows)) {
                        throw new \RuntimeException('Duplicate provider identity: ' . $key);
                    }
                    $rows[$key] = $row;
                }
            }
            $this->providers[$parts[0]] = $rows;
        }
        $data = null;
        if (str_starts_with($parts[1], 'reference-')) {
            $data = $this->read($parts[1])['cases'][(int)($parts[2] ?? -1)] ?? null;
            if ($data === null || $data['file'] !== $original['file'] || $data['title'] !== $original['title']) {
                throw new \RuntimeException('Stale reference identity: ' . $mapping);
            }
            $key = $parts[2] . ': ' . $data['file'] . ': ' . $data['title'] . ($shortClass === 'UpstreamBehaviorTest' ? ': ' . $data['name'] : '');
            $row = [$data];
        } elseif (str_starts_with($parts[1], 'example ')) {
            $example = (int)substr($parts[1], 8);
            $data = $original['parameters'][1] ?? null;
            if ($data === null || $data['example'] !== $example) {
                throw new \RuntimeException('Stale CommonMark identity: ' . $mapping);
            }
            $key = $example . ': ' . $data['section'];
            $row = [$data];
        } else {
            [$file, $index] = explode(':', $parts[1]);
            $data = $this->read('upstream/' . $file)['cases'][(int)$index] ?? null;
            if ($data === null || $data !== ($original['parameters'][1] ?? null)) {
                throw new \RuntimeException('Stale YAML contents: ' . $mapping);
            }
            if ($shortClass === 'UpstreamTransformTest') {
                $relative = substr($file, strlen('packages/'));
                $key = $relative . ':' . $index . ':' . $data['title'];
                $row = [$relative, $data];
            } else {
                $key = basename($file) . ':' . $index . ':' . $data['title'];
                $row = $method === 'testUpstreamHtml' ? [$data, basename($file)] : [$data, str_contains($file, '/myst-spec/'), basename($file)];
            }
        }
        if (!array_key_exists($key, $this->providers[$parts[0]]) || $this->providers[$parts[0]][$key] !== $row) {
            throw new \RuntimeException('Missing or changed executable dataset: ' . $mapping);
        }
    }

    /** @return array<string, mixed> */
    private function read(string $file): array
    {
        if (!isset($this->files[$file])) {
            $path = dirname(__DIR__) . '/Fixtures/' . $file;
            if (!is_file($path)) {
                throw new \RuntimeException('Missing fixture: ' . $file);
            }
            $this->files[$file] = str_ends_with($path, '.json') ? json_decode((string)file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : Yaml::parseFile($path);
        }
        return $this->files[$file];
    }
}
