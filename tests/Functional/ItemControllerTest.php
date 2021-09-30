<?php

namespace App\Tests;

use App\Entity\Item;
use App\Repository\ItemRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class ItemControllerTest extends WebTestCase
{
    public function testCreate()
    {
        $client = static::createClient();

        $userRepository = static::$container->get(UserRepository::class);
        $itemRepository = static::$container->get(ItemRepository::class);
        $entityManager = static::$container->get(EntityManagerInterface::class);

        $user = $userRepository->findOneByUsername('john');

        $client->loginUser($user);
        
        $data = 'very secure new item data';

        $newItemData = ['data' => $data];

        $client->request('POST', '/item', $newItemData);
        $client->request('GET', '/item');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('very secure new item data', $client->getResponse()->getContent());

//        $userRepository->findOneByData($data);
    }

    public function testUpdate()
    {
        $client = static::createClient();

        $userRepository = static::$container->get(UserRepository::class);
        /**
         * @var $entityManager EntityManagerInterface
         */
        $entityManager = static::$container->get(EntityManagerInterface::class);

        $user = $userRepository->findOneByUsername('john');

        $client->loginUser($user);

        $item = new Item();
        $time = time();
        $data = "very secure item data $time";
        $item->setData($data);
        $item->setUser($user);
        $entityManager->persist($item);
        $entityManager->flush();

        $newData = "new data $time";
        $newItemData = [
            'id' => $item->getId(),
            'data' => $newData,
        ];

        $client->request('PUT', '/item', $newItemData);
        $this->assertResponseIsSuccessful();
        $client->request('GET', '/item');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString($newData, $client->getResponse()->getContent());
    }
}
