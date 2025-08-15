<?php

namespace App\Exceptions;

use Exception;

class FortiGateConnectionException extends FortiGateApiException
{
    protected bool $isTimeout = false;
    protected bool $isNetworkError = false;
    protected ?float $responseTime = null;

    public function setIsTimeout(bool $isTimeout): self
    {
        $this->isTimeout = $isTimeout;
        return $this;
    }

    public function setIsNetworkError(bool $isNetworkError): self
    {
        $this->isNetworkError = $isNetworkError;
        return $this;
    }

    public function setResponseTime(?float $responseTime): self
    {
        $this->responseTime = $responseTime;
        return $this;
    }

    public function isTimeout(): bool
    {
        return $this->isTimeout;
    }

    public function isNetworkError(): bool
    {
        return $this->isNetworkError;
    }

    public function getResponseTime(): ?float
    {
        return $this->responseTime;
    }

    public function report(): void
    {
        logger()->critical('FortiGate Connection Error', [
            'message' => $this->getMessage(),
            'endpoint' => $this->apiEndpoint,
            'is_timeout' => $this->isTimeout,
            'is_network_error' => $this->isNetworkError,
            'response_time' => $this->responseTime,
            'context' => $this->context,
            'trace' => $this->getTraceAsString(),
        ]);
    }

    public function render($request): \Illuminate\Http\JsonResponse
    {
        $message = $this->isTimeout 
            ? __('fortigate.errors.timeout')
            : __('fortigate.errors.connection_failed');

        return response()->json([
            'error' => true,
            'message' => $message,
            'retry_available' => true,
        ], 503);
    }
}