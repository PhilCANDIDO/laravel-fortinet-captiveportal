<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class FortiGateApiException extends Exception
{
    protected array $context = [];
    protected ?string $apiEndpoint = null;
    protected ?int $httpStatusCode = null;
    protected ?array $apiResponse = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function setApiEndpoint(string $endpoint): self
    {
        $this->apiEndpoint = $endpoint;
        return $this;
    }

    public function setHttpStatusCode(int $statusCode): self
    {
        $this->httpStatusCode = $statusCode;
        return $this;
    }

    public function setApiResponse(?array $response): self
    {
        $this->apiResponse = $response;
        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getApiEndpoint(): ?string
    {
        return $this->apiEndpoint;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    public function getApiResponse(): ?array
    {
        return $this->apiResponse;
    }

    public function report(): void
    {
        logger()->error('FortiGate API Error', [
            'message' => $this->getMessage(),
            'endpoint' => $this->apiEndpoint,
            'http_status' => $this->httpStatusCode,
            'response' => $this->apiResponse,
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }

    public function render($request): JsonResponse
    {
        $message = match ($this->httpStatusCode) {
            401 => __('fortigate.errors.unauthorized'),
            403 => __('fortigate.errors.forbidden'),
            404 => __('fortigate.errors.not_found'),
            429 => __('fortigate.errors.rate_limited'),
            500, 502, 503, 504 => __('fortigate.errors.server_error'),
            default => __('fortigate.errors.general'),
        };

        return response()->json([
            'error' => true,
            'message' => $message,
            'details' => config('app.debug') ? $this->getMessage() : null,
        ], 500);
    }
}