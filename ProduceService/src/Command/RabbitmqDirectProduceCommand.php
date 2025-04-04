<?php

namespace App\Command;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rabbitmq:direct:produce',
    description: 'Add a short description for your command',
)]
class RabbitmqDirectProduceCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('message', InputArgument::REQUIRED, 'Argument description');
        $this->addArgument('routingKey', InputArgument::REQUIRED, 'Argument description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $message = (string) $input->getArgument('message');
        $routingKey = (string) $input->getArgument('routingKey');

        if ($routingKey) {
            $io->note(sprintf('You passed an argument: %s', $routingKey));
        }

        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->basic_publish(new AMQPMessage(
            $message,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]),
            'symfony_direct',
            $routingKey
        );

        $channel->close();
        $connection->close();
        $io->success(
            sprintf('You add a new message "%s" with roting key "%s" to symfony_direct exchange', $message,  $routingKey)
        );

        return Command::SUCCESS;
    }
}
