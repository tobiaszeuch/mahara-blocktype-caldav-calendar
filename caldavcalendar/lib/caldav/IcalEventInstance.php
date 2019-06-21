<?php

namespace mahara\blocktype\CaldavCalendarPlugin;

/**
 * Simple class that describes a simple event instance without repetitions
 * Like the single instances for a repeating event
 * @author Tobias Zeuch
 */
interface IcalEventInstance extends IcalEventBase {
    
    /**
     * returns the end date of the event<br/>
     * If no start date exists, it should be calculated by adding
     * duration to startdate
     * @return \DateTime
     */
    public function get_calculated_end_date();
}
