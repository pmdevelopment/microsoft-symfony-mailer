<?php

namespace PMDevelopment\Mailer\Bridge\Microsoft\Transport;

use Microsoft\Graph\Graph;
use Symfony\Component\Mailer\Transport\Dsn;

class GraphClient
{
    private Graph $graph;

    private string $clientId;
    private string $clientSecret;
    private string $tenantId;

    public function __construct(string $accessToken, Dsn $dsn)
    {
        $this->graph = new Graph();
        $this->graph->setAccessToken($accessToken);

        $this->clientId = $dsn->getUser();
        $this->clientSecret = $dsn->getPassword();
        $this->tenantId = $dsn->getHost();
    }

    public function getGraph(): Graph
    {
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

}