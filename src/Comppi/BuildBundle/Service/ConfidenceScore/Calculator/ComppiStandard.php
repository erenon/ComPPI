<?php

namespace Comppi\BuildBundle\Service\ConfidenceScore\Calculator;

use Doctrine\DBAL\Types\IntegerType as IntegerParameter;

class ComppiStandard implements CalculatorInterface
{
    private $id;

    /**
     * @var Comppi\BuildBundle\Service\LocalizationTranslator\LocalizationTranslator
     */
    private $localizationTranslator;

    /**
     * @var array
     */
    private $scoreCache = array();

    private $fiInt = 1;

    public function __construct($id) {
        $this->id = $id;
    }

    public function setLocalizationTranslator($translator) {
        $this->localizationTranslator = $translator;
    }

    public function calculate(\Doctrine\DBAL\Connection $connection) {
        $this->initCalculation($connection);

        require_once("confidence.php");

        $insert = $connection->prepare(
            'INSERT INTO ConfidenceScore(interactionId, calculatorId, score)' .
            ' VALUES(?, ?, ?)'
        );

        $insert->bindValue(2, $this->id, IntegerParameter::INTEGER);

        $interactionSelect = $connection->prepare(
            'SELECT Interaction.id as id, Protein.specieId as speciesId FROM Interaction' .
            ' LEFT JOIN Protein ON Protein.id = Interaction.actorAId' .
            ' ORDER BY id ASC LIMIT ?, ?'
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

                $score = $pLinkLoc[$interaction['id']]; //from confidence.php

//                $score = $this->getScore(
//                    $interaction['id'],
//                    $interaction['speciesId']
//                );

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

    private function initCalculation(\Doctrine\DBAL\Connection $connection) {
        // @TODO this hack is required here because of a PDO bug
        // https://bugs.php.net/bug.php?id=44639
        $connection->getWrappedConnection()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        $this->initScoreCache();
    }

    private function initScoreCache() {

    }

    private function getScore($interactionId, $speciesId) {
        $this->selectInteractionSpecies->bindValue(1, $interactionId);

        $largelocs = $this->localizationTranslator->getLargelocs();
        $locval = array();
        foreach ($largelocs as $localizationId) {
            $locval[] = $this->getLocval($speciesId, $localizationId);
        }

        return $this->operatorOrArray($locval) * $this->fiInt;
    }

    private function getLocval($speciesId, $localizationId) {
        $osa = $this->correct(

        );

        $osb = $this->correct(

        );

        return $osa * $osb;
    }

    private function correct($a, $b, $avg, $pow) {
        $x = $avg + ($b - $avg) * $pow;

        if ($b < $avg) {
            return $a * (1 - $pow + ($pow * $b / $avg));
        } else {
            return $this->operatorOr($a, $pow * ($b - $avg) / (1 - $avg));
        }
    }

    private function operatorOr($a, $b) {
        return 1 - (1 - $a) * (1 - $b);
    }

    private function operatorOrArray(array $array) {
        $product = 1;
        foreach ($array as $a) {
            $product *= (1 - $a);
        }

        return 1 - $product;
    }
}