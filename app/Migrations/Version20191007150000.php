<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add fields used to store recipes
 */
final class Version20191007150000 extends AbstractMigration
{
    
    public function getDescription(): string
    {
        return 'Add fields used to store recipes';
    }
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE recipe_ingredient (id INT UNSIGNED AUTO_INCREMENT NOT NULL, rid INT UNSIGNED DEFAULT NULL, iid INT UNSIGNED DEFAULT NULL, uid INT UNSIGNED DEFAULT NULL, modified_by INT DEFAULT NULL, created_by INT DEFAULT NULL, amount DOUBLE PRECISION NOT NULL, description VARCHAR(255) DEFAULT \'\' NOT NULL, modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_22D1FE1356D41083 (rid), INDEX IDX_22D1FE1346A75C12 (iid), INDEX IDX_22D1FE13539B0606 (uid), INDEX IDX_22D1FE1325F94802 (modified_by), INDEX IDX_22D1FE13DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE viand (id INT UNSIGNED AUTO_INCREMENT NOT NULL, default_quantity_unit INT UNSIGNED DEFAULT NULL, modified_by INT DEFAULT NULL, created_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_E2A18845E237E06 (name), INDEX IDX_E2A1884ADF7FE62 (default_quantity_unit), INDEX IDX_E2A188425F94802 (modified_by), INDEX IDX_E2A1884DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE viand_food_property (viand_id INT UNSIGNED NOT NULL, food_property_id INT UNSIGNED NOT NULL, INDEX IDX_D8447663D9DD27ED (viand_id), INDEX IDX_D8447663F524C522 (food_property_id), PRIMARY KEY(viand_id, food_property_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE food_property (id INT UNSIGNED AUTO_INCREMENT NOT NULL, modified_by INT DEFAULT NULL, created_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, exclusion_term VARCHAR(255) DEFAULT \'\' NOT NULL, exclusion_term_description VARCHAR(255) DEFAULT NULL, exclusion_term_short VARCHAR(255) DEFAULT NULL, modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_A3BF3B9C5E237E06 (name), INDEX IDX_A3BF3B9C25F94802 (modified_by), INDEX IDX_A3BF3B9CDE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE recipe (id INT UNSIGNED AUTO_INCREMENT NOT NULL, modified_by INT DEFAULT NULL, created_by INT DEFAULT NULL, title VARCHAR(255) NOT NULL, cooking_instructions LONGTEXT NOT NULL, modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_DA88B13725F94802 (modified_by), INDEX IDX_DA88B137DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE quantity_unit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, modified_by INT DEFAULT NULL, created_by INT DEFAULT NULL, name VARCHAR(255) NOT NULL, short VARCHAR(32) NOT NULL, modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_9BD6627125F94802 (modified_by), INDEX IDX_9BD66271DE12AB56 (created_by), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE recipe_ingredient ADD CONSTRAINT FK_22D1FE1356D41083 FOREIGN KEY (rid) REFERENCES recipe (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE recipe_ingredient ADD CONSTRAINT FK_22D1FE1346A75C12 FOREIGN KEY (iid) REFERENCES viand (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE recipe_ingredient ADD CONSTRAINT FK_22D1FE13539B0606 FOREIGN KEY (uid) REFERENCES quantity_unit (id) ON DELETE RESTRICT'
        );
        $this->addSql(
            'ALTER TABLE recipe_ingredient ADD CONSTRAINT FK_22D1FE1325F94802 FOREIGN KEY (modified_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE recipe_ingredient ADD CONSTRAINT FK_22D1FE13DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE viand ADD CONSTRAINT FK_E2A1884ADF7FE62 FOREIGN KEY (default_quantity_unit) REFERENCES quantity_unit (id) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE viand ADD CONSTRAINT FK_E2A188425F94802 FOREIGN KEY (modified_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE viand ADD CONSTRAINT FK_E2A1884DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE viand_food_property ADD CONSTRAINT FK_D8447663D9DD27ED FOREIGN KEY (viand_id) REFERENCES viand (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE viand_food_property ADD CONSTRAINT FK_D8447663F524C522 FOREIGN KEY (food_property_id) REFERENCES food_property (id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE food_property ADD CONSTRAINT FK_A3BF3B9C25F94802 FOREIGN KEY (modified_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE food_property ADD CONSTRAINT FK_A3BF3B9CDE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE recipe ADD CONSTRAINT FK_DA88B13725F94802 FOREIGN KEY (modified_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE recipe ADD CONSTRAINT FK_DA88B137DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE quantity_unit ADD CONSTRAINT FK_9BD6627125F94802 FOREIGN KEY (modified_by) REFERENCES `user` (uid) ON DELETE SET NULL'
        );
        $this->addSql(
            'ALTER TABLE quantity_unit ADD CONSTRAINT FK_9BD66271DE12AB56 FOREIGN KEY (created_by) REFERENCES `user` (uid) ON DELETE SET NULL;'
        );
        
