<?php

namespace BraintreeHttp\Serializer;

use BraintreeHttp\HttpRequest;
use BraintreeHttp\Serializer;

class CurlSupported implements Serializer
{
    /**
     * @return string Regex that matches the content type it supports.
     */
    public function contentType()
    {
        return "/^multipart\/.*$/";
    }

    /**
     * @param HttpRequest $request
     * @return string representation of your data after being serialized.
     *
     * Curl will automatically encode these types. In these cases, just the raw array
     * is returned.
     */
    public function encode(HttpRequest $request)
    {
        if (!is_array($request->body) || !$this->isAssociative($request->body))
        {
            throw new \Exception("HttpRequest body must be an associative array when Content-Type is: " . $request->headers["Content-Type"]);
        }

        return $request->body;
    }

    /**
     * @param $body
     * @return mixed
     * @throws \Exception as multipart does not support deserialization.
     */
    public function decode($body)
    {
        throw new \Exception("CurlSupported does not support deserialization");
    }

    private function isAssociative(array $array) {
        return array_values($array) !== $array;
    }
}
