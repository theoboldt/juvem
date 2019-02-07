<?php declare(strict_types=1);

namespace Application\Migrations;

use AppBundle\Entity\Participant;
use AppBundle\Entity\ParticipantPaymentEvent;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
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
            $price   = $this->getPriceForParticipant($em, $participant);
            if ($participant->getBasePrice() !== $price) {
                $participant->setBasePrice($price);
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
    
    /**
     * Get current price for transmitted @see Participant
     *
     * @param EntityManager $em Entity manager
     * @param Participant $participant Desired participant
     * @return int
     */
    private function getPriceForParticipant(EntityManager $em, Participant $participant)
    {
        $qb = $em->createQueryBuilder();
        $qb->select('e.value')
           ->from(ParticipantPaymentEvent::class, 'e')
           ->innerJoin('e.participant', 'a')
           ->andWhere($qb->expr()->eq('a.aid', ':aid'))
            ->andWhere('e.isPriceSet = 1')
           ->orderBy('e.createdAt', 'DESC')
           ->setMaxResults(1);

        $result = $qb->getQuery()->execute(['aid' => $participant->getAid()]);
        if (is_array($result) && isset($result[0]) && isset($result[0]['value'])) {
            return $result[0]['value'];
        } else {
            return null;
        }
    }

}
