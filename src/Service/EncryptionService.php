<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Item;
use App\Entity\User;
use App\Repository\ItemRepository;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EncryptionService
{
    const BATCH_SIZE = 20;

    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em)
    {
        $this->params = $params;
        $this->em = $em;
    }

    /**
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function getEncryptedData(User $user, string $data): string
    {
        $unlocked = $this->getUnlockedKey($user);

        return Crypto::encrypt($data, $unlocked);
    }

    /**
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException
     */
    public function getDecryptedData(User $user, string $encryptedData): string
    {
        $unlocked = $this->getUnlockedKey($user);

        return Crypto::decrypt($encryptedData, $unlocked);
    }

    public function encryptAllItems(): void
    {
        /**
         * @var $itemRepo ItemRepository
         */
        $itemRepo = $this->em->getRepository(Item::class);
        $items = $itemRepo->getNotEncryptedItems();
        $i = 1;
        foreach ($items as $item) {
            $user = $item->getUser();
            $encryptedData = $this->getEncryptedData($user, $item->getData());
            $item->setEncryptedData($encryptedData);

            $decryptedData = $this->getDecryptedData($user, $item->getEncryptedData());

            if ($decryptedData !== $item->getData()) {
                continue;
            }
            ++$i;
            $this->em->persist($item);
            if (($i % self::BATCH_SIZE) === 0) {
                $this->em->flush(); // Executes all updates.
            }
        }
        $this->em->flush();
    }

    private function getUnlockedKey(User $user): Key
    {
        $password = $this->generateKeyPassword($user);

        $protectedKey = KeyProtectedByPassword::loadFromAsciiSafeString($this->getPasswordProtectedKey($user));

        return $protectedKey->unlockKey($password);
    }

    private function generateKeyPassword(User $user): string
    {
        return hash('sha256', sprintf('%s.%s', $user->getPassword(), $this->params->get('encryption_secret')));
    }

    /**
     * @return string
     *
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    private function createPasswordProtectedKey(User $user): void
    {
        if ($user->getPasswordProtectedKey()) {
            throw new \InvalidArgumentException('Password-protected key already exists.');
        }

        $password = $this->generateKeyPassword($user);

        $protectedKey = KeyProtectedByPassword::createRandomPasswordProtectedKey($password);

        $user->setPasswordProtectedKey($protectedKey->saveToAsciiSafeString());
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    private function getPasswordProtectedKey(User $user): string
    {
        if (!$user->getPasswordProtectedKey()) {
            $this->createPasswordProtectedKey($user);
            $this->em->refresh($user);
        }

        return $user->getPasswordProtectedKey();
    }
}
