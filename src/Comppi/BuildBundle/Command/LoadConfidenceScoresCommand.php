<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadConfidenceScoresCommand extends ContainerAwareCommand
{
    /**
     * @var Comppi\BuildBundle\Service\ConfidenceScore\ConfidenceScore
     */
    private $calculator;

    protected function configure() {
        $this
            ->setName('comppi:build:confidenceScores')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->calculator = $this->getContainer()->get('comppi.build.confidenceScore');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('  > calculating null score');

        $calculator = $this->calculator;
        $calculator->calculateScores($calculator::NULL_CALC);
    }
}