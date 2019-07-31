<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Convert attendance list into more customizable form
 */
final class Version20190730074221 extends AbstractMigration
{
    public function getDescription()
    {
        return 'Convert attendance list into more customizable form';
    }
    
    const COLUMN_PRESENT   = 1;
    const COLUMN_TRANSPORT = 2;
    const COLUMN_PAID      = 3;
    
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE attendance_list_column_assignments (list_id INT NOT NULL, column_id INT UNSIGNED NOT NULL, INDEX IDX_23A553A23DAE168B (list_id), INDEX IDX_23A553A2BE8E8ED5 (column_id), PRIMARY KEY(list_id, column_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE attendance_list_column_choices (choice_id INT UNSIGNED AUTO_INCREMENT NOT NULL, column_id INT UNSIGNED NOT NULL, title VARCHAR(255) NOT NULL, short_title VARCHAR(255) DEFAULT NULL, INDEX IDX_1099B71ABE8E8ED5 (column_id), PRIMARY KEY(choice_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'CREATE TABLE attendance_list_column (column_id INT UNSIGNED AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(column_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE attendance_list_column_assignments
               ADD CONSTRAINT FK_23A553A23DAE168B FOREIGN KEY (list_id) REFERENCES attendance_list (tid) ON DELETE CASCADE,
               ADD CONSTRAINT FK_23A553A2BE8E8ED5 FOREIGN KEY (column_id) REFERENCES attendance_list_column (column_id) ON DELETE CASCADE'
        );
        $this->addSql(
            'ALTER TABLE attendance_list_column_choices ADD CONSTRAINT FK_1099B71ABE8E8ED5 FOREIGN KEY (column_id) REFERENCES attendance_list_column (column_id) ON DELETE CASCADE'
        );
        
        $this->addSql(
            'CREATE TABLE attendance_list_participant_fillout (list_id INT NOT NULL, participant_id INT NOT NULL, column_id INT UNSIGNED NOT NULL, choice_id INT UNSIGNED NOT NULL, comment VARCHAR(255) DEFAULT NULL, modified_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_873133163DAE168B (list_id), INDEX IDX_873133169D1C3019 (participant_id), INDEX IDX_87313316BE8E8ED5 (column_id), INDEX IDX_87313316998666D1 (choice_id), PRIMARY KEY(list_id, participant_id, column_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB'
        );
        $this->addSql(
            'ALTER TABLE attendance_list_participant_fillout
                      ADD CONSTRAINT FK_873133163DAE168B FOREIGN KEY (list_id) REFERENCES attendance_list (tid) ON DELETE CASCADE,
                      ADD CONSTRAINT FK_873133169D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (aid) ON DELETE CASCADE,
                      ADD CONSTRAINT FK_87313316BE8E8ED5 FOREIGN KEY (column_id) REFERENCES attendance_list_column (column_id) ON DELETE CASCADE,
                      ADD CONSTRAINT FK_87313316998666D1 FOREIGN KEY (choice_id) REFERENCES attendance_list_column_choices (choice_id) ON DELETE CASCADE'
        );
        
        //Insert default configuration
        $this->addSql(
            "INSERT INTO attendance_list_column (column_id, title)
VALUES
	(" . self::COLUMN_PRESENT . ", 'Anwesenheit'),
	(" . self::COLUMN_TRANSPORT . ", 'Fahrkarte'),
	(" . self::COLUMN_PAID . ", 'Bezahlung')"
        );
        $this->addSql(
            "INSERT INTO attendance_list_column_choices (choice_id, column_id, title, short_title)
VALUES
	(1, " . self::COLUMN_PRESENT . ", 'Anwesend', NULL),
	(2, " . self::COLUMN_PRESENT . ", 'Nicht Anwesend', 'N'),
	(3, " . self::COLUMN_PRESENT . ", 'Entschuldigt', 'E'),
	(4, " . self::COLUMN_TRANSPORT . ", 'vorhanden', 'V'),
	(5, " . self::COLUMN_TRANSPORT . ", 'nicht vorhanden', 'N'),
	(6, " . self::COLUMN_PAID . ", 'bezahlt', 'B'),
	(7, " . self::COLUMN_PAID . ", 'nicht bezahlt', 'N');
"
        );
        $this->addSql(
            "INSERT INTO attendance_list_column_assignments (list_id, column_id) SELECT tid, " . self::COLUMN_PRESENT .
            " FROM attendance_list"
        );
        $this->addSql(
            "INSERT INTO attendance_list_column_assignments (list_id, column_id) SELECT tid, " .
            self::COLUMN_TRANSPORT . " FROM attendance_list WHERE is_public_transport <> 0"
        );
        $this->addSql(
            "INSERT INTO attendance_list_column_assignments (list_id, column_id) SELECT tid, " . self::COLUMN_PAID .
            " FROM attendance_list WHERE is_paid <> 0"
        );
        
        $rows       = $this->connection->fetchAll(
            'SELECT * FROM attendance_list_fillout',
            [self::COLUMN_TRANSPORT]
        );
        $filloutSql = 'INSERT INTO attendance_list_participant_fillout (list_id, participant_id, column_id, choice_id, comment, modified_at, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?)';
        
        foreach ($rows as $row) {
            if ($row['is_attendant'] !== null) {
                $choice = 1;
                if (!$row['is_attendant']) {
                    $choice = 2;
                }
                $this->addSql(
                    $filloutSql,
                    [$row['tid'], $row['aid'], self::COLUMN_PRESENT, $choice, $row['comment'], $row['modified_at'],
                     $row['created_at']
                    ]
                );
            }
            if ($row['is_public_transport'] !== null) {
                if ($row['is_public_transport']) {
                    $this->addSql(
                        $filloutSql,
                        [$row['tid'], $row['aid'], self::COLUMN_TRANSPORT, 4, $row['comment'], $row['modified_at'],
                         $row['created_at']
                        ]
                    );
                } else {
                    $this->addSql(
                        $filloutSql,
                        [$row['tid'], $row['aid'], self::COLUMN_TRANSPORT, 5, $row['comment'], $row['modified_at'],
                         $row['created_at']
                        ]
                    );
                }
            }
            if ($row['is_paid'] !== null) {
                if ($row['is_paid']) {
                    $this->addSql(
                        $filloutSql,
                        [$row['tid'], $row['aid'], self::COLUMN_PAID, 6, $row['comment'], $row['modified_at'],
                         $row['created_at']
                        ]
                    );
                } else {
                    $this->addSql(
                        $filloutSql,
                        [$row['tid'], $row['aid'], self::COLUMN_PAID, 7, $row['comment'], $row['modified_at'],
                         $row['created_at']
                        ]
                    );
                }
            }
        }
        $this->addSql('DROP TABLE attendance_list_fillout');
        $this->addSql('ALTER TABLE attendance_list ADD start_date DATE, DROP is_public_transport, DROP is_paid');
    }
    
    public function down(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE attendance_list_fillout (
  did int(11) NOT NULL AUTO_INCREMENT,
  tid int(11) DEFAULT NULL,
  aid int(11) DEFAULT NULL,
  is_attendant tinyint(1) DEFAULT NULL,
  is_public_transport tinyint(1) DEFAULT NULL,
  is_paid tinyint(1) DEFAULT NULL,
  comment varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  modified_at datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY (did),
  KEY IDX_7AE6302B52596C31 (tid),
  KEY IDX_7AE6302B48B40DAA (aid),
  CONSTRAINT FK_7AE6302B48B40DAA FOREIGN KEY (aid) REFERENCES participant (aid) ON DELETE CASCADE,
  CONSTRAINT FK_7AE6302B52596C31 FOREIGN KEY (tid) REFERENCES attendance_list (tid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci'
        );
        $this->addSql(
            'ALTER TABLE attendance_list ADD is_public_transport TINYINT(1) NOT NULL, ADD is_paid TINYINT(1) NOT NULL, DROP start_date'
        );
        
        $rows = $this->connection->fetchAll(
            'SELECT * FROM attendance_list_participant_fillout',
            );
        $data = [];
        
        foreach ($rows as $row) {
            if (!isset($data[$row['list_id']])) {
                $data[$row['list_id']] = [];
            }
            if (!isset($data[$row['list_id']][$row['participant_id']])) {
                $data[$row['list_id']][$row['participant_id']] = [
                    'tid'                 => (int)$row['list_id'],
                    'aid'                 => (int)$row['participant_id'],
                    'is_attendant'        => null,
                    'is_public_transport' => null,
                    'is_paid'             => null,
                    'comment'             => $row['comment'],
                    'modified_at'         => $row['modified_at'],
                    'created_at'          => $row['created_at'],
                ];
            }
            switch ($row['choice_id']) {
                case 1:
                    $data[$row['list_id']][$row['participant_id']]['is_attendant'] = 1;
                    break;
                case 2:
                    $data[$row['list_id']][$row['participant_id']]['is_attendant'] = 0;
                    break;
                case 4:
                    $data[$row['list_id']][$row['participant_id']]['is_public_transport'] = 1;
                    break;
                case 5:
                    $data[$row['list_id']][$row['participant_id']]['is_public_transport'] = 0;
                    break;
                case 6:
                    $data[$row['list_id']][$row['participant_id']]['is_paid'] = 1;
                    break;
                case 7:
                    $data[$row['list_id']][$row['participant_id']]['is_paid'] = 0;
                    break;
                default:
                    $this->warnIf(true, 'Data for fillout ' . $row['list_fillout_id'] . ' will be lost');
                    break;
            }
        }
        foreach ($data as $listId => $rows) {
            foreach ($rows as $datum) {
                $this->addSql(
                    'INSERT INTO attendance_list_fillout (tid, aid, is_attendant, is_public_transport, is_paid, comment, modified_at, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    array_values($datum)
                );
            }
        }
        
        $rows = $this->connection->fetchAll(
            'SELECT list_id FROM attendance_list_column_assignments WHERE column_id = ?',
            [self::COLUMN_TRANSPORT]
        );
        foreach ($rows as $row) {
            $this->addSql('UPDATE attendance_list SET is_public_transport = 1 WHERE tid = ?', [$row['list_id']]);
        }
        $rows = $this->connection->fetchAll(
            'SELECT list_id FROM attendance_list_column_assignments WHERE column_id = ?',
            [self::COLUMN_PAID]
        );
        foreach ($rows as $row) {
            $this->addSql('UPDATE attendance_list SET is_paid = 1 WHERE tid = ?', [$row['list_id']]);
        }
        
        $this->addSql('DROP TABLE attendance_list_participant_fillout');
        $this->addSql('DROP TABLE attendance_list_column_assignments');
        $this->addSql('DROP TABLE attendance_list_column_choices');
        $this->addSql('DROP TABLE attendance_list_column');
    }
}
