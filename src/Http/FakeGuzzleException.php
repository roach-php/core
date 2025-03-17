<?php

namespace RoachPHP\Http;

use Exception;
use GuzzleHttp\Exception\GuzzleException;

class FakeGuzzleException extends Exception implements GuzzleException
{
}
