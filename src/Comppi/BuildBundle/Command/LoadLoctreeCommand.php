<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadLoctreeCommand extends ContainerAwareCommand
{
	/**
	 * @var Doctrine\DBAL\Connection
	 */
	protected $connection;
	
	/**
	 * LocalizationTranslator service
	 * @var Comppi\BuildBundle\Service\LocalizationTranslator\LocalizationTranslator
	 */
	protected $localizationTranslator;
	
    protected function configure() {
        $this
            ->setName('comppi:build:loctree')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
    	$container = $this->getContainer();
    	$this->localizationTranslator = $container->get('comppi.build.localizationTranslator');
    	
    	// setup database connection
    	$this->connection = $container
	    	->get('doctrine.orm.default_entity_manager')
	    	->getConnection();
    	
    	// avoid memory leak
    	$this->connection->getConfiguration()->setSQLLogger(null);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
		$insert = $this->connection->prepare(
			'INSERT INTO Loctree(id, secondaryId, goCode, name, majorLocName)' .
			' VALUES(?, ?, ?, ?, ?)'
		);
		
		$this->connection->beginTransaction();
		
		$localizations = $this->localizationTranslator->localizations;
		
		foreach ($localizations as $loc) {
			if ($loc['id'] == 0) continue; // skip Cell
			
			$majorLoc = $this->localizationTranslator->getLargelocById($loc['id']);
			$insert->execute(array(
				$loc['id'], 
				$loc['sid'], 
				$loc['name'], 
				$loc['humanReadable'],
				$majorLoc
			));
		}
		
		$this->connection->commit();
    }
}