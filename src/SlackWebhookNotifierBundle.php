<?php
/*
 * This file is part of the SlackWebhookNotifier.
 *
 * (c) Daniel STANCU <birkof@birkof.ro>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace birkof\SlackWebhookNotifier;

use birkof\SlackWebhookNotifier\DependencyInjection\SlackWebhookNotifierExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class SlackWebhookNotifierBundle
 * @package birkof\SlackWebhookNotifier
 */
class SlackWebhookNotifierBundle extends Bundle
{
    const VERSION = '5.0.0';

    public function getContainerExtension()
    {
        return new SlackWebhookNotifierExtension();
    }
}
