<?php

namespace App\Services\Amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class ConnectionsPool
{
    /**
     * @var AMQPStreamConnection[]
     */
    private static array $connections = [];


    /**
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @return AMQPStreamConnection
     * @throws \Exception
     */
    public function get( string $host = 'localhost', int $port = 5672, string $username = 'guest', string $password = 'guest'):AMQPStreamConnection
    {
        $hash = md5($host. ':' .$port. ':' .$username. ':' .$password);

        if (!isset(self::$connections[$hash])) {
            static::$connections[$hash] = new AMQPStreamConnection($host, $port, $username, $password);
        }

        return self::$connections[$hash];
    }


}