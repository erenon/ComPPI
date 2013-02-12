<?php

namespace Comppi\BuildBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckLocalizationsCommand extends ContainerAwareCommand
{
    /**
     * LocalizationTranslator service
     * @var Comppi\BuildBundle\Service\LocalizationTranslator\LocalizationTranslator
     */
    protected $localizationTranslator;

    protected function configure() {
        $this
            ->setName('comppi:check:localizations')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output) {
        $this->localizationTranslator = $this->getContainer()->get('comppi.build.localizationTranslator');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        foreach ($this->localizationTranslator->localizations as $localzation) {
            $id = $localzation['id'];

            try {
                $largeloc = $this->localizationTranslator->getLargelocById($id);
            } catch (\InvalidArgumentException $e) {
                $output->writeln("<error>No largeloc found for id: '" . $id . "'</error>");
            }
        }
    }
}