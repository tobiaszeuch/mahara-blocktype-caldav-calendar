<?php

namespace mahara\blocktype\CaldavCalendarPlugin;
require_once (dirname(__FILE__)."/IcalEventBase.php");

/**
 *
 * @author Tobias Zeuch
 */
interface IcalEvent extends IcalEventBase {

    /**
     * returns the duration of the event<br/>
     * The duration is optional but this or end_date must exist
     * @return \DateInterval
     */
    public function get_duration();

    /**
     * the recurrence rule that is used to describe a event that is repeated
     * over time
     * @return IcalRecur
     */
    public function get_recurrence_rule();
    /**
     * returns an array of dates that mark exceptions to the recurrence rule <br/>
     * Each element is an instance of mahara\blocktype\CaldavCalendarPlugin
     * @return array
     */
    public function get_exception_dates();
}
