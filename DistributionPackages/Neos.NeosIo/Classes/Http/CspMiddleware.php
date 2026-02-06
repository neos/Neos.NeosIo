<?php

declare(strict_types=1);

namespace Neos\NeosIo\Http;

use GuzzleHttp\Psr7\Response;
use Neos\Flow\Annotations as Flow;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class CspMiddleware implements MiddlewareInterface
{
    private const REPORT_URI_PATH = '/__csp-report';
    private const REPORT_MAX_PAYLOAD_SIZE = 4 * 1024;
    #[Flow\Inject]
    protected LoggerInterface $logger;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        if ($request->getUri()->getPath() === self::REPORT_URI_PATH) {
            return $this->processReportRequest($request);
        }
        return $next->handle($request);
    }

    private function processReportRequest(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            throw new \RuntimeException(sprintf('CSP Violation Report: Invalid request method: %s. Only POST requests are logged.', $request->getMethod()), 1549474764);
        }

        $rawPayload = $request->getBody()->getContents();
        if ($rawPayload === '') {
            throw new \RuntimeException(sprintf('CSP Violation Report: Payload size must not be empty.'), 1549477541);
        }
        if (strlen($rawPayload) > self::REPORT_MAX_PAYLOAD_SIZE) {
            throw new \RuntimeException(sprintf('CSP Violation Report: Payload size must not exceed %u bytes. Got %u bytes, starting with: %s', self::REPORT_MAX_PAYLOAD_SIZE, $request->getBody()->getSize(), substr($rawPayload, 100)), 1549474772);
        }

        try {
            $decodedPayload = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('CSP Violation Report: Could not parse violation report "%s...". JSON error: %s', substr($rawPayload, 100), $e->getMessage()), 1677010199, $e);
        }
        if (!is_array($decodedPayload)) {
            throw new \RuntimeException(sprintf('CSP Violation Report: Invalid payload, expected JSON object but got "%s...".', substr($rawPayload, 100)), 1549474776);
        }

        $reportPayload = $decodedPayload['csp-report'] ?? [];
        $errorMessage = sprintf(
            'CSP Violation: %s blocked %s on %s',
            $reportPayload['violated-directive'] ?? '?',
            $reportPayload['blocked-uri'] ?? '?',
            $reportPayload['document-uri'] ?? '?'
        );
        $this->logger->error($errorMessage, $reportPayload);
        return new Response(204);
    }
}
