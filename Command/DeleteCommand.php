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
class DeleteCommand extends Command
{
    /**
     * {@hinheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('delete')
            ->setDescription(
                    'Deletes all issues (only if --all) in a project or issued created in last import '
                  . 'command run. Does nothing if cant find serielized created issues ids file'
                )
            ->addOption(
               'all',
               null,
               InputOption::VALUE_NONE,
               'if Specified, deletes all issues in a project. WARNING! all issues, not just the imported ones.'
            )
            ->addOption(
               'project',
               null,
               InputOption::VALUE_REQUIRED,
               'This must be set either if --all is set or just last Run Ids need to be deleted.' 
             . ' Its the project identifier, the one that appears in the URL. i.e. ' 
             . ' <RedmineDomain>/projects/<projectIdentifier>/issues?...'
            );
    }

    /**
     * {@hinheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $import = \ImportService::getInstance();
        $progectidentifier = $input->getOption('project') ? : null;
        if (null === $progectidentifier) {
            throw new \Exception('You must use --project="RedmineProectIdentifier"');
        }
        $all = $input->getOption('all') ? : null;
        if (null === $all) {
            $import->deleteLastRunCreatedIssues($progectidentifier);
        } else {
            $import->deleteIssuesInProject($progectidentifier);
        }
    }
}
