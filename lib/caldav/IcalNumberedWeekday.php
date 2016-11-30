<?php

namespace mahara\blocktype\CaldavCalendarPlugin;

/**
 * A quantified weekday has a number and a weekday, that points to @see IcalWeekday
 *
 * @author tobias
 */
class IcalNumberedWeekday {
    /**
     * int
     */
    public $number;
    /**
     * points to @see IcalWeekday
     * string
     */
    public $weekday;

    public function __construct($number, $weekday) {
        $this->number = $number;
        $this->weekday = $weekday;
    }
}
