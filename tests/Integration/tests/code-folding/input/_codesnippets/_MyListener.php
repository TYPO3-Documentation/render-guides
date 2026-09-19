<?php

declare(strict_types=1);

/*
 * This file is part of the "my_extension" extension.
 */

namespace MyVendor\MyExtension\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Mail\Event\AfterMailerSentMessageEvent;

/**
 * Logs every mail that was sent.
 */
#[AsEventListener]
final readonly class MyListener
{
    public function __invoke(AfterMailerSentMessageEvent $event): void
    {
        /* A comment
           spanning lines */
        $message = $event->getMailer()->getSentMessage();
    }
}
