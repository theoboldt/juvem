<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * Introduce more complex choice options
 */
final class Version20180917100000 extends AbstractMigration implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    public function getDescription(): string
    {
        return 'Introduce more complex choice options';
    }


    public function up(Schema $schema): void
    {

        $this->connection->exec(
            'CREATE TABLE acquisition_attribute_choice_option (id INT AUTO_INCREMENT NOT NULL, bid INT DEFAULT NULL, form_title VARCHAR(255) NOT NULL, management_title VARCHAR(255) DEFAULT NULL, short_title VARCHAR(255) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_F421FA2D4AF2B3F3 (bid), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->connection->exec(
            'ALTER TABLE acquisition_attribute_choice_option ADD CONSTRAINT FK_F421FA2D4AF2B3F3 FOREIGN KEY (bid) REFERENCES acquisition_attribute (bid) ON DELETE CASCADE'
        );

        $fields = $this->fetchFields();
        foreach ($fields as $bid => $fieldOptions) {
            $options    = json_decode($fieldOptions, true);
            $choicesOld = $options['choices'];
            $choicesNew = [];

            foreach ($choicesOld as $title => $oldKey) {
                $this->connection->executeQuery(
                    'INSERT INTO acquisition_attribute_choice_option (bid, form_title) VALUES (?, ?)',
                    [$bid, $title]
                );
                $newKey              = (int)$this->connection->lastInsertId();
                $choicesNew[$oldKey] = $newKey;
            }

            unset($options['choices']);
            $this->connection->executeStatement(
                'UPDATE acquisition_attribute SET field_options = ? WHERE bid = ?',
                [json_encode($options), $bid]
            );

            $this->replaceOption($bid, $choicesNew);
        }

        $this->addSql('SELECT 1');
    }

    /**
     * Fetch fields
     *
     * @return array
     */
    private function fetchFields(): array
    {
        $qbA = $this->connection->createQueryBuilder();
        $qbA->select(['bid', 'field_options'])
            ->from('acquisition_attribute')
            ->andWhere($qbA->expr()->eq('field_type', ':type'))
            ->setParameter('type', ChoiceType::class);
        $fields = [];
        foreach ($qbA->execute()->fetchAll() as $field) {
            $fields[$field['bid']] = $field['field_options'];
        }
        return $fields;
    }

    /**
     * Replace options
     *
     * @param int   $bid        Related field id
     * @param array $choicesNew New choice conversion
     */
    private function replaceOption(int $bid, array $choicesNew)
    {
        $qbC = $this->connection->createQueryBuilder();
        $qbC->select(['oid', 'value'])
            ->from('acquisition_attribute_fillout')
            ->andWhere($qbC->expr()->eq('bid', ':bid'))
            ->andWhere($qbC->expr()->isNotNull('value'));

        $qbC->setParameter('bid', $bid);
        $fillouts = $qbC->execute()->fetchAll();
        foreach ($fillouts as $fillout) {
            $newValue = null;
            $oldValue = json_decode($fillout['value'], true, 16);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $oldValue = $fillout['value'];
            }

            if (is_array($oldValue)) {
                $newValue = [];
                foreach ($oldValue as $oldChoice) {
                    $newValue[] = $this->findOption($bid, $oldChoice, $choicesNew);
                }
                $newValue = json_encode($newValue);
            } else {
                $newValue = $this->findOption($bid, $oldValue, $choicesNew);
            }
            if ($newValue !== null && $newValue != $fillout['value']) {
                $affected = $this->connection->executeStatement(
                    'UPDATE acquisition_attribute_fillout SET value = ? WHERE oid = ?',
                    [$newValue, $fillout['oid']]
                );
                if ($affected !== 1) {
                    $this->write(
                        sprintf(
                            'Did not find fillout %d for value %s from %s', $fillout['oid'], $newValue,
                            $fillout['value']
                        )
                    );
                }
            }
        }
    }

    /**
     * Find option in new field
     *
     * @param int    $bid        Related field id for possible issue
     * @param string $oldChoice  New choice
     * @param array  $choicesNew Conversion table of new choices
     * @return string
     */
    private function findOption(int $bid, $oldChoice, array $choicesNew)
    {
        if (isset($choicesNew[$oldChoice])) {
            return $choicesNew[$oldChoice];
        } else {
            $this->write(
                sprintf('Failed to find new option for value "%s" of field %d', $oldChoice, $bid)
            );
            return $oldChoice;
        }
    }

    public function down(Schema $schema): void
    {
        $qbC = $this->connection->createQueryBuilder();
        $qbC->select(['id', 'bid', 'form_title'])
            ->from('acquisition_attribute_choice_option');

        $qbO = $this->connection->createQueryBuilder();
        $qbO->select(['oid', 'value'])
            ->from('acquisition_attribute_fillout')
            ->andWhere($qbO->expr()->eq('bid', ':bid'))
            ->andWhere($qbO->expr()->isNotNull('value'));

        $choiceConversion = [];
        $choicesList      = [];
        foreach ($qbC->execute()->fetchAll() as $option) {
            if (!isset($choiceConversion[$option['bid']])) {
                $choiceConversion[$option['bid']] = [];
            }
            $choiceConversion[$option['bid']][$option['id']] = sha1($option['form_title']);

            if (!isset($choicesList[$option['bid']])) {
                $choicesList[$option['bid']] = [];
            }
            $choicesList[$option['bid']][$option['form_title']] = sha1($option['form_title']);

        }

        $fields = $this->fetchFields();

        foreach ($choiceConversion as $bid => $choices) {
            $this->replaceOption($bid, $choices);

            $fieldOptions            = json_decode($fields[$bid], true);
            $fieldOptions['choices'] = $choicesList[$bid];

            $this->connection->executeStatement(
                'UPDATE acquisition_attribute SET field_options = ? WHERE bid = ?',
                [json_encode($fieldOptions), $bid]
            );

        }

        $this->addSql('DROP TABLE acquisition_attribute_choice_option');
    }
}
