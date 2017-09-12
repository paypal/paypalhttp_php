<?php

namespace BraintreeHttp\Serializer;

use BraintreeHttp\HttpRequest;
use BraintreeHttp\Serializer;

class Text implements Serializer
{

    public function contentType()
    {
        return "/^text\\/.*/";
    }

    public function serialize(HttpRequest $request)
    {
        $body = $request->body;
        if (is_string($body)) {
            return $body;
        }
        if (is_array($body)) {
            return json_encode($body);
        }
        return implode(" ", $body);
    }

    public function deserialize($data)
    {
        return $data;
    }
}
