<?php

namespace App\Command;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rabbitmq:topic:produce',
    description: 'Add a short description for your command',
)]
class RabbitmqTopicProduceCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('routingKey',InputArgument::REQUIRED, 'Option description')
            ->addArgument('message', InputArgument::REQUIRED, 'Argument description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $message = $input->getArgument('message');
        $routingKey = $input->getArgument('routingKey');

        $io->note(sprintf('You produce message "%s" topic %s to exchange error', $message, $routingKey));

        $Connections = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $Channel = $Connections->channel();
        $message = new AMQPMessage($message);
        $Channel->basic_publish($message, 'error', $routingKey);

        $Channel->close();
        $Connections->close();

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
