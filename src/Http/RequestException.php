<?php declare(strict_types=1);

namespace RoachPHP\Http;

use Exception;
use GuzzleHttp\Exception\GuzzleException;

final class RequestException extends Exception
{
    public function __construct(
        private Request $request,
        private GuzzleException $reason
    ) {
        parent::__construct('An exception occurred while sending a request', previous: $reason);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getReason(): GuzzleException
    {
        return $this->reason;
    }
}
