<?php

namespace PayPalHttp;

use PayPalHttp\Serializer\Form;
use PayPalHttp\Serializer\Json;
use PayPalHttp\Serializer\Multipart;
use PayPalHttp\Serializer\Text;

/**
 * Class Encoder
 * @package PayPalHttp
 *
 * Encoding class for serializing and deserializing request/response.
 */
class Encoder
{
    private $serializers = [];

    function __construct()
    {
        $this->serializers[] = new Json();
        $this->serializers[] = new Text();
        $this->serializers[] = new Multipart();
        $this->serializers[] = new Form();
    }


    /*
     * Supporting case insensitivity in headers */
    public function  prepareHeaders(array $headers){
        return array_change_key_case($headers);
    }

    public function serializeRequest(HttpRequest $request)
    {
        $formattedHeaders = $this->prepareHeaders($request->headers);
        if (!array_key_exists('content-type', $formattedHeaders)) {
            throw new \Exception("HttpRequest does not have Content-Type header set");
        }

        $contentType = $formattedHeaders['content-type'];
        /** @var Serializer $serializer */
        $serializer = $this->serializer($contentType);

        if (is_null($serializer)) {
            throw new \Exception(sprintf("Unable to serialize request with Content-Type: %s. Supported encodings are: %s", $contentType, implode(", ", $this->supportedEncodings())));
        }

        if (!(is_string($request->body) || is_array($request->body))) {
            throw new \Exception(sprintf("Body must be either string or array"));
        }

        $serialized = $serializer->encode($request);

        if (array_key_exists("content-encoding", $formattedHeaders) && $formattedHeaders["content-encoding"] === "gzip") {
            $serialized = gzencode($serialized);
        }

        return $serialized;
    }


    public function deserializeResponse($responseBody, $headers)
    {
        $formattedHeaders = $this->prepareHeaders($headers);
        if (!array_key_exists('content-type', $formattedHeaders)) {
            throw new \Exception("HTTP response does not have Content-Type header set");
        }

        $contentType = $formattedHeaders['content-type'];
        /** @var Serializer $serializer */
        $serializer = $this->serializer($contentType);

        if (is_null($serializer)) {
            throw new \Exception(sprintf("Unable to deserialize response with Content-Type: %s. Supported encodings are: %s", $contentType, implode(", ", $this->supportedEncodings())));
        }

        if (array_key_exists("content-encoding", $formattedHeaders) && $formattedHeaders["content-encoding"] === "gzip") {
            $responseBody = gzdecode($responseBody);
        }

        return $serializer->decode($responseBody);
    }

    private function serializer($contentType)
    {
        /** @var Serializer $serializer */
        foreach ($this->serializers as $serializer) {
            try {
                if (preg_match($serializer->contentType(), $contentType) == 1) {
                    return $serializer;
                }
            } catch (\Exception $ex) {
                throw new \Exception(sprintf("Error while checking content type of %s: %s", get_class($serializer), $ex->getMessage()), $ex->getCode(), $ex);
            }
        }

        return NULL;
    }

    private function supportedEncodings()
    {
        $values = [];
        /** @var Serializer $serializer */
        foreach ($this->serializers as $serializer) {
            $values[] = $serializer->contentType();
        }
        return $values;
    }
}
