# Symfony Mailer for Microsoft

Use this bridge to send Emails with `symfony/mailer` and Microsoft Graph API. Supported Features:



| Field       | Supported |
|-------------|-----------|
| Subject     | ✓         |
| Return-Path | ✕         |
| Sender      | ✓         |
| From        | ✕         |
| Reply-To    | ✕         |
| To          | ✓         |
| Cc          | ✓         |
| Bcc         | ✓         |
| Priority    | ✕         |
| Text        | ✓         |
| HTML        | ✕         |
| Attachments | ✓         |


## Configuration

Extend your `services.yaml`configuration

      PMDevelopment\Mailer\Bridge\Microsoft\Transport\GraphTransportFactory:
        tags:
          - { name: mailer.transport_factory }


Add required Parameters to `.env`

    MAILER_DSN=microsoft+graph://clientId:clientSecret@tenantId


