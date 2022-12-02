<?php

namespace ForestAdmin\AgentPHP\Agent\Console\Commands;

use ForestAdmin\AgentPHP\Agent\Builder\AgentFactory;
use ForestAdmin\AgentPHP\Agent\ForestAdminHttpDriver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendApimapCommand extends Command
{
    protected function configure()
    {
        $this->setName('send-apimap')
            ->setDescription('Send the apimap to Forest');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //ForestAdminHttpDriver::sendSchema(AgentFactory::get('datasource'));

        $output->writeln('<info>Apimap sent</info>');

        return Command::SUCCESS;
    }
}
