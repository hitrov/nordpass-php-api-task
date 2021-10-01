<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ItemControllerTest extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $userRepository = static::$container->get(UserRepository::class);
        $this->user = $userRepository->findOneByUsername('john');
    }

    /**
     * @covers \App\Controller\ItemController::create
     * @covers \App\Service\ItemService::create
     */
    public function testCreate(): void
    {
        $time = time();
        $data = "very secure item data $time";
        $this->createItemGetId($data);

        $this->client->request('GET', '/item');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($data, $this->client->getResponse()->getContent());
    }

    /**
     * @covers \App\Controller\ItemController::update
     * @covers \App\Service\ItemService::update
     */
    public function testUpdate(): void
    {
        $time = time();
        $data = "very secure item data $time";
        $id = $this->createItemGetId($data);

        $newData = "new data $time";
        $newItemData = [
            'id' => $id,
            'data' => $newData,
        ];

        $this->client->request('PUT', '/item', $newItemData);
        $this->assertResponseIsSuccessful();
        $this->client->request('GET', '/item');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($newData, $this->client->getResponse()->getContent());
    }

    /**
     * @covers \App\Controller\ItemController::delete
     */
    public function testDelete(): void
    {
        $time = time();
        $data = "very secure item data $time";
        $id = $this->createItemGetId($data);

        $this->client->request('DELETE', "/item/$id");
        $this->assertResponseIsSuccessful();
        $this->client->request('GET', '/item');

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString($data, $this->client->getResponse()->getContent());
    }

    private function createItemGetId(string $data): int
    {
        $this->client->loginUser($this->user);
        $newItemData = ['data' => $data];
        $this->client->request('POST', '/item', $newItemData);

        $this->client->request('GET', '/item');
        $response = $this->client->getResponse()->getContent();
        $item = array_filter(json_decode($response, true), function ($item) use ($data) {
            return $item['data'] === $data;
        });

        return reset($item)['id'];
    }
}
