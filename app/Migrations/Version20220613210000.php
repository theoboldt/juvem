<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Convert event feedback question topic to title
 */
final class Version20220613210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert event feedback question topic to title';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SELECT 1');
        $this->connection->beginTransaction();
        $result = $this->connection->executeQuery(
            'SELECT event.eid, event.feedback_questionnaire
               FROM event
              WHERE event.feedback_questionnaire IS NOT NULL',
            []
        );
        while ($row = $result->fetch()) {
            if (!empty($row['feedback_questionnaire'])) {
                $rowFeedbackQuestionnaire = json_decode($row['feedback_questionnaire'], true);
                $change                   = false;
                if (isset($rowFeedbackQuestionnaire['questions'])) {
                    foreach ($rowFeedbackQuestionnaire['questions'] as &$question) {
                        if (empty($question['title']) && isset($question['topic'])) {
                            $change            = true;
                            $question['title'] = $question['topic'];
                            $question['topic'] = '';
                        }
                    }
                    unset($question);
                }

                if ($change) {
                    $this->connection->executeStatement(
                        'UPDATE event SET feedback_questionnaire = ? WHERE eid = ?',
                        [json_encode($rowFeedbackQuestionnaire), $row['eid']]
                    );
                }
            }
        }
        $this->connection->commit();
    }

    public function down(Schema $schema): void
    {

        $this->addSql('SELECT 1');
        $this->connection->beginTransaction();
        $result = $this->connection->executeQuery(
            'SELECT event.eid, event.feedback_questionnaire
               FROM event
              WHERE event.feedback_questionnaire IS NOT NULL',
            []
        );
        while ($row = $result->fetch()) {
            if (!empty($row['feedback_questionnaire'])) {
                $rowFeedbackQuestionnaire = json_decode($row['feedback_questionnaire'], true);
                $change                   = false;
                if (isset($rowFeedbackQuestionnaire['questions'])) {
                    foreach ($rowFeedbackQuestionnaire['questions'] as &$question) {
                        if (isset($question['title']) && empty($question['topic'])) {
                            $change            = true;
                            $question['topic'] = $question['title'];
                            unset($question['title']);
                        }
                    }
                    unset($question);
                }
                if ($change) {
                    $this->connection->executeStatement(
                        'UPDATE event SET feedback_questionnaire = ? WHERE eid = ?',
                        [json_encode($rowFeedbackQuestionnaire), $row['eid']]
                    );
                }
            }
        }
        $this->connection->commit();
    }
}
