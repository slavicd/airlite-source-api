# airlite-source-api
A collection of API wrappers for services that provide air quality data.

## Networks

* [Uradmonitor](https://www.uradmonitor.com/)
* [Airvisual/IQAir](https://www.iqair.com/) device owner API

## Usage

````php
<?php

use Airlite\Measurement;
use Airlite\SourceApi\Uradmonitor as UradApi;
$api = new UradApi([
    'chunk_size' => 3600*24,    // 24 hours in seconds
]);

$filter = [
    'from' => strtotime('yesterday'),
    'sensors'   => [Measurement::SENSOR_PM25, Measurement::SENSOR_PM10],
];

// fetch and process with a lambda function that receives chunks of 24 hours of data
$api->fetch(
    $filter,
    function($dataChunk) {
        foreach ($dataChunk as $row) {
            var_dump($row->value);
        }
    }
);
````
