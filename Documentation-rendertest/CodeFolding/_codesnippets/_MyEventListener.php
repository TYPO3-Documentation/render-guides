<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 */

namespace MyVendor\MyExtension\EventListener;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Mail\Event\AfterMailerSentMessageEvent;
use TYPO3\CMS\Core\Mail\Mailer;

/**
 * Logs the recipients of every mail TYPO3 has sent.
 */
#[AsEventListener(identifier: 'my-extension/log-sent-mail')]
final readonly class MyEventListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(AfterMailerSentMessageEvent $event): void
    {
        $mailer = $event->getMailer();
        if (!$mailer instanceof Mailer) {
            return;
        }

        $sentMessage = $mailer->getSentMessage();
        $this->logger->info('Mail sent', [
            'recipients' => $sentMessage?->getEnvelope()->getRecipients(),
        ]);
    }
}
