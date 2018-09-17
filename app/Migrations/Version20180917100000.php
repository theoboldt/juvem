<?php declare(strict_types=1);

namespace Application\Migrations;

use AppBundle\Entity\AcquisitionAttribute\Attribute;
use AppBundle\Entity\AcquisitionAttribute\AttributeChoiceOption;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180917100000 extends AbstractMigration implements ContainerAwareInterface {
    
    use ContainerAwareTrait;
    
    public function up(Schema $schema) {
        $this->addSql(
            'CREATE TABLE acquisition_attribute_choice_option (id INT AUTO_INCREMENT NOT NULL, bid INT DEFAULT NULL, form_title VARCHAR(255) NOT NULL, management_title VARCHAR(255) NOT NULL, short_title VARCHAR(255) DEFAULT NULL, hide_in_form SMALLINT UNSIGNED DEFAULT 0 NOT NULL, INDEX IDX_F421FA2D4AF2B3F3 (bid), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE acquisition_attribute_choice_option ADD CONSTRAINT FK_F421FA2D4AF2B3F3 FOREIGN KEY (bid) REFERENCES acquisition_attribute (bid) ON DELETE CASCADE'
        );
        
        $repository = $this->container->get('doctrine')->getRepository(Attribute::class);
        $attributes = $repository->findBy(['fieldType' => ChoiceType::class]);
        
        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            if ($attribute->getFieldType() === ChoiceType::class) {
                $options = $attribute->getFieldTypeChoiceOptions(true);
                foreach (array_keys($options) as $title) {
                    $this->addSql(
                        'INSERT INTO acquisition_attribute_choice_option (bid, form_title, management_title) VALUES (?, ?, ?)',
                        [$attribute->getBid(), $title, $title]
                    );
                }
            }
        }
    }
    
    public function down(Schema $schema) {
        $em          = $this->container->get('doctrine.orm.entity_manager');
        $repository  = $this->container->get('doctrine')->getRepository(Attribute::class);
        $allOptions  = $this->container->get('doctrine')->getRepository(AttributeChoiceOption::class)->findAll();
        $choiceLists = [];
        
        /** @var AttributeChoiceOption $option */
        foreach ($allOptions as $option) {
            $title = str_replace(';', ',', $option->getFormTitle());
            if (!isset($choiceLists[$option->getAttribute()->getBid()])) {
                $choiceLists[$option->getAttribute()->getBid()] = [];
            }
            $choiceLists[$option->getAttribute()->getBid()][$title] = sha1($title);
        }
        
        foreach ($choiceLists as $bid => $choices) {
            /** @var Attribute $attribute */
            $attribute               = $repository->find($bid);
            $fieldOptions            = $attribute->getFieldOptions();
            $fieldOptions['choices'] = $choices;
            $attribute->setFieldOptions($fieldOptions);
            $em->persist($attribute);
        }
        $em->flush();
        
        $this->addSql('DROP TABLE acquisition_attribute_choice_option');
    }
}
