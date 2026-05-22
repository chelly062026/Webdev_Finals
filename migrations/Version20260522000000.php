<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create stock_log table to track product stock changes and the user who updated them.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stock_log (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, user_id INT DEFAULT NULL, username VARCHAR(180) DEFAULT NULL, role VARCHAR(100) DEFAULT NULL, action VARCHAR(50) NOT NULL, quantity_before INT DEFAULT NULL, quantity_after INT DEFAULT NULL, change_amount INT DEFAULT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'' . "(DC2Type:datetime_immutable)" . '\', INDEX IDX_F424DB2C4584665A (product_id), INDEX IDX_F424DB2C6A2CC6C (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stock_log ADD CONSTRAINT FK_F424DB2C4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE stock_log ADD CONSTRAINT FK_F424DB2C6A2CC6C FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_log DROP FOREIGN KEY FK_F424DB2C6A2CC6C');
        $this->addSql('ALTER TABLE stock_log DROP FOREIGN KEY FK_F424DB2C4584665A');
        $this->addSql('DROP TABLE stock_log');
    }
}
