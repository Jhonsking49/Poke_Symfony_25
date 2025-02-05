<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250205120514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fights (id INT AUTO_INCREMENT NOT NULL, pokeuser_id INT DEFAULT NULL, pokenemy_id INT DEFAULT NULL, result INT NOT NULL, INDEX IDX_9927918EC97601AF (pokeuser_id), INDEX IDX_9927918E9E51E4DD (pokenemy_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pokemons (id INT AUTO_INCREMENT NOT NULL, pokeplantilla_id INT DEFAULT NULL, user_id INT DEFAULT NULL, level INT NOT NULL, strength INT NOT NULL, img VARCHAR(255) NOT NULL, INDEX IDX_3FD8B03D2E085CBD (pokeplantilla_id), INDEX IDX_3FD8B03DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pokeplantilla (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE fights ADD CONSTRAINT FK_9927918EC97601AF FOREIGN KEY (pokeuser_id) REFERENCES pokemons (id)');
        $this->addSql('ALTER TABLE fights ADD CONSTRAINT FK_9927918E9E51E4DD FOREIGN KEY (pokenemy_id) REFERENCES pokemons (id)');
        $this->addSql('ALTER TABLE pokemons ADD CONSTRAINT FK_3FD8B03D2E085CBD FOREIGN KEY (pokeplantilla_id) REFERENCES pokeplantilla (id)');
        $this->addSql('ALTER TABLE pokemons ADD CONSTRAINT FK_3FD8B03DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE fights DROP FOREIGN KEY FK_9927918EC97601AF');
        $this->addSql('ALTER TABLE fights DROP FOREIGN KEY FK_9927918E9E51E4DD');
        $this->addSql('ALTER TABLE pokemons DROP FOREIGN KEY FK_3FD8B03D2E085CBD');
        $this->addSql('ALTER TABLE pokemons DROP FOREIGN KEY FK_3FD8B03DA76ED395');
        $this->addSql('DROP TABLE fights');
        $this->addSql('DROP TABLE pokemons');
        $this->addSql('DROP TABLE pokeplantilla');
        $this->addSql('DROP TABLE user');
    }
}
