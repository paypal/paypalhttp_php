<?php

namespace BraintreeHttp;

class Curl {

    protected $curl;

    public function init() {
        $this->curl = curl_init();
        return $this;
    }

    public function setOpt($option, $value) {
        curl_setopt($this->curl, $option, $value);
        return $this;
    }

    public function close()
    {
        curl_close($this->curl);
        return $this;
    }

    public function exec() {
        return curl_exec($this->curl);
    }

    public function errNo()
    {
        return curl_errno($this->curl);
    }

    public function getInfo($option)
    {
        return curl_getinfo($this->curl, $option);
    }

    public function error()
    {
        return curl_error($this->curl);
    }
}