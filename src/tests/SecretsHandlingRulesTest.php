<?php

/**
 * Story S1.8 Step 4 guard: the documented secrets-handling rules state the
 * injection-only rule, cover what must never be committed and where secrets
 * live in development versus production, and stay in agreement with the
 * secret-hygiene enforcement in ProductionSecretHygieneTest.
 */
class SecretsHandlingRulesTest extends \PHPUnit_Framework_TestCase
{
    private function repoDir(): string
    {
        return dirname(__DIR__, 2);
    }

    private function secretsDocFile(): string
    {
        return $this->repoDir() . '/docs/deployment/secrets-handling.md';
    }

    private function secretsDocContents(): string
    {
        $contents = file_get_contents($this->secretsDocFile());
        return $contents === false ? '' : $contents;
    }

    /**
     * The secret-variable inventory enforced by ProductionSecretHygieneTest,
     * read via reflection so this cross-check cannot drift from enforcement.
     *
     * @return string[]
     */
    private function enforcedSecretVariables(): array
    {
        if (!class_exists('ProductionSecretHygieneTest')) {
            require_once $this->repoDir() . '/src/tests/ProductionSecretHygieneTest.php';
        }
        $reflection = new ReflectionMethod('ProductionSecretHygieneTest', 'secretVariables');
        $reflection->setAccessible(true);
        $probe = new ProductionSecretHygieneTest('ProductionSecretHygieneTest');
        $inventory = $reflection->invoke($probe);
        return is_array($inventory) ? $inventory : [];
    }

    /**
     * Variable names listed in the ```text block under
     * "## Credential variables" in secrets-handling.md.
     *
     * @return string[]
     */
    private function documentedCredentialVariables(): array
    {
        $section = strstr($this->secretsDocContents(), '## Credential variables');
        if ($section === false) {
            return [];
        }
        $fenceStart = strpos($section, '```text');
        if ($fenceStart === false) {
            return [];
        }
        $bodyStart = strpos($section, "\n", $fenceStart);
        if ($bodyStart === false) {
            return [];
        }
        $fenceEnd = strpos($section, '```', $bodyStart);
        if ($fenceEnd === false) {
            return [];
        }
        $block = substr($section, $bodyStart + 1, $fenceEnd - $bodyStart - 1);
        $variables = [];
        foreach (explode("\n", $block) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $variables[] = $line;
            }
        }
        return $variables;
    }

    public function testSecretsDocExistsAndStatesInjectionOnlyRule()
    {
        $this->assertFileExists($this->secretsDocFile());
        $contents = $this->secretsDocContents();
        $this->assertTrue(
            strpos($contents, '## Injection-only rule') !== false,
            'secrets-handling.md must carry an "Injection-only rule" section'
        );
        $this->assertTrue(
            strpos($contents, 'exclusively through environment mechanisms') !== false,
            'the rules must state that secrets are injected only via environment mechanisms'
        );
        $this->assertTrue(
            strpos($contents, 'Compose') !== false,
            'the container orchestration environment mechanism (Compose) must be named'
        );
        $this->assertTrue(
            strpos($contents, 'Coolify environment settings') !== false,
            'the deployment platform environment settings (Coolify) must be named'
        );
    }

    public function testSecretsDocCoversNeverCommitAndEnvironmentLocations()
    {
        $contents = $this->secretsDocContents();
        $this->assertTrue(
            strpos($contents, '## Never commit') !== false,
            'the rules must state what must never be committed'
        );
        $this->assertTrue(
            strpos($contents, 'git-ignored') !== false,
            'the rules must state that .env stays git-ignored'
        );
        $this->assertTrue(
            strpos($contents, 'changeme') !== false,
            'the rules must reference the changeme placeholder convention'
        );
        $this->assertTrue(
            strpos($contents, '## Where secrets live') !== false,
            'the rules must state where secrets live per environment'
        );
        $section = strstr($contents, '## Where secrets live');
        if ($section === false) {
            $section = '';
        }
        $this->assertTrue(
            strpos($section, 'Development') !== false && strpos($section, 'Production') !== false,
            'the rules must cover both the development and production secret locations'
        );
        $this->assertTrue(
            strpos($section, '.env') !== false,
            'the development secret location must be the local .env file'
        );
        $this->assertTrue(
            strpos($section, 'Coolify environment settings') !== false,
            'the production secret location must be the Coolify environment settings'
        );
    }

    public function testDocumentedCredentialListMatchesEnforcedInventory()
    {
        $documented = $this->documentedCredentialVariables();
        $this->assertNotEmpty(
            $documented,
            'secrets-handling.md must list the credential variables in a ```text block'
        );
        $enforced = $this->enforcedSecretVariables();
        $documentedSorted = $documented;
        $enforcedSorted = $enforced;
        sort($documentedSorted);
        sort($enforcedSorted);
        $this->assertEquals(
            $enforcedSorted,
            $documentedSorted,
            'the documented credential list must exactly match the inventory '
            . 'ProductionSecretHygieneTest enforces'
        );
    }
}