        $this->addSql(
            "INSERT INTO quantity_unit (name, short, created_at)
VALUES
	('Stück', 'Stk', '2019-10-08 10:00:00'),
	('Kilogramm', 'kg', '2019-10-08 10:00:00'),
	('Gramm', 'g', '2019-10-08 10:00:00'),
	('Liter', 'l', '2019-10-08 10:00:00'),
	('Milliliter', 'ml', '2019-10-08 10:00:00'),
	('Packungen', 'Pack', '2019-10-08 10:00:00'),
	('Flasche', 'Flasche', '2019-10-08 10:00:00'),
	('Glas', 'Glas', '2019-10-08 10:00:00'),
	('Becher', 'Becher', '2019-10-08 10:00:00'),
	('Köpfe', 'Köpfe', '2019-10-08 10:00:00'),
	('Dosen', 'Dosen', '2019-10-08 10:00:00'),
	('Bund', 'Bund', '2019-10-08 10:00:00');
"
        );
        
        $this->addSql(
            "INSERT INTO food_property (name, exclusion_term, exclusion_term_description, exclusion_term_short, created_at)
VALUES
	('enthält Laktose', 'laktosefrei', 'ernährt sich laktosefrei', 'lf', '2019-10-08 10:00:00'),
	('enthält Fructose', 'fructosefrei', 'ernährt sich fructosefrei', 'ff', '2019-10-08 10:00:00'),
	('kann Spuren von Erdnüssen enthalten', 'Erdnussallergie', 'hat eine Erdnussallergie (bereits Spuren von Erdnüssen dürfen nicht konsumiert werden)', 'ER', '2019-10-08 10:00:00'),
	('enthält Erdnüsse', 'leichte Erdnussallergie', 'hat eine Erdnussallergie (Spuren von Erdnüssen sind akzeptabel)', 'er', '2019-10-08 10:00:00'),
	('kann Spuren von Gluten enthalten', 'Glutenallergie/Zöliakie', 'leidet an Zöliakie (o.ä.), darf auf keinen Fall keine Spuren von Gluten konsumieren', 'ZA', '2019-10-08 10:00:00'),
	('enthält Gluten', 'Glutenunverträglichkeit', 'hat eine Glutenunverträglichkeit (Spuren von Gluten sind akzeptabel)', 'gf', '2019-10-08 10:00:00'),
	('enthält Fleisch', 'vegetarisch', 'ernährt sich vegetarisch', 'vg', '2019-10-08 10:00:00'),
	('enthält tierische Erzeugnisse', 'vegan', 'ernährt sich vegan', 've', '2019-10-08 10:00:00'),
	('enthält Schwein', 'ohne Schwein', 'verzichtet auf Schweinefleisch', 'os', '2019-10-08 10:00:00'),
	('enthält Zitrusfrüchte', 'Zitrusfrucht-Allergie', 'Reagiert allergisch auf Zitrusfrüchte', 'zf', '2019-10-08 10:00:00');
"
        );
        
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE viand_food_property');
        $this->addSql('DROP TABLE food_property');
        $this->addSql('DROP TABLE recipe_ingredient');
        $this->addSql('DROP TABLE recipe');
        $this->addSql('DROP TABLE viand');
        $this->addSql('DROP TABLE quantity_unit');
    }
}
