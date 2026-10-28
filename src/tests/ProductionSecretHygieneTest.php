<?php

/**
 * Story S1.6 Step 3 guard: the repository carries only documented
 * environment templates with no real secret values — every credential is
 * supplied exclusively via the deployment platform's environment settings,
 * and `.env` stays git-ignored.
 */
class ProductionSecretHygieneTest extends \PHPUnit_Framework_TestCase
{
    private function repoDir(): string
    {
        return dirname(__DIR__, 2);
    }

    private function envTemplateFile(): string
    {
        return $this->repoDir() . '/.env.example';
    }

    private function gitignoreFile(): string
    {
        return $this->repoDir() . '/.gitignore';
    }

    /**
     * Non-comment patterns from .gitignore, in file order.
     *
     * @return string[]
     */
    private function gitignorePatterns(): array
    {
        $lines = file($this->gitignoreFile(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $patterns = [];
        if ($lines === false) {
            return $patterns;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            $patterns[] = $line;
        }
        return $patterns;
    }

    /**
     * Variable => value pairs from .env.example, comments stripped.
     *
     * @return array<string, string>
     */
    private function templatePairs(): array
    {
        $lines = file($this->envTemplateFile(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $pairs = [];
        if ($lines === false) {
            return $pairs;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            $separator = strpos($line, '=');
            if ($separator === false) {
                continue;
            }
            $pairs[trim(substr($line, 0, $separator))] = trim(substr($line, $separator + 1));
        }
        return $pairs;
    }

    /**
     * Credential-material variables (Story S1.6 Step 3 inventory plus the
     * Step 5 relay credentials): supplied exclusively via the platform's
     * environment settings, never committed with real values.
     *
     * @return string[]
     */
    private function secretVariables(): array
    {
        return [
            'MYSQL_ROOT_PASSWORD',
            'MYSQL_DATABASE',
            'MYSQL_USER',
            'MYSQL_PASSWORD',
            'ELGG_DB_PREFIX',
            'APP_CRON_MINUTE',
            'APP_CRON_FIVEMINUTE',
            'APP_CRON_FIFTEENMIN',
            'APP_CRON_HALFHOUR',
            'APP_CRON_HOURLY',
            'APP_CRON_DAILY',
            'APP_CRON_WEEKLY',
            'APP_CRON_MONTHLY',
            'APP_CRON_YEARLY',
            'MAIL_SMTP_USER',
            'MAIL_SMTP_PASSWORD',
        ];
    }

    /**
     * Variables whose committed template values are documented non-secret
     * defaults; every other template value must keep its `changeme`
     * placeholder.
     *
     * @return string[]
     */
    private function nonSecretDefaultVariables(): array
    {
        return [
            'PHP_VERSION',
            'COMPOSE_PROJECT_NAME',
            'APP_HOST_PORT',
            'PMA_HOST_PORT',
            'WWW_DATA',
            'WWW_CACHE',
            'MYSQL_HOST',
            'ELGG_TIMEZONE',
            'PMA_HOST',
            'MAIL_SMTP_HOST',
            'MAIL_SMTP_PORT',
            'MAIL_SMTP_AUTH',
            // Elgg log verbosity: WARNING surfaces errors + deprecation
            // notices (Story S1.8 Step 1)
            'ELGG_DEBUG_LEVEL',
            // Empty = SAPI default log destination (container stdout); set
            // to a fs-dataroot path for file logging (Story S1.8 Step 1)
            'ELGG_LOG_FILE',
        ];
    }

    public function testEnvFileIsGitIgnoredAndNeverReincluded()
    {
        $this->assertFileExists($this->gitignoreFile());
        $ignored = false;
        foreach ($this->gitignorePatterns() as $pattern) {
            $negated = strpos($pattern, '!') === 0;
            $body = ltrim($negated ? substr($pattern, 1) : $pattern, '/');
            if (!fnmatch($body, '.env')) {
                continue;
            }
            $this->assertFalse(
                $negated,
                ".gitignore re-includes .env via '{$pattern}' - real secret values could then be committed"
            );
            $ignored = true;
        }
        $this->assertTrue(
            $ignored,
            '.env must be matched by a .gitignore pattern so real secret values can never be committed'
        );
    }

    public function testEverySecretVariableIsDocumentedWithPlaceholder()
    {
        $this->assertFileExists($this->envTemplateFile());
        $pairs = $this->templatePairs();
        $this->assertNotEmpty($pairs, '.env.example must document the environment variables');
        foreach ($this->secretVariables() as $variable) {
            $this->assertArrayHasKey(
                $variable,
                $pairs,
                "{$variable} must be documented in .env.example so the platform settings can be walked against it"
            );
            $this->assertTrue(
                strpos($pairs[$variable], 'changeme') === 0,
                "{$variable} must keep its changeme placeholder in the committed template (found '{$pairs[$variable]}')"
            );
        }
    }

    public function testTemplateCarriesNoRealSecretValues()
    {
        $this->assertFileExists($this->envTemplateFile());
        $pairs = $this->templatePairs();
        $this->assertNotEmpty($pairs, '.env.example must document the environment variables');
        foreach ($pairs as $variable => $value) {
            if (in_array($variable, $this->nonSecretDefaultVariables(), true)) {
                continue;
            }
            $this->assertTrue(
                strpos($value, 'changeme') === 0,
                "{$variable} carries a non-placeholder value in .env.example - only documented "
                . 'non-secret defaults may deviate from the changeme placeholder'
            );
        }
    }
}
