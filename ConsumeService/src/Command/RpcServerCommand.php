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
use Throwable;

#[AsCommand(
    name: 'rpc:server',
    description: 'Add a short description for your command',
)]
class RpcServerCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('queueName', InputArgument::REQUIRED, 'queue name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $queueName = $input->getArgument('queueName');

        $Connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $Channel = $Connection->channel();
        $Channel->queue_declare($queueName, false, true, false, false);

        function fbn (int $n) {
            if ($n === 0 || $n == 1) {
                return $n;
            }

            return fbn($n-1) + fbn($n-2);
        }

        $io->note('awaiting RPC request...');

        $callback = function (AMQPMessage $request) use ($io) {
            $n = intval($request->getBody());

            $io->note(sprintf('fib(%s)', $n));
            $msg = new AMQPMessage(
                fbn($n),
                ['correlation_id' => $request->get('correlation_id')]
            );/
            $request->getChannel()->basic_publish($msg, '', $request->get('reply_to'));
            $request->ack();

            $io->note(sprintf('ack cor_id %s fib(%s)', $request->get('correlation_id'), $n));
        };

        $Channel->basic_qos(null, 1, false);
        $Channel->basic_consume($queueName, '', false, false, false, false, $callback);

        try {
            $Channel->consume();
            $code = Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error($e);
            $code = Command::FAILURE;
        }

        return $code;
    }
}
