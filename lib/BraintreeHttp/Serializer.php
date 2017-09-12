<?php

namespace BraintreeHttp;

interface Serializer
{
    /**
     * @return string Regex that matches the content type it supports.
     */
    public function contentType();

    public function serialize(HttpRequest $request);

    public function deserialize($body);
}
