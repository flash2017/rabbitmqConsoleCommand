<?php

namespace App\Services\Amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class Service
{
     private ConnectionsPool $connectionsPool;

    public function __construct(private ContainerBagInterface $containerBag)
    {
        $this->connectionsPool = new ConnectionsPool();
    }

    /**
     * @param string|null $host
     * @param int|null $port
     * @param string|null $username
     * @param string|null $password
     * @return AMQPStreamConnection
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getConnection(?string $host = null, ?int $port = null, ?string $username = null, ?string $password = null): AMQPStreamConnection
    {
        return $this->connectionsPool->get(
            $host ?? $this->containerBag->get('app.rabbit_host'),
            $port ?? $this->containerBag->get('app.rabbit_port'),
            $username ?? $this->containerBag->get('app.rabbit_user'),
            $password ?? $this->containerBag->get('app.rabbit_pass')
        );
    }
}