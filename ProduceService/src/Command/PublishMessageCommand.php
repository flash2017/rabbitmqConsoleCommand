<?php

namespace App\Command;

use App\Services\Amqp\Service;
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
    name: 'publish:message',
    description: 'публикация сообщений',
)]
class PublishMessageCommand extends Command
{
    public function __construct(private Service $amqpService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('exchange', InputArgument::REQUIRED, 'exchange name')
            ->addArgument('message', InputArgument::REQUIRED, 'message data')
            ->addArgument('routingKey', InputArgument::OPTIONAL, 'routing key')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $message = $input->getArgument('message');
        $exchange = $input->getArgument('exchange');
        $routingKey = $input->getArgument('routingKey');

        $dataCommand = sprintf('You publish message [%s] to exchange [%s] rotingKey [%s]', $message, $exchange, $routingKey ?? '');
        $io->note($dataCommand);


        $amqpMessage = new AMQPMessage($message);
        $Channel = $this->amqpService->getConnection()->channel();
        $Channel->basic_publish($amqpMessage, $exchange, $routingKey);

        $Channel->close();
        $this->amqpService->getConnection()->close();

        $io->success('You published message success! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
