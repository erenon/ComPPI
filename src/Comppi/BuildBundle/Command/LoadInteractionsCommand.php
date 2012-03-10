<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadInteractionsCommand extends ContainerAwareCommand
{
    /**
     * Collection of interaction databases
     */
    private $databases;
    private $translator;
    private $specie;
    
    /**
     * @var Doctrine\DBAL\Connection
     */
    private $connection;
    
    protected function configure() {
        $this
            ->setName('comppi:build:interactions')
            ->addArgument('specie', InputArgument::REQUIRED, 'Specie abbreviation to load')
        ;
    }
    
    protected function initialize(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();

        $specie = $input->getArgument('specie');
        if (!$specie) {
            throw new \Exception("Please specify a specie");
        }
        $this->specie = $specie;
        
        $databaseProvider = $container->get('comppi.build.databaseProvider');
        $this->databases = $databaseProvider->getInteractionsBySpecie($specie);
        
        $this->translator = $container->get('comppi.build.proteinTranslator');
        $this->connection = $this
            ->getContainer()
            ->get('doctrine.orm.default_entity_manager')
            ->getConnection();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output) {
        $interactionEntityName = 'Interaction' . ucfirst($this->specie);        
        $recordsPerTransaction = 100;
        
        $connection = $this->connection;
        $translator = $this->translator;            
            
        foreach ($this->databases as $database) {
            $output->writeln('  > loading interaction database: ' . get_class($database));
            
            $sourceDb = $database->getDatabaseIdentifier();
            $sourceNamingConvention = $database->getDatabaseNamingConvention();
            
            $recordIdx = 0;
            $connection->beginTransaction();
            
            foreach ($database as $interaction) {
                // get proteinA name
                $proteinAOriginalName = $interaction['proteinAName'];
                $proteinAComppiId = $translator->getComppiId(
                    $sourceNamingConvention, $proteinAOriginalName, $this->specie
                );
                
                $proteinBOriginalName = $interaction['proteinBName'];
                $proteinBComppiId = $translator->getComppiId(
                    $sourceNamingConvention, $proteinBOriginalName, $this->specie
                );
                
                /**
                 * @TODO This insert is wrong
                 * If the protein is translatable, the ComppiId lookup
                 * will fail continuously becouse it will try it with
                 * stronger convention.
                 * 
                 * Restructure the table schema, and use
                 * translated names in the following two inserts. 
                 */
                $this->insertProtein(
                    $this->specie,
                    $proteinAComppiId, 
                    $proteinAOriginalName, 
                    $sourceNamingConvention, 
                    $sourceDb
                );
                
                $this->insertProtein(
                    $this->specie,
                    $proteinBComppiId, 
                    $proteinBOriginalName, 
                    $sourceNamingConvention, 
                    $sourceDb
                );
                
                $connection->insert($interactionEntityName, array(
                    'actorAId' => $proteinAComppiId,
                    'actorBId' => $proteinBComppiId,
                    'sourceDb' => $sourceDb,
                    'pubmedId' => $interaction['pubmedId'],
                    'experimentalSystemType' => $interaction['experimentalSystemType']
                ));
                
                // flush transaction
                $recordIdx++;
                if ($recordIdx == $recordsPerTransaction) {
                    $recordIdx = 0;
                    
                    $connection->commit();
                    $connection->beginTransaction();
                    
                    $output->writeln('  > ' . $recordsPerTransaction . ' records loaded');
                }
            }
            
            $connection->commit();
        }            
    }
    
    private function insertProtein($specie, $comppiId, $sourceId, $sourceNamingConvention, $sourceDb) {
        if ($comppiId === false) {
            $comppiId = $this->getNewComppiId($specie);
        }
        
        $proteinToDatabaseTable = 'ProteinToDatabase' . ucfirst($specie);
        $this->connection->executeQuery(
            'INSERT INTO ' .$proteinToDatabaseTable.
            ' VALUES ("", ?, ?, ?, ?)',
            array($comppiId, $sourceId, $sourceNamingConvention, $sourceDb)
        );
    }
    
    private function getNewComppiId($specie) {
        $proteinTable = 'Protein' . ucfirst($specie);
        $this->connection->executeQuery(
        	'INSERT INTO ' . $proteinTable . ' VALUES ("")'
        );
        
        return $this->connection->lastInsertId();
    }
}