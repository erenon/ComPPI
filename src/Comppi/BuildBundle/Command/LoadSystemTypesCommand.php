<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadSystemTypesCommand extends ContainerAwareCommand
{
    /**
     * @var Comppi\BuildBundle\Service\SystemTypeTranslator\SystemTypeTranslator
     */
    private $translator;

    protected function configure() {
        $this
            ->setName('comppi:build:systems')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->translator = $this->getContainer()->get('comppi.build.systemTypeTranslator');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->translator->loadSystems();
    }
}