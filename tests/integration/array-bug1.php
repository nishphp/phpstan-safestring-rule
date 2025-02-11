<?php

namespace Arr;

use DatePeriod;
use DateTime;
use DateInterval;
use DateTimeInterface;

class DateSet
{
    /** @var array<string, bool> */
    public $data;

    /** @return DatePeriod<DateTimeInterface,DateTimeInterface,null> */
    private function makePeriod(string $start, string $end)
    {
        $s = new DateTime($start);
        $e = new DateTime($end);

        $e->add(new DateInterval('P1D'));

        return new DatePeriod($s, new DateInterval('P1D'), $e);
    }

    public function __construct(?string $start = null, ?string $end = null)
    {
        $this->data = [];

        if ($start && $end){
            foreach ($this->makePeriod($start, $end) as $date){
                $this->data[$date->format('Ymd')] = true;
            }
        }
    }

    public function remove(string $start, string $end):void
    {
        foreach ($this->makePeriod($start, $end) as $date){
            if (isset($this->data[$date->format('Ymd')]))
                unset($this->data[$date->format('Ymd')]);
        }
    }

    public function err():void
    {
        foreach ($this->data as $k => $v){
            echo $k;
            echo $v;
        }
    }
}
