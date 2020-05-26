<?php declare(strict_types=1);

namespace Application\Migrations;

use AppBundle\Entity\Participant;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Remove all textual info text which should be regarded as empty
 */
final class Version20180101220000 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Remove all textual info text which should be regarded as empty';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema): void
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->select('p')
           ->from(Participant::class, 'p');
        $result = $qb->getQuery()->execute();

        /** @var Participant $participant */
        foreach ($result as $participant) {
            $changed     = false;
            $infoGeneral = $participant->getInfoGeneral();
            if (Participant::isInfoEmpty($infoGeneral)) {
                $participant->setInfoGeneral('');
                if ($infoGeneral != $participant->getInfoGeneral()) {
                    $changed = true;
                }
            }
            $infoMedical = $participant->getInfoMedical();
            if (Participant::isInfoEmpty($infoMedical)) {
                $participant->setInfoMedical('');
                if ($infoMedical != $participant->getInfoMedical()) {
                    $changed = true;
                }
            }

            if ($changed) {
                $em->persist($participant);
            }
        }

        $em->flush();

        $this->addSql('SELECT 1');
    }

    /**
     * {@inheritdoc}
     */
    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Once the info is changed, it can not be reverted');

    }
}
