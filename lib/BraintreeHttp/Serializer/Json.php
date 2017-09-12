<?php

namespace BraintreeHttp\Serializer;

use BraintreeHttp\Deserializable;
use BraintreeHttp\HttpRequest;
use BraintreeHttp\Serializable;
use BraintreeHttp\Serializer;

class Json implements Serializer
{

    public function contentType()
    {
        return "/^application\\/json$/";
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
        if ($body instanceof Serializable) {
            $map = [];
            $body->serialize($map);
            return json_encode($map);
        }
        throw new \Exception("Cannot serialize data. Unknown type");
    }

    public function deserialize($data)
    {
        return json_decode($data);
    }
}
