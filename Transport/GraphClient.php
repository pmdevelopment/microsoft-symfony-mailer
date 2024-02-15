<?php

namespace PMDevelopment\Mailer\Bridge\Microsoft\Transport;

use GuzzleHttp\Client;
use Microsoft\Graph\Graph;
use Symfony\Component\Mailer\Transport\Dsn;

class GraphClient
{
    private ?Graph $graph = null;

    private string $clientId;
    private string $clientSecret;
    private string $tenantId;

    public function __construct(Dsn $dsn)
    {
        $this->clientId = $dsn->getUser();
        $this->clientSecret = $dsn->getPassword();
        $this->tenantId = $dsn->getHost();
    }

    public function getGraph(): Graph
    {
        if (null === $this->graph) {
            $this->login();
        }

        return $this->graph;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    private function login()
    {
        $client = new Client();

        $loginUrl = sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', $this->tenantId);

        $loginFormParameters = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
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

        $this->graph = new Graph();
        $this->graph->setAccessToken($response['access_token']);
    }

}