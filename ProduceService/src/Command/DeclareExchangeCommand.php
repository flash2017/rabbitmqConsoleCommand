<?php

namespace App\Command;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'declare:exchange',
    description: 'Add a short description for your command',
)]
class DeclareExchangeCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'exchange name')
            ->addArgument('type', InputArgument::REQUIRED, 'exchange type: fanout, direct, topic, headers')
            ->addOption('passive', 'p', InputOption::VALUE_OPTIONAL, 'exchange pasive boolean value ', false)
            ->addOption('auto_delete', 'ad', InputOption::VALUE_OPTIONAL, 'exchange auto delete boolean value ', false)
            ->addOption('durable', 'd', InputOption::VALUE_OPTIONAL, 'exchange durable boolean value ', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $type = $input->getArgument('type');
        $passive = $input->getOption('passive');
        $autoDelete = $input->getOption('auto_delete');
        $durable = $input->getOption('durable');

        $commandData = sprintf('name [%s] passive [%s] durable [%s] auto_delete [%s]', $name, ($passive ?? 0), ($durable ?? 0), ($autoDelete ?? 0));

        $io->note("You passed an argument $commandData");

        $Connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $Channel = $Connection->channel();
        $result = $Channel->exchange_declare(
            $name,
            $type,
            $passive,
            $durable,
            $autoDelete
        );

        $Channel->close();
        $Connection->close();

        $io->success(sprintf("You create a new exchange: %s %s",$commandData,  $result));

        return Command::SUCCESS;
    }
}
