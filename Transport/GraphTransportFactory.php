<?php

namespace PMDevelopment\Mailer\Bridge\Microsoft\Transport;

use GuzzleHttp\Client;
use Microsoft\Graph\Graph;
use RuntimeException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\IncompleteDsnException;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class GraphTransportFactory extends AbstractTransportFactory
{
    public function __construct(EventDispatcherInterface $eventDispatcher, LoggerInterface $logger = null)
    {
        parent::__construct($eventDispatcher, null, $logger);
    }


    public function create(Dsn $dsn): TransportInterface
    {
        return new GraphSendMailTransport(new GraphClient($dsn), $this->dispatcher, $this->logger);
    }


    protected function getSupportedSchemes(): array
    {
        return ['microsoft+graph'];
    }


}