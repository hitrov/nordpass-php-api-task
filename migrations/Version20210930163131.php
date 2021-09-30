<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Service\EncryptionService;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210930163131 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE item ADD encrypted_data LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD password_protected_key VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE item DROP encrypted_data');
        $this->addSql('ALTER TABLE user DROP password_protected_key');
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function postUp(Schema $schema): void
    {
        /**
         * @var $service EncryptionService
         */
        $service = $this->container->get(EncryptionService::class);
        $service->encryptAllItems();
    }
}
