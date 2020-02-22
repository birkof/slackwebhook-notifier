<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace birkof\SlackWebhookNotifier\Transport;

use Monolog\Handler\Slack\SlackRecord;
use Monolog\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author       Daniel Stancu <birkof@birkof.ro>
 *
 * @internal
 * @experimental in 5.0
 */
final class SlackWebhookTransport extends AbstractTransport
{
    protected const HOST = 'hooks.slack.com';

    protected $client;
    private $webhookPath;
    private $channel;
    private $username;
    private $userIcon;

    public function __construct(
        string $webhookPath,
        string $channel,
        string $username,
        string $userIcon,
        HttpClientInterface $client = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        $this->client = $client;
        $this->webhookPath = $webhookPath;
        $this->channel = $channel;
        $this->username = $username;
        $this->userIcon = $userIcon;

        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s://%s/%s?channel=%s&username=%s&user_icon=%s&',
            SlackWebhookTransportFactory::SCHEME,
            $this->getEndpoint(),
            $this->webhookPath,
            $this->channel,
            $this->username,
            $this->userIcon
        );
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof ChatMessage
            && (null === $message->getOptions() || $message->getOptions() instanceof SlackOptions);
    }

    /**
     * Sending messages using Incoming Webhooks
     * @see https://api.slack.com/messaging/webhooks
     */
    protected function doSend(MessageInterface $message): void
    {
        if (!$message instanceof ChatMessage) {
            throw new LogicException(
                sprintf(
                    'The "%s" transport only supports instances of "%s" (instance of "%s" given).',
                    __CLASS__,
                    ChatMessage::class,
                    \get_class($message)
                )
            );
        }

        if ($message->getOptions() && !$message->getOptions() instanceof SlackOptions) {
            throw new LogicException(
                sprintf(
                    'The "%s" transport only supports instances of "%s" for options.',
                    __CLASS__,
                    SlackOptions::class
                )
            );
        }

        if (!($opts = $message->getOptions()) && $notification = $message->getNotification()) {
            $opts = SlackOptions::fromNotification($notification);
        }

        $options = $opts ? $opts->toArray() : [];

        $slackRecord = new SlackRecord(
            $options['channel'] ?? $this->channel,
            $options['username'] ?? $this->username,
            $options['use_attachment'] ?? false,
            $options['user_icon'] ?? $this->userIcon,
            false,
            $options['include_context_and_extra'] ?? false
        );

        $postData = $slackRecord->getSlackData(
            [
                'level'      => $options['level'] ?? 0,
                'level_name' => $options['level_name'] ?? null,
                'level_name' => $options['level_name'] ?? null,
                'extra'      => $options['extra'] ?? null,
                'context'    => $options['context'] ?? null,
                'datetime'   => new \DateTime(),
                'message'    => $message->getSubject(),
            ]
        );

        // We keep the message on top, even with attachment
        if (!empty($options['text'])) {
            $postData['text'] = $options['text'];
        }

        // Make the request on Slack
        $response = $this->client->request(
            Request::METHOD_POST,
            sprintf(
                '%s://%s/%s',
                'https',
                $this->getEndpoint(),
                $this->webhookPath
            ),
            [
                'body' => Utils::jsonEncode($postData),
            ]
        );

        if (200 !== $response->getStatusCode()) {
            throw new TransportException(
                sprintf('Unable to post to Slack Webhook: %s.', $response->getContent(false)),
                $response
            );
        }
    }
}
