<?php

namespace App\Command;

use AMQPConnection;
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
    name: 'rabbitmq:fanout:message',
    description: 'Add a short description for your command',
)]
class RabbitmqFanoutMessageCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('exchange', InputArgument::REQUIRED, 'rabbitmq exchange name')
            ->addArgument('message', InputOption::VALUE_REQUIRED, 'message data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $exchange = $input->getArgument('exchange');
        $message = $input->getArgument('message');

        $io->note(sprintf('You passed an argument: exchange %s message %s', $exchange, $message));

        $msgAmqp = new AMQPMessage($message);
        $Connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $Channel = $Connection->channel();

        $Channel->basic_publish($msgAmqp, $exchange);

        $Channel->close();
        $Connection->close();

        $success = sprintf('You have a publish message "%s" to %s! Pass --help to see your options.', $message, $exchange);
        $io->success($success);

        return Command::SUCCESS;
    }
}
