<?php

namespace App\Tests\Unit;

use App\Entity\Item;
use App\Entity\User;
use App\Service\ItemService;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ItemServiceTest extends TestCase
{
    /**
     * @var EntityManagerInterface|MockObject
     */
    private $entityManager;

    /**
     * @var ItemService
     */
    private $itemService;

    public function setUp(): void
    {
        /** @var EntityManagerInterface */
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->itemService = new ItemService($this->entityManager);
    }

    public function testCreate(): void
    {
        /** @var User */
        $user = $this->createMock(User::class);
        $data = 'secret data';

        $expectedObject = new Item();
        $expectedObject->setUser($user);

        $this->entityManager->expects($this->once())->method('persist')->with($this->any());
        $this->entityManager->expects($this->once())->method('flush');

        $this->itemService->create($user, $data);
    }

    public function testUpdate(): void
    {
        /** @var User */
        $user = $this->createMock(User::class);
        $expectedObject = $this->createMock(Item::class);
        $expectedObject->setUser($user);
        $data = 'secret data';
        $expectedObject->setData($data);

        $this->entityManager->expects($this->once())->method('persist')->with($expectedObject);
        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('refresh')->with($expectedObject);

        $this->itemService->update($expectedObject, $data);
    }
}
