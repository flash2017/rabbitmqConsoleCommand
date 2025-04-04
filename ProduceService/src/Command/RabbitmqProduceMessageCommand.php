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
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'rabbitmq:produce:message',
    description: 'Add a short description for your command',
)]
class RabbitmqProduceMessageCommand extends Command
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
            ->addArgument('body', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $body = $input->getArgument('body');

        if ($input->getOption('option1')) {
            // ...
        }

        $jsonContent = $this->serializer->serialize($body, 'json');

        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $channel->exchange_declare('symfony_fanout', 'fanout', false, true, false);
        $channel->queue_declare('symfony_fanout', false, false, false, false);
        $channel->queue_declare('symfony_fanout2', false, false, false, false);
        $channel->queue_bind('symfony_fanout', 'symfony_fanout');
        $channel->queue_bind('symfony_fanout2', 'symfony_fanout');

        $msg = new AMQPMessage($jsonContent);
        $channel->basic_publish($msg, 'symfony_fanout');

        $io->note(sprintf( '[x] Sent "%s"', $jsonContent));

        $channel->close();
        $connection->close();

        return Command::SUCCESS;
    }
}
