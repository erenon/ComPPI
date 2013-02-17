<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\DBAL\Types\IntegerType as IntegerParameter;

class GenerateGexfGraphCommand extends ContainerAwareCommand
{
    /**
     * @var Comppi\BuildBundle\Service\GexfWriter\GexfWriter
     */
    protected $writer;

    /**
     * @var Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieDescriptor
     */
    protected $specie;

    protected $buildPath;
    protected $buildTarget;

    protected function configure() {
        $this
            ->setName('comppi:generate:gexf')
            ->addArgument('specie', InputArgument::REQUIRED, 'Abbreviation of species  to load')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        $specieAbbr = $input->getArgument('specie');
        if (!$specieAbbr) {
            throw new \Exception("Please specify a species! Species availabe: ce, dm, hs, sc");
        }

        $container = $this->getContainer();

        $this->writer = $container->get('comppi.build.gexfWriter');

        $this->specie = $container
            ->get('comppi.build.specieProvider')
            ->getSpecieByAbbreviation($specieAbbr);

        $this->buildPath = $container->getParameter('comppi.build.buildPath');
        $this->buildTarget = $this->buildPath
            . DIRECTORY_SEPARATOR
            . 'comppi-' . $this->specie->abbreviation . '.gexf';

        $this->connection = $container
            ->get('doctrine.orm.default_entity_manager')
            ->getConnection();

        // @TODO this hack is required here because of a PDO bug
        // https://bugs.php.net/bug.php?id=44639
        $this->connection->getWrappedConnection()
             ->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

        // avoid memory leak
        $this->connection->getConfiguration()->setSQLLogger(null);

        $this->checkBuildDir();
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->writer->open($this->buildTarget);
        $this->writer->nodes();
        $this->addNodes($output);
        $this->writer->edges();
        $this->addEdges($output);
        $this->writer->close();
    }

    private function checkBuildDir() {
        if (!is_dir($this->buildPath)) {
            mkdir($this->buildPath, 0777, true);
        }
    }

    private function addNodes(OutputInterface $output) {
        $selectProteins = $this->connection->prepare(
        	'SELECT id, proteinName FROM Protein' .
            ' WHERE specieId = ? ORDER BY id ASC LIMIT ?, ?'
        );
        $selectProteins->bindValue(1, $this->specie->id, IntegerParameter::INTEGER);

        // init params
        $offset = 0;
        $blockSize = 1000;

        $selectProteins->bindValue(2, $offset, IntegerParameter::INTEGER);
        $selectProteins->bindValue(3, $blockSize, IntegerParameter::INTEGER);

        $selectProteins->execute();

        while ($selectProteins->rowCount() > 0) {
            // iterate over rows
            $proteins = $selectProteins->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
            foreach ($proteins as $protein) {
                $this->writer->addNode($protein['id'], $protein['proteinName']);
            }

            $output->writeln('  > ' . $selectProteins->rowCount() . ' proteins processed');

            // select proteins again
            $offset += $blockSize;

            $selectProteins->closeCursor();
            $selectProteins->bindValue(2, $offset, IntegerParameter::INTEGER);
            $selectProteins->execute();
        }
    }

    private function addEdges(OutputInterface $output) {
        $selectInteractions = $this->connection->prepare(
            'SELECT actorAId, actorBId FROM Interaction' .
            ' LEFT JOIN Protein ON Interaction.actorAId = Protein.id' .
            ' WHERE Protein.specieId = ? ORDER BY Interaction.id ASC LIMIT ?, ?'
        );
        $selectInteractions->bindValue(1, $this->specie->id, IntegerParameter::INTEGER);

        // init params
        $offset = 0;
        $blockSize = 1000;

        $selectInteractions->bindValue(2, $offset, IntegerParameter::INTEGER);
        $selectInteractions->bindValue(3, $blockSize, IntegerParameter::INTEGER);

        $selectInteractions->execute();

        while ($selectInteractions->rowCount() > 0) {
            // iterate over rows
            $interactions = $selectInteractions->fetchAll(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);
            foreach ($interactions as $interaction) {
                $this->writer->addEdge($interaction['actorAId'], $interaction['actorBId']);
            }

            $output->writeln('  > ' . $selectInteractions->rowCount() . ' interactions processed');

            // select interactions again
            $offset += $blockSize;

            $selectInteractions->closeCursor();
            $selectInteractions->bindValue(2, $offset, IntegerParameter::INTEGER);
            $selectInteractions->execute();
        }
    }
}