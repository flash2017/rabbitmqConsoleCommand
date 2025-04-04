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
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'rabbitmq:consume:message',
    description: 'Add a short description for your command',
)]
class RabbitmqConsumeMessageCommand extends Command
{
    private SerializerInterface $serializer;
    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct();

        $this->serializer = $serializer;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('queueName', InputArgument::REQUIRED, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queueName = $input->getArgument('queueName');

        if ($queueName) {
           $io->note(sprintf('You passed an argument: %s', $queueName));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $io->note(" [*] Waiting for messages. To exit press CTRL+C");

        $callback = function ($msg) use ($io)  {
            $object = implode("|", json_decode($msg->body, true));
            $io->note(sprintf(' [x] Received %s', $object));
        };

        $channel->basic_consume($queueName, '', false, true, false, false, $callback);

        try {
            $channel->consume();
        } catch (\Throwable $exception) {
            echo $exception->getMessage();
        }

        return Command::SUCCESS;
    }
}
