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
use Throwable;

#[AsCommand(
    name: 'rabbitmq:consume:logs',
    description: 'Add a short description for your command',
)]
class RabbitmqConsumeLogsCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('exchange', InputArgument::REQUIRED, 'exchange name');
        $this->addOption('binding_keys', 'bk' ,InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'binding keys delimeter words ".", special chars "#" as any words and delimetrs', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $exchange = $input->getArgument('exchange');
        $bindingKeys = $input->getOption('binding_keys');
        $Connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $Channel = $Connection->channel();
        list($queueName,,) = $Channel->queue_declare('', false, false, true, false);

        foreach ($bindingKeys as $key) {
            $Channel->queue_bind($queueName, $exchange, $key);
        }

        $callback = fn (AMQPMessage $msg) => $io->success($msg->getRoutingKey()." ".$msg->getBody());
        $Channel->basic_consume(
            $queueName,
            '',
            false,
            true,
            false,
            false,
            $callback
        );

        $io->note(sprintf('You start consume with exchange: [%s] queue name [%s] with binding keys [%s]', $exchange, $queueName, implode('|', $bindingKeys)));

        $io->success('Waiting for logs. To exit press CTRL+C\n');

        try {
            $Channel->consume();
        } catch (Throwable $e) {
            $io->error($e->getMessage());
        }

        $Channel->close();
        $Connection->close();

        return Command::SUCCESS;
    }
}
