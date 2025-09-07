<?php

namespace App\Command;

use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;
use App\Services\Amqp\Service;

#[AsCommand(
    name: 'rabbitmq:produce:message',
    description: 'тест fanout ',
)]
class RabbitmqProduceMessageCommand extends Command
{
    public function __construct(private SerializerInterface $serializer, private Service $amqpService)
    {
        parent::__construct();

    }

    protected function configure(): void
    {
        $this
            ->addArgument('body', InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'сообщение')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $body = $input->getArgument('body');

        $jsonContent = $this->serializer->serialize($body, 'json');

        $connection = $this->amqpService->getConnection();
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
