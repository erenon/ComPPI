<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateStatsCommand extends ContainerAwareCommand
{
    /**
     * LocalizationTranslator service
     * @var Comppi\BuildBundle\Service\LocalizationTranslator\LocalizationTranslator
     */
    protected $localizationTranslator;

    /**
     * @var Comppi\StatBundle\Service\Statistics\Statistics
     */
    protected $statistics;

    /**
     * @var Comppi\BuildBundle\Service\SpecieProvider\SpecieProvider
     */
    protected $species;

    protected $buildPath;

    protected function configure() {
        $this
            ->setName('comppi:generate:stats')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        $container = $this->getContainer();
        $this->localizationTranslator = $container->get('comppi.build.localizationTranslator');
        $this->statistics = $container->get('comppi.stat.statistics');
        $this->species = $container->get('comppi.build.specieProvider');
        $this->buildPath = $container->getParameter('comppi.build.buildPath');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $histogram = array();
        foreach ($this->species->getDescriptors() as $specie) {
            $specieStat = $this->statistics->getInteractionHistogram(10, $specie->id);

            foreach ($specieStat as $index => $column) {
                $label = ($column['min'] == $column['max'])
                    ? $column['min']
                    : $column['min'] . " - ". $column['max'];

                $specieStat[$index] = array(
                	'label' => $label,
                    'values' => (int)$column['count']
                );
            }

            $histogram[$specie->id] = $specieStat;
        }

        $this->checkBuildDir();

        file_put_contents(
            $this->buildPath . DIRECTORY_SEPARATOR . 'interactionHistogram.json',
            json_encode($histogram)
        );
    }

    private function checkBuildDir() {
        if (!is_dir($this->buildPath)) {
            mkdir($this->buildPath, 0777, true);
        }
    }
}