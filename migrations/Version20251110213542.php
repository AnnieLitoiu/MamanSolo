<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251110213542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avatar ADD utilisateur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE avatar ADD CONSTRAINT FK_1677722FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1677722FFB88E14F ON avatar (utilisateur_id)');
        $this->addSql('ALTER TABLE situation ADD utilisateur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE situation ADD CONSTRAINT FK_EC2D9ACAFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EC2D9ACAFB88E14F ON situation (utilisateur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avatar DROP FOREIGN KEY FK_1677722FFB88E14F');
        $this->addSql('DROP INDEX UNIQ_1677722FFB88E14F ON avatar');
        $this->addSql('ALTER TABLE avatar DROP utilisateur_id');
        $this->addSql('ALTER TABLE situation DROP FOREIGN KEY FK_EC2D9ACAFB88E14F');
        $this->addSql('DROP INDEX UNIQ_EC2D9ACAFB88E14F ON situation');
        $this->addSql('ALTER TABLE situation DROP utilisateur_id');
    }
}
