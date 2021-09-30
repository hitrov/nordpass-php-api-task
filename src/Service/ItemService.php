<?php

namespace App\Service;

use App\Entity\Item;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ItemService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(User $user, string $data): void
    {
        $item = new Item();
        $item->setUser($user);
        $item->setData($data);

        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    public function update(Item $item, string $data): array
    {
        $item->setData($data);

        $this->entityManager->persist($item);
        $this->entityManager->flush();
        $this->entityManager->refresh($item);

        return $this->convertToResponse($item);
    }

    public function convertToResponse(Item $item): array
    {
        $response = [];

        $response['id'] = $item->getId();
        $response['data'] = $item->getData();
        $response['created_at'] = $item->getCreatedAt();
        $response['updated_at'] = $item->getUpdatedAt();

        return $response;
    }
} 