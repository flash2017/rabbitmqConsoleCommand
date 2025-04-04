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
    name: 'rabbitmq:topic:consume',
    description: 'Add a short description for your command',
)]
class RabbitmqTopicConsumeCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('queueName', InputArgument::REQUIRED, 'Argument description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queueName = $input->getArgument('queueName');

        $Connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $Channel = $Connection->channel();
        $callback = function (AMQPMessage $msg) use ($io) {
            sleep(5);
            $io->success($msg->getBody());
            $msg->ack();
        };
        $Channel->basic_qos(0, 1, false);
        $Channel->basic_consume(
            $queueName,
            '',
            false,
            false,
            false,
            false,
            $callback
        );

        $io->note(sprintf('You start consume queue "%s"', $queueName));

        try {
            $Channel->consume();
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
        }
        $Channel->close();
        $Connection->close();

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
