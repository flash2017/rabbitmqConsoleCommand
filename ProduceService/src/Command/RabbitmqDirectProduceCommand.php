<?php

namespace App\Command;

use App\Services\Amqp\Service;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'rabbitmq:direct:produce',
    description: 'публикация сообщения в symfony_direct',
)]
class RabbitmqDirectProduceCommand extends Command
{
    public function __construct(private Service $amqpService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('message', InputArgument::REQUIRED, 'сообщение');
        $this->addArgument('routingKey', InputArgument::REQUIRED, 'routingKey');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $message = (string) $input->getArgument('message');
        $routingKey = (string) $input->getArgument('routingKey');

        if ($routingKey) {
            $io->note(sprintf('You passed an argument: %s', $routingKey));
        }

        $connection = $this->amqpService->getConnection();
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
