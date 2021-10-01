<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Item;
use App\Entity\User;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Doctrine\ORM\EntityManagerInterface;

class ItemService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EncryptionService
     */
    private $encryptionService;

    public function __construct(EntityManagerInterface $entityManager, EncryptionService $encryptionService)
    {
        $this->entityManager = $entityManager;
        $this->encryptionService = $encryptionService;
    }

    public function create(User $user, string $data): void
    {
        $item = new Item();
        $item->setUser($user);
        $encryptedData = $this->encryptionService->getEncryptedData($user, $data);
        $item->setEncryptedData($encryptedData);

        $this->entityManager->persist($item);
        $this->entityManager->flush();
    }

    public function update(Item $item, string $data): array
    {
        $encryptedData = $this->encryptionService->getEncryptedData($item->getUser(), $data);
        $item->setEncryptedData($encryptedData);

        $this->entityManager->persist($item);
        $this->entityManager->flush();
        $this->entityManager->refresh($item);

        return $this->convertToResponse($item);
    }

    public function convertToResponse(Item $item): array
    {
        $response = [];

        $response['id'] = $item->getId();
        $response['data'] = 'Sorry, unable to decrypt.';
        try {
            if ($item->getEncryptedData()) {
                $response['data'] = $this->encryptionService->getDecryptedData($item->getUser(), $item->getEncryptedData());
            }
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            // nothing to do
        }

        $response['created_at'] = $item->getCreatedAt();
        $response['updated_at'] = $item->getUpdatedAt();

        return $response;
    }
}
