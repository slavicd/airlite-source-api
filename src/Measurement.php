<?php


namespace Airlite;


class Measurement
{
    const SENSOR_PM25 = 25;                 // mkg/m^3
    const SENSOR_PM25_GRAVIMETRIC = 250;    // mkg/m^3
    const SENSOR_PM10 = 10;                 // mkg/m^3
    const SENSOR_PM10_GRAVIMETRIC = 100;    // mkg/m^3
    const SENSOR_CO2 = 2;                   // ppm
    const SENSOR_TEMPERATURE = 1;           // Celsius
    const SENSOR_REL_HUMID = 5;             // %

    public $sensor;
    public $time;
    public $value;
    public $lat;
    public $lng;

    public function __construct(array $data)
    {
        $this->sensor   = $data['sensor'];
        $this->time     = $data['time'];
        $this->value    = $data['value'];
        $this->lat      = $data['lat'];
        $this->lng      = $data['lng'];
    }
}