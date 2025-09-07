<?php

namespace App\Command;

use App\Services\Amqp\Service;
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
    description: 'добавить в обменник сообщение fanout',
)]
class RabbitmqFanoutMessageCommand extends Command
{
    public function __construct(private Service $amqpService)
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
        $Connection = $this->amqpService->getConnection();
        $Channel = $Connection->channel();

        $Channel->basic_publish($msgAmqp, $exchange);

        $Channel->close();
        $Connection->close();

        $success = sprintf('You have a publish message "%s" to %s! Pass --help to see your options.', $message, $exchange);
        $io->success($success);

        return Command::SUCCESS;
    }
}
