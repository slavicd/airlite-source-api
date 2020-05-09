<?php


namespace Airlite\SourceApi;

use Airlite\Measurement;
use Airlite\SourceInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class that allows fetching API data from IqAir (previously AirVisual) devices.
 * N.B.: "API" here means the API exposed by IQ Air to device owners, not the licensed
 * API IQ Air offers.
 *
 * @see
 * @package Airlite\SourceApi
 */
class Airvisual implements SourceInterface
{
    const URL = 'https://www.airvisual.com/api/v2/node/';
    const USER_AGENT = 'Airlite/0.1.0 (Linux)';
    const REQ_TIMEOUT = 10;

    private $httpClient;
    private $config;

    public function __construct(array $config=[])
    {
        if (!array_key_exists('station', $config) || empty($config['station'])) {
            throw new \LogicException('No station identifier provided!');
        }

        $defaults = [
            'sensors' => array_keys(self::sensorMap()),
        ];
        $this->config = array_merge($defaults, $config);

        $this->httpClient = new Client([
            'base_uri' => self::URL,
            'headers' => [
                'User-Agent' => self::USER_AGENT,
            ],
        ]);
    }

    public static function sensorMap()
    {
        return [
            Measurement::SENSOR_PM25        => 'p2',
            Measurement::SENSOR_PM10        => 'p1',
            Measurement::SENSOR_CO2         => 'co',
            Measurement::SENSOR_REL_HUMID   => 'hm',
            Measurement::SENSOR_TEMPERATURE => 'tp',
        ];
    }

    /**
     * Airvisual owner API only offers data for
     *
     * @param array $filter this is a dummy for AirVisual, just here for sake of consistent interface
     * @param callable $processor callable function that receives one chunk of fetched data
     * @return bool
     */
    public function fetch($filter, callable $processor)
    {

        $devId = $this->config['station'];
        $sensors = (isset($filter['sensors']) && is_array($filter['sensors']))?
            $filter['sensors'] : $this->config['sensors'];

        $sensorMap = self::sensorMap();

        $htResp = $this->httpClient->get(sprintf("%s%s", self::URL, $devId));
        $resp = json_decode($htResp->getBody());

        $chunk = [];
        for ($i=sizeof($resp->historical->instant)-1; $i>=0; $i--) {
            $data = $resp->historical->instant[$i];

            foreach ($sensors as $hereKey) {
                $thereKey = $sensorMap[$hereKey];

                $chunk[] = ([
                    'time'  => strtotime($data->ts),
                    'sensor'=> $hereKey,
                    'value' => $data->{$thereKey},
                ]);
            }
        }

        call_user_func($processor, $chunk);

        return true;
    }
}