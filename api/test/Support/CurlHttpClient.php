<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests\Support;

/**
 * Könnyűsúlyú HTTP kliens PHP curl extension alapján.
 * Guzzle alternatíva, ha az iconv extension nem elérhető a konténerben.
 */
class CurlHttpClient
{
    public function __construct(
        private readonly string $baseUrl,
    ) {
    }

    /**
     * @param array<string, string> $query
     * @return array{status: int, body: string, headers: array<string, string>}
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request("GET", $path, $query);
    }

    /**
     * @param array<string, string> $query
     * @return array{status: int, body: string, headers: array<string, string>}
     */
    public function request(string $method, string $path, array $query = []): array
    {
        $url = $this->baseUrl . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init($url);
        assert($ch !== false);

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $raw      = (string) curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headerRaw = substr($raw, 0, $headerSize);
        $body      = substr($raw, $headerSize);

        $headers = [];
        foreach (explode("\r\n", $headerRaw) as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[trim($name)] = trim($value);
            }
        }

        return ['status' => $status, 'body' => $body, 'headers' => $headers];
    }
}
