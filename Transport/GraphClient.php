<?php

namespace PMDevelopment\Mailer\Bridge\Microsoft\Transport;

use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Symfony\Component\Mailer\Transport\Dsn;

class GraphClient
{
    private ?GraphServiceClient $graph = null;

    private string $clientId;
    private string $clientSecret;
    private string $tenantId;

    public function __construct(Dsn $dsn)
    {
        $this->clientId = $dsn->getUser();
        $this->clientSecret = $dsn->getPassword();
        $this->tenantId = $dsn->getHost();
    }

    public function getGraph(): GraphServiceClient
    {
        if (null === $this->graph) {
            $tokenRequestContext = new ClientCredentialContext(
                $this->tenantId,
                $this->clientId,
                $this->clientSecret,
            );

            $this->graph = new GraphServiceClient($tokenRequestContext);
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
}
