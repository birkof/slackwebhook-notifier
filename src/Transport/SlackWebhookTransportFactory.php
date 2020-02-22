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

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

/**
 * @author       Daniel Stancu <birkof@birkof.ro>
 *
 * @experimental in 5.0
 */
final class SlackWebhookTransportFactory extends AbstractTransportFactory
{
    const SCHEME = 'slackwebhook';

    /**
     * @param Dsn $dsn
     *
     * @return TransportInterface
     */
    public function create(Dsn $dsn): TransportInterface
    {
        $channel = sprintf('#%s', $dsn->getOption('channel'));
        $username = $dsn->getOption('username');
        $userIcon = $dsn->getOption('user_icon');
        $host = 'default' === $dsn->getHost() ? null : $dsn->getHost();
        $webhookPath = ltrim($dsn->getPath(), '/');

        if (self::SCHEME === $dsn->getScheme()) {
            return (new SlackWebhookTransport($webhookPath, $channel, $username, $userIcon, $this->client, $this->dispatcher))
                ->setHost($host);
        }

        throw new UnsupportedSchemeException($dsn, self::SCHEME, $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return [self::SCHEME];
    }
}
