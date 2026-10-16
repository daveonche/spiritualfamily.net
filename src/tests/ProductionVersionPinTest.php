<?php

declare(strict_types=1);

/**
 * Story S1.6 Step 2 guard: every version reference in the production
 * definition (compose.yml plus the Dockerfiles behind its locally built
 * images) resolves to an exact pin — no floating tags — and the pins this
 * test cross-checks are recorded in docs/baseline/baseline.md so a
 * deployment can be reproduced from the repository alone.
 *
 * Complements, not duplicates: ComposeDatabasePinTest (dbm ↔ baseline) and
 * CiPinnedRuntimeTest (runtime tag ↔ recorded digest enforcement in CI).
 */
class ProductionVersionPinTest extends \PHPUnit_Framework_TestCase
{
    private function repoDir(): string
    {
        return dirname(__DIR__, 2);
    }

    private function composeFile(): string
    {
        return $this->repoDir() . '/compose.yml';
    }

    private function composeContents(): string
    {
        return $this->readFile($this->composeFile());
    }

    private function appDockerfile(): string
    {
        return $this->repoDir() . '/docker/app/Dockerfile';
    }

    private function appDockerfileContents(): string
    {
        return $this->readFile($this->appDockerfile());
    }

    private function cronDockerfile(): string
    {
        return $this->repoDir() . '/docker/cron/Dockerfile';
    }

    private function cronDockerfileContents(): string
    {
        return $this->readFile($this->cronDockerfile());
    }

    private function baselineFile(): string
    {
        return $this->repoDir() . '/docs/baseline/baseline.md';
    }

    private function baselineContents(): string
    {
        return $this->readFile($this->baselineFile());
    }

    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            $this->fail('Unable to read ' . $path);
        }

        return $contents;
    }

    /**
     * Every `image:` value in compose.yml, outer quotes stripped.
     *
     * @return string[]
     */
    private function composeImageReferences(): array
    {
        preg_match_all('/^\s*image:\s*(.+)$/m', $this->composeContents(), $matches);
        $images = [];
        foreach ($matches[1] as $raw) {
            $images[] = trim($raw, " \t\"'");
        }

        return $images;
    }

    /**
     * Resolve `${VAR:-default}` interpolation to its committed default.
     */
    private function resolveDefaults(string $imageRef): string
    {
        return (string) preg_replace('/\$\{[A-Za-z_][A-Za-z0-9_]*:-([^}]*)\}/', '$1', $imageRef);
    }

    /**
     * The tag of an image reference, ignoring a digest pin; null when the
     * reference carries no tag at all (implicit latest).
     */
    private function imageTag(string $imageRef): ?string
    {
        $nameAndTag = explode('@', $imageRef)[0];
        $separator = strrpos($nameAndTag, ':');
        if ($separator === false) {
            return null;
        }

        return substr($nameAndTag, $separator + 1);
    }

    public function testComposeImageReferencesAreExactPins(): void
    {
        $images = $this->composeImageReferences();
        $this->assertNotEmpty($images, 'compose.yml defines no image references');

        foreach ($images as $imageRef) {
            $resolved = $this->resolveDefaults($imageRef);
            $tag = $this->imageTag($resolved);
            $this->assertTrue(
                $tag !== null && $tag !== '',
                "Image reference {$imageRef} carries no tag and would float to latest"
            );
            $this->assertTrue(
                $tag !== 'latest',
                "Image reference {$imageRef} uses a floating latest tag"
            );
            $digestPinned = strpos($resolved, '@sha256:') !== false;
            $this->assertTrue(
                $digestPinned || preg_match('/^\d+$/', (string) $tag) !== 1,
                "Image reference {$imageRef} carries a major-only tag without a digest pin"
            );
        }
    }

    public function testCronBaseIsPinnedExactlyAndMatchesCronImageTagAndBaseline(): void
    {
        $this->assertSame(
            1,
            preg_match('/^FROM alpine:(\S+)\s*$/m', $this->cronDockerfileContents(), $matches),
            'docker/cron/Dockerfile must pin its alpine base with an explicit tag'
        );
        $baseTag = $matches[1];
        $this->assertSame(
            1,
            preg_match('/^\d+\.\d+\.\d+$/', $baseTag),
            "alpine base tag {$baseTag} is not an exact major.minor.patch pin"
        );

        $cronImageTag = null;
        foreach ($this->composeImageReferences() as $imageRef) {
            if (strpos($imageRef, 'sfn/elgg_cron:') === 0) {
                $cronImageTag = $this->imageTag($this->resolveDefaults($imageRef));
                break;
            }
        }
        $this->assertNotNull($cronImageTag, 'compose.yml defines no sfn/elgg_cron image');
        $this->assertSame(
            $baseTag,
            $cronImageTag,
            'The cron image tag must carry the pinned alpine base version'
        );
        $this->assertTrue(
            strpos($this->baselineContents(), 'alpine:' . $baseTag) !== false,
            "docs/baseline/baseline.md records no alpine:{$baseTag} pin for the cron service"
        );
    }

    public function testComposerStageIsDigestPinnedAndRecordedInTheBaseline(): void
    {
        $this->assertSame(
            1,
            preg_match('/^FROM composer:[0-9]+@sha256:([0-9a-f]{64})/m', $this->appDockerfileContents(), $matches),
            'docker/app/Dockerfile must digest-pin the composer stage (a major-only tag is acceptable only under a digest)'
        );
        $this->assertTrue(
            strpos($this->baselineContents(), $matches[1]) !== false,
            "docs/baseline/baseline.md records no digest {$matches[1]} for the composer base image"
        );
    }

    public function testRuntimeBaseStaysTagPinnedViaPhpVersion(): void
    {
        $this->assertSame(
            1,
            preg_match('/^FROM coldtrick\/elgg_webserver:\$\{PHP_VERSION\}\s*$/m', $this->appDockerfileContents()),
            'docker/app/Dockerfile must keep the runtime base tag-pinned via ${PHP_VERSION} with no hard-coded tag or digest (version-pair-agnostic build, Story S1.2)'
        );
    }
}
