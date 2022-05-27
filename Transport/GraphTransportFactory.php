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
        $client = new Client();

        $loginUrl = sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $dsn->getHost());

        $loginFormParameters = [
            'client_id'     => $dsn->getUser(),
            'client_secret' => $dsn->getPassword(),
            'grant_type'    => 'client_credentials',
            'scope'         => 'https://graph.microsoft.com/.default',
        ];

        $request = $client->post($loginUrl, [
            'form_params' => $loginFormParameters,
        ]);

        $response = json_decode($request->getBody()->getContents(), true);
        if (false === array_key_exists('access_token', $response)) {
            throw new RuntimeException('Key "access_token" not found in %s', implode(',', array_keys($response)));
        }

        return new GraphSendMailTransport(new GraphClient($response['access_token'], $dsn), $this->dispatcher, $this->logger);
    }


    protected function getSupportedSchemes(): array
    {
        return ['microsoft+graph'];
    }


}