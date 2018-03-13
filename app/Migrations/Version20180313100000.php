<?php declare(strict_types=1);

namespace Application\Migrations;

use AppBundle\Entity\Participant;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Introduce and calculate to pay values for participants
 */
class Version20180313100000 extends AbstractMigration implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Introduce and calculate to pay values for participants';
    }

    public function up(Schema $schema)
    {
        $this->connection->executeQuery('ALTER TABLE participant ADD to_pay INT DEFAULT NULL');

        $em = $this->container->get('doctrine.orm.entity_manager');
        $pm = $this->container->get('app.payment_manager');

        $qb = $em->createQueryBuilder();
        $qb->select('p')
           ->from(Participant::class, 'p');
        $result = $qb->getQuery()->execute();

        /** @var Participant $participant */
        foreach ($result as $participant) {
            $changed = false;
            $price   = $pm->getPriceForParticipant($participant);
            if ($participant->getPrice() !== $price) {
                $changed = true;
                $participant->setPrice($price);
            }
            if ($price !== null) {
                $changed = true;
                $toPay   = $pm->toPayValueForParticipant($participant);
                $participant->setToPay($toPay);
            }
            if ($changed) {
                $em->persist($participant);
            }
        }

        $em->flush();

        $this->addSql('SELECT 1');
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE participant DROP to_pay');
    }
}
