<?php

namespace PMDevelopment\Mailer\Bridge\Microsoft\Transport;

use GuzzleHttp\Psr7\Utils;
use Microsoft\Graph\Generated\Models\BodyType;
use Microsoft\Graph\Generated\Models\EmailAddress;
use Microsoft\Graph\Generated\Models\FileAttachment;
use Microsoft\Graph\Generated\Models\ItemBody;
use Microsoft\Graph\Generated\Models\Message;
use Microsoft\Graph\Generated\Models\Recipient;
use Microsoft\Graph\Generated\Users\Item\SendMail\SendMailPostRequestBody;
use Microsoft\Kiota\Abstractions\ApiException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\RuntimeException as MailerRuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Message as MimeMessage;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Part\DataPart;
use Throwable;

class GraphSendMailTransport extends AbstractTransport
{
    public function __construct(private readonly GraphClient $client, ?EventDispatcherInterface $dispatcher = null, ?LoggerInterface $logger = null)
    {
        parent::__construct($dispatcher, $logger);
    }

    protected function doSend(SentMessage $message): void
    {
        $graph = $this->client->getGraph();

        try {
            $originalMessage = $message->getOriginalMessage();
            if (false === ($originalMessage instanceof MimeMessage)) {
                throw new MailerRuntimeException(sprintf('Expected Message to be instance of "%s", got "%s"', MimeMessage::class, get_class($originalMessage)));
            }

            $email = MessageConverter::toEmail($originalMessage);
        } catch (Throwable $e) {
            throw new MailerRuntimeException(sprintf('Unable to send message with the "%s" transport: ', __CLASS__) . $e->getMessage(), 0, $e);
        }

        $graphMessage = new Message();

        $eMailAddressSender = new EmailAddress();
        $eMailAddressSender->setAddress($message->getEnvelope()->getSender()->getAddress());

        $fromRecipient = new Recipient();
        $fromRecipient->setEmailAddress($eMailAddressSender);

        $graphMessage->setFrom($fromRecipient);

        $recipientMapping = [
            'getTo' => 'setToRecipients',
            'getCc' => 'setCcRecipients',
            'getBcc' => 'setBccRecipients',
        ];

        foreach ($recipientMapping as $recipientGetter => $recipientSetter) {
            $recipients = [];

            foreach ($email->$recipientGetter() as $address) {
                if (false === ($address instanceof Address)) {
                    continue;
                }

                $eMailAddress = new EmailAddress();
                $eMailAddress->setAddress($address->getAddress());
                $eMailAddress->setName($address->getName());

                $recipient = new Recipient();
                $recipient->setEmailAddress($eMailAddress);

                $recipients[] = $recipient;
            }

            if (0 < count($recipients)) {
                $graphMessage->$recipientSetter($recipients);
            }
        }

        $attachments = [];
        /** @var DataPart $attachmentFromEmail */
        foreach ($email->getAttachments() as $attachmentFromEmail) {
            $attachmentGraph = new FileAttachment();
            $attachmentGraph->setName($attachmentFromEmail->getPreparedHeaders()->getHeaderParameter('Content-Disposition', 'name'));
            $attachmentGraph->setContentBytes(Utils::streamFor(base64_encode($attachmentFromEmail->getBody())));
            $attachmentGraph->setContentType($attachmentFromEmail->getContentType());

            $attachments[] = $attachmentGraph;
        }

        $emailBody = new ItemBody();
        $emailBody->setContent($email->getTextBody());
        $emailBody->setContentType(new BodyType(BodyType::TEXT));

        $graphMessage->setSubject($email->getSubject());
        $graphMessage->setBody($emailBody);
        $graphMessage->setAttachments($attachments);

        $requestBody = new SendMailPostRequestBody();
        $requestBody->setMessage($graphMessage);

        try {
            $graph->users()->byUserId($eMailAddressSender->getAddress())->sendMail()->post($requestBody)->wait();
        } catch (ApiException $exception) {
            throw new MailerRuntimeException(sprintf('Sending mail failed with exception "%s"', $exception->getMessage()), $exception->getResponseStatusCode(), $exception);
        }
    }

    public function __toString()
    {
        return sprintf('microsoft+graph://%s:%s@%s', $this->client->getClientId(), $this->client->getClientSecret(), $this->client->getTenantId());
    }
}
