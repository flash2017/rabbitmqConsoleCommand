<?php

namespace App\Command;

use AMQPConnection;
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
    name: 'rpc:client',
    description: 'Add a short description for your command',
)]
class RpcClientCommand extends Command
{
    private $Connection;
    private $Channel;
    private $callbackQueue;
    private $response;
    private $correlationId;
    public function __construct()
    {
        parent::__construct();

        $this->Connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $this->Channel = $this->Connection->channel();
        list($this->callbackQueue,,) = $this->Channel->queue_declare('', false, false, true, false);
        $this->Channel->basic_consume($this->callbackQueue,
            '',
            false,
            true,
            false,
            false,
            [$this, 'onResponse']);
    }

    protected function configure(): void
    {
        $this->addArgument('n', InputArgument::REQUIRED, 'number for fibonachi sum function');
    }

    public function onResponse(AMQPMessage $response): void
    {
        if ($response->get('correlation_id') === $this->correlationId) {
            $this->response = $response->getBody();
        }
    }

    public function call(int $n)
    {

        $this->response = null;
        $this->correlationId = uniqid();

        $msg = new AMQPMessage(
            $n,
            ['correlation_id' => $this->correlationId, 'reply_to'=>$this->callbackQueue]
        );

        $this->Channel->basic_publish($msg, 'rpc_exchange', 'rpc_queue');
        while(!$this->response) {
            $this->Channel->wait();
        }

        return intval($this->response);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('n');

        $io->note(sprintf('You passed an argument: %s', $arg1));

        $result = $this->call($arg1);

        $io->success('You have a new fibonachi sum: ' . $result);

        return Command::SUCCESS;
    }
}
