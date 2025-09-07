ProduceService command
- declare:exchange [options] [--] <name> <type>
- publish:message <exchange> <message> [<routingKey>]
- rabbitmq:direct:produce <message> <routingKey>
- rabbitmq:fanout:message <exchange> [<message>]
-  rabbitmq:produce:message [options] [--] <body>...
-  rabbitmq:topic:produce <routingKey> <message>

ConsumeService command
- rabbitmq:consume:logs  
- rabbitmq:consume:message
- rabbitmq:direct:consume 
- rabbitmq:topic:consume
- rpc:server

Сервер c rabbitmq поставляется отдельно

