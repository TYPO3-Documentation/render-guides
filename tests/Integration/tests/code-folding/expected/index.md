---
title: "Code folding"
manual: "Table of contents"
version: "13.4"
permalink: "https://docs.typo3.org/permalink/t3coreapi:code-folding@13.4"
source: "index.rst"
start: true
rendered: "2023-01-01T12:00:00+00:00"
---

# Code folding {#code-folding}

The header of a PHP file is folded:

**EXT:my_extension/Classes/EventListener/MyListener.php**

```php
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

```

Only the lines an author names are shown; a comment spans the fold:

**EXT:my_extension/Classes/EventListener/MyListener.php**

```php
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

```

Folding switched off:

```php
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

```

An invalid value is reported and leaves the default folding:

```php
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

```

A file with no header worth folding:

```php
<?php

return ['key' => 'value'];

```

A code-block takes the option too:

```yaml
services:
  MyVendor\MyExtension\:
    resource: '../Classes/*'
```

With line numbers and emphasized lines:

```php
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

```
