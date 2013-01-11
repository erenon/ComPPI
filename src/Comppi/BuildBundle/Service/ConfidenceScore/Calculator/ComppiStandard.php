<?php

namespace Comppi\BuildBundle\Service\ConfidenceScore\Calculator;

use Doctrine\DBAL\Types\IntegerType as IntegerParameter;

class ComppiStandard implements CalculatorInterface
{
    private $id;

    public function __construct($id) {
        $this->id = $id;
    }

    public function calculate(\Doctrine\DBAL\Connection $connection) {
        // @TODO this hack is required here because of a PDO bug
        // https://bugs.php.net/bug.php?id=44639
        $connection->getWrappedConnection()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        $insert = $connection->prepare(
            'INSERT INTO ConfidenceScore(interactionId, calculatorId, score)' .
            ' VALUES(?, ?, ?)'
        );

        $insert->bindValue(2, $this->id, IntegerParameter::INTEGER);

        $interactionSelect = $connection->prepare(
            'SELECT id FROM Interaction ORDER BY id ASC LIMIT ?, ?'
        );

        $interactionOffset = 0;
        $blockSize = 500;

        $interactionSelect->bindValue(1, $interactionOffset, IntegerParameter::INTEGER);
        $interactionSelect->bindValue(2, $blockSize, IntegerParameter::INTEGER);

        $interactionSelect->execute();

        while ($interactionSelect->rowCount() > 0) {
            $interactions = $interactionSelect->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

            $connection->beginTransaction();

            foreach ($interactions as $interaction) {
                // TODO calculate confidence score here
                $score = 0;

                $insert->bindValue(1, $interaction['id']);
                $insert->bindValue(3, $score);
                $insert->execute();
            }

            $connection->commit();

            // advance cursor
            $interactionOffset += $blockSize;

            $interactionSelect->closeCursor();
            $interactionSelect->bindValue(1, $interactionOffset, IntegerParameter::INTEGER);
            $interactionSelect->execute();
        }
    }

    public function getName() {
        return "ComPPI Standard";
    }
}