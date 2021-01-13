<?php

namespace App\Tests;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class WebTestCaseWithDatabase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManager $em;

    private ?string $jwt = null;

    protected function setUp(): void
    {
        parent::setUp();

        /**
         * You need to boot the kernel to create the DB. Because the kernel can only
         * be booted once and static::createClient() tries booting it again, the client
         * is created here.
         */
        $this->client = static::createClient();

        if ('test' !== self::$kernel->getEnvironment()) {
            throw new \LogicException('Tests cases with fresh database must be executed in the test environment');
        }

        $this->em = self::$kernel->getContainer()->get('doctrine')->getManager();

        $metaData = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->em);
        $schemaTool->updateSchema($metaData);

        $this->login();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $purger = new ORMPurger($this->em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $purger->purge();

        // reset SQLite auto increments (not handled by purger)
        $this->em->getConnection()->executeStatement('DELETE FROM sqlite_sequence');
    }

    protected function addFixture(string $className): void
    {
        $loader = new Loader();
        $loader->addFixture(new $className());

        $executor = new ORMExecutor($this->em);
        $executor->execute($loader->getFixtures(), true);
    }

    protected function request(string $method, string $uri, ?string $json = null)
    {
        return $this->client->request($method, $uri, [], [], $this->getHeaders(), $json);
    }

    protected function login(): void
    {
        if ($this->jwt !== null) {
            return;
        }

        $this->client->request(Request::METHOD_POST, '/api/login', [], [], [], null);

        $response = $this->client->getResponse();
        $responseArray = json_decode($response->getContent(), true);

        $this->jwt = $responseArray['jwt'];
    }

    protected function logout(): void
    {
        $this->jwt = null;
    }

    protected static function assertAtomDateTime(string $dateString): void
    {
        $dateTime = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $dateString);
        $formattedDateTime = $dateTime->format(\DateTimeInterface::ATOM);

        static::assertSame($dateString, $formattedDateTime);
    }

    private function getHeaders(): array
    {
        $headers = [];
        if ($this->jwt !== null) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->jwt;
        }

        return $headers;
    }
}
