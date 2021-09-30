<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Item;
use App\Repository\ItemRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210930214756 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function getDescription() : string
    {
        return 'This migration will erase plain text user items data if it was already encrypted.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE item CHANGE data data LONGTEXT DEFAULT NULL, CHANGE encrypted_data encrypted_data LONGTEXT NOT NULL');
        $this->addSql('UPDATE item SET data = NULL WHERE encrypted_data IS NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }

    public function preUp(Schema $schema): void
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        /**
         * @var $itemRepo ItemRepository
         */
        $itemRepo = $em->getRepository(Item::class);
        $items = $itemRepo->getNotEncryptedItems();
        $this->abortIf(count($items) !== 0, 'Database still has not encrypted data. Consider executing EncryptionService::encryptAllItems()');
    }
}
