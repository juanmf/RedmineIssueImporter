<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Symfony Command component used for easing CLI arguments treatment.
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class ImportCommand extends Command
{
    /**
     * {@hinheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('import')
            ->setDescription('Imports issues in a Redmine project, from a data sheet')
            ->addArgument(
                'dataFile',
                InputArgument::REQUIRED,
                'absolute file path to the data sheet'
            )
            ->addOption(
               'sheet',
               null,
               InputOption::VALUE_REQUIRED,
               'The sheet name config to be used to interpret data in input sheet file.'
            )
            ->addOption(
               'record',
               null,
               InputOption::VALUE_REQUIRED,
               'The default record name config to be used to interpret data in input sheet file.'
            )
            ->addOption(
               'delimiter',
               null,
               InputOption::VALUE_REQUIRED,
               'The sheet field delimiter, for CSV.',
               null     
            )
            ->addOption(
               'fileType',
               null,
               InputOption::VALUE_REQUIRED,
               "The default file Format to be used, its case sensitive as the parser class name "
             . "beggins with this string and ends with 'SheetRecordParser' i.e. 'CsvSheetRecordParser'.",
               'Csv'     
            );
    }

    /**
     * {@hinheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = $input->getArgument('dataFile');
        $sheet = $input->getOption('sheet') ? : null;
        $record = $input->getOption('record') ? : null;
        $delimiter = $input->getOption('delimiter') ? : null;
        $fileType = $input->getOption('fileType') ? : null;

        if (null === $sheet || null === $record) {
            throw new \Exception('Both, --sheet and --record options must be set.');
        }
        
        $import = \ImportService::getInstance(
            $fileName, $sheet, $delimiter, $fileType, $record
        );
        $import->executeCreate();
    }
}
