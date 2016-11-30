<?php

namespace mahara\blocktype\CaldavCalendarPlugin\libical;

use \mahara\blocktype\CaldavCalendarPlugin\IcalCalendar;

/**
 * Wrapper for the libical-vCalendar file
 *
 * @author tobias
 */
class LibIcalCalendarImpl implements IcalCalendar {
    /** @var array the vCalendar file that is wrapped here */
    private $vCalendars;
    const VEVENT = 'VEVENT';

    public function __construct(array $vCalendar) {
        $this->vCalendars = $vCalendar;
    }

    public function get_events() {
        $events = array();
        foreach ($this->vCalendars as $vCalendar) {
            $vEvents = $vCalendar->get(self::VEVENT);
            foreach ($vEvents as $vEvent) {
                $event = new LibIcalEventImpl($vEvent);
                $events []= $event;
            }
        }
        return $events;
    }

}
