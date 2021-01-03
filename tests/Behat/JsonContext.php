<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

final class JsonContext implements Context
{
    private const DATETIME_PATTERN = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2]\d|3[0-1])T[0-2]\d:[0-5]\d:[0-5]\d[+-][0-2]\d:[0-5]\d$/';
    private \Behatch\Context\JsonContext $jsonContext;

    /** @BeforeScenario */
    public function gatherContexts(BeforeScenarioScope $scope): void
    {
        $environment = $scope->getEnvironment();

        $this->jsonContext = $environment->getContext('Behatch\Context\JsonContext');
    }

    /**
     * Checks, that given JSON node is a Datetime
     *
     * @Then the JSON node :node should be a datetime
     */
    public function theJsonNodeShouldBeDateTime(string $node): void
    {
        $this->jsonContext->theJsonNodeShouldMatch($node, self::DATETIME_PATTERN);
    }
}
