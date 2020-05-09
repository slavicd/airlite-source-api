<?php


namespace Airlite\SourceApi;

use Airlite\Measurement;
use Airlite\SourceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class that allows fetching API data from uradmonitor network.
 *
 * @see https://www.uradmonitor.com/wordpress/wp-content/uploads/2018/08/uradmonitor_example_response_101.pdf
 * @see https://www.uradmonitor.com/
 * @package Airlite\SourceApi
 */
class Uradmonitor implements SourceInterface
{
    const URL = 'http://data.uradmonitor.com/api/v1/';
    const USER_AGENT = 'Airlite/0.1.0 (Linux)';
    const REQ_TIMEOUT = 20;

    private $httpClient;
    private $config;

    public function __construct(array $config=[])
    {
        $defaults = [
            'headers' => [
                'X-User-id' => 'www',
                'X-User-hash' => 'global',
            ],
            'sensors' => array_keys(self::sensorMap()),
            'chunk_size' => 864000, // 10*24*3600 =~ 10 days in seconds; Urad allows max 2 months
        ];
        $this->config = array_merge($defaults, $config);

        $this->httpClient = new Client([
            'base_uri' => self::URL,
            'headers' => array_merge(
                [
                    'User-Agent' => self::USER_AGENT,
                ],
                $this->config['headers']
            )
        ]);
    }

    public static function sensorMap()
    {
        return [
            Measurement::SENSOR_PM25        => 'pm25',
            Measurement::SENSOR_PM10        => 'pm10',
            Measurement::SENSOR_TEMPERATURE => 'temperature',
            Measurement::SENSOR_REL_HUMID   => 'humidity',
        ];
    }

    /**
     * Will fetch data as defined by the $filter parameter and call a client-supplied
     * processor function with a single parameter: a chunk of data points.
     * The chunk size depends on the "chunk_size" constructor config param.
     *
     * The interval of the fetching is between $filter['from'] parameter
     * and the moment of invocation of this method.
     *
     * @param array $filter "station" and "from" keys are mandatory
     * @param callable $processor callable function that receives one chunk of fetched data
     * @return bool
     */
    public function fetch($filter, callable $processor)
    {
        if (!array_key_exists('station', $filter) || empty($filter['station'])) {
            throw new \LogicException('No station identifier provided!');
        }
        if (!array_key_exists('from', $filter)) {
            throw new \LogicException('No from unix timestamp provided!');
        }

        $devId = $filter['station'];
        $sensors = (isset($filter['sensors']) && is_array($filter['sensors']))?
            $filter['sensors'] : $this->config['sensors'];

        // In uradmonitor's API terms, "from" is actually n seconds before this moment
        $from = time() - $filter['from'];
        // todo: from should be taken from the API itself if not provided
        // todo: for short spans, use the "all" sensor keyword to return all sensor data

        $sensorMap = self::sensorMap();

        do {
            $to = $from - $this->config['chunk_size'];
            if ($to < 0) {
                $to = 0;
            }

            foreach ($sensors as $hereKey) {
                $thereKey = $sensorMap[$hereKey];

                $htResp = $this->httpClient->get(
                    sprintf('devices/%s/%s/%d/%d', $devId, $thereKey, $from, $to),
                    ['timeout' => self::REQ_TIMEOUT]
                );
                // todo: deal with failures

                $resp = json_decode($htResp->getBody());

                if (!is_array($resp)) {
                    continue;
                }

                $chunk = array_map(function($data) use ($hereKey, $thereKey) {
                    return [
                        'time'  => $data->time,
                        'lat'   => $data->latitude,
                        'lng'   => $data->longitude,
                        'sensor'=> $hereKey,
                        'value' => $data->{$thereKey},
                    ];
                }, $resp);
                call_user_func($processor, $chunk);
            }
            $from-=$this->config['chunk_size']+1;   //todo: find out from Radu Motisan if startinterval/stopinterval are inclusive or exclusive
        } while ($to>0);

        return true;
    }
}