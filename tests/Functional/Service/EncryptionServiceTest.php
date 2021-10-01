<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service;

use App\Entity\Item;
use App\Entity\User;
use App\Repository\ItemRepository;
use App\Repository\UserRepository;
use App\Service\EncryptionService;
use App\Service\ItemService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EncryptionServiceTest extends KernelTestCase
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var EncryptionService
     */
    private $encryptionService;

    /**
     * @var ItemService
     */
    private $itemService;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function setUp(): void
    {
        self::bootKernel();

        // returns the real and unchanged service container
        $container = self::$kernel->getContainer();

        // gets the special container that allows fetching private services
        $container = self::$container;
        $this->encryptionService = $container->get(EncryptionService::class);
        $this->itemService = $container->get(ItemService::class);
        $this->em = $container->get(EntityManagerInterface::class);
        $userRepository = static::$container->get(UserRepository::class);
        $this->user = $userRepository->findOneByUsername('john');
    }

    /**
     * @covers EncryptionService::getEncryptedData
     * @covers EncryptionService::getDecryptedData
     *
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function testEncryption(): void
    {
        $time = time();
        $data = "very secure item data $time";

        $encryptedData = $this->encryptionService->getEncryptedData($this->user, $data);
        $decryptedData = $this->encryptionService->getDecryptedData($this->user, $encryptedData);

        $this->assertEquals($data, $decryptedData);
    }

    /**
     * @covers EncryptionService::encryptAllItems
     * @covers \App\Service\ItemService::convertToResponse
     */
    public function testEncryptAllItems(): void
    {
        $this->user->setPasswordProtectedKey(null);
        $this->em->persist($this->user);
        $this->em->flush();
        $this->em->refresh($this->user);

        $this->em->createQueryBuilder()
            ->delete(Item::class)
            ->where('1 = 1')
            ->getQuery()
            ->execute();

        $itemsData = [
            'zero',
            'one',
            'two',
        ];
        foreach ($itemsData as $data) {
            $item = new Item();
            $item->setData($data);
            $item->setUser($this->user);
            $this->assertNull($item->getEncryptedData());
            $this->em->persist($item);
        }
        $this->em->flush();

        /**
         * @var $itemsRepo ItemRepository
         */
        $itemsRepo = $this->em->getRepository(Item::class);
        $items = $itemsRepo->findAllUserItems($this->user);
        $filteredItems = array_filter($items, function (Item $item) use ($itemsData) {
            $this->assertNull($item->getEncryptedData());

            return $item->getData() === $itemsData[0]
                || $item->getData() === $itemsData[1]
                || $item->getData() === $itemsData[2];
        });
        $this->assertCount(3, $filteredItems);

        $this->encryptionService->encryptAllItems();
        $filteredItems = array_filter($items, function (Item $item) use ($itemsData) {
            $this->em->refresh($item);
            $this->assertNotNull($item->getEncryptedData());
            $response = $this->itemService->convertToResponse($item);

            return $response['data'] === $itemsData[0]
                || $response['data'] === $itemsData[1]
                || $response['data'] === $itemsData[2];
        });
        $this->assertCount(3, $filteredItems);
    }
}
