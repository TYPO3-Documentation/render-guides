<?php

declare(strict_types=1);

namespace T3Docs\Typo3DocsTheme\Integration\Http;

use Symfony\Component\HttpClient\Response\MockResponse;

/** Answers the HTTP client of guides from the recorded responses. */
final class RecordedHttpResponder
{
    public function __construct(
        private readonly RecordedResponses $responses,
    ) {}

    public function __invoke(string $method, string $url): MockResponse
    {
        $body = ($this->responses)($url);

        return $body === null
            ? new MockResponse('', ['http_code' => 404])
            : new MockResponse($body, ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]);
    }
}
