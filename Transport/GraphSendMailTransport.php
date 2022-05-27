<?php

namespace PMDevelopment\Mailer\Bridge\Microsoft\Transport;

use GuzzleHttp\Client;
use Microsoft\Graph\Graph;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\RuntimeException as MailerRuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

class GraphSendMailTransport extends AbstractTransport
{
    private GraphClient $client;

    public function __construct(GraphClient $client, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->client = $client;

        parent::__construct($dispatcher, $logger);
    }


    protected function doSend(SentMessage $message): void
    {
        $graph = $this->client->getGraph();

        try {
            $email = MessageConverter::toEmail($message->getOriginalMessage());
        } catch (Throwable $e) {
            throw new MailerRuntimeException(sprintf('Unable to send message with the "%s" transport: ', __CLASS__) . $e->getMessage(), 0, $e);
        }

        $mailBody = [
            'Message' => [
                'attachments'   => [],
                'bccRecipients' => $this->getArrayFromAddresses($email->getBcc()),
                'body'          => [
                    'contentType' => 'text',
                    'content'     => $email->getTextBody(),
                ],
                'ccRecipients'  => $this->getArrayFromAddresses($email->getCc()),
                'subject'       => $email->getSubject(),
                'toRecipients'  => $this->getArrayFromAddresses($email->getTo()),
            ],
        ];

        foreach ($email->getAttachments() as $attachment) {
            $mailBody['Message']['attachments'][] = [
                '@odata.type'  => '#microsoft.graph.fileAttachment',
                'contentType'  => 'text/plain',
                'contentBytes' => base64_encode($attachment->getBody()),
                'name'         => $attachment->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'name'),
            ];
        }


        $result = $graph->createRequest(Request::METHOD_POST, sprintf('/users/%s/sendMail', $message->getEnvelope()->getSender()->getAddress()))
            ->attachBody($mailBody)
            ->execute();

        if (Response::HTTP_ACCEPTED !== $result->getStatus()) {
            throw new MailerRuntimeException(sprintf('Sending mail failed with status %d', $result->getStatus()));
        }
    }

    public function __toString()
    {
        return sprintf('microsoft+graph://%s:%s@%s', $this->client->getClientId(), $this->client->getClientSecret(), $this->client->getTenantId());
    }

    private function getArrayFromAddresses(array $addresses)
    {
        return array_map(function (Address $address) {
            return [
                'emailAddress' => [
                    'address' => $address->getAddress(),
                ],
            ];
        }, $addresses);
    }
}