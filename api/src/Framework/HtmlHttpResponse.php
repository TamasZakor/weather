<?php

declare(strict_types=1);

namespace ADS\TakeHome\Framework;

/**
 * HTML formátumú HTTP válasz (pl. Swagger UI).
 */
class HtmlHttpResponse implements HttpResponseInterface
{
    /**
     * @param string $html A válasz HTML tartalma.
     * @param int $status HTTP státusz kód.
     * @param array<string, string> $headers Egyedi fejlécek.
     */
    public function __construct(
        private readonly string $html,
        private readonly int $status = 200,
        private readonly array $headers = [],
    ) {
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getBody(): string|null
    {
        return $this->html;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return array_merge([
            "Content-Type" => "text/html; charset=utf-8",
        ], $this->headers);
    }
}
