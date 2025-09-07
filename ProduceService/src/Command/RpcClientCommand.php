<?php

namespace App\Command;


use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Services\Amqp\Service;

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

    /**
     * @param Service $amqpService
     */
    public function __construct(private Service $amqpService)
    {
        parent::__construct();

        $this->Connection = $this->amqpService->getConnection();
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

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('n', InputArgument::REQUIRED, 'number for fibonachi sum function');
    }

    /**
     * @param AMQPMessage $response
     * @return void
     */
    public function onResponse(AMQPMessage $response): void
    {
        if ($response->get('correlation_id') === $this->correlationId) {
            $this->response = $response->getBody();
        }
    }

    /**
     * @param int $n
     * @return int
     */
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
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
