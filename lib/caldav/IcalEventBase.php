<?php

namespace mahara\blocktype\CaldavCalendarPlugin;

/**
 * Simple class that describes a simple event instance without repetitions
 * Like the single instances for a repeating event
 * @author Tobias Zeuch
 */
interface IcalEventBase {
    /**
     * returns a list of attendees, which may represent a list of emails
     * Each attendee is of type mahara\blocktype\CalDavCalendarPlugin\CaldavUserAddress
     * @return array
     */
    public function get_attendees();

    /**
     * returns the summary/title of the event
     * @return string
     */
    public function get_summary();

    /**
     * returns the end date of the event<br/>
     * The end date is optional but this, or duration,
     * has to be specified
     * @return \DateTime
     */
    public function get_end_date();

    /**
     * returns the start date of the event<br/>
     * The start date is mandatory for an event
     * @return \DateTime
     */
    public function get_start_date();

    /**
     * returns true, if this event lasts all day and false otherwise
     * @return boolean
     */
    public function is_all_day();
    /**
     * the uid is unique for the event and can be used to retrieve the
     * event from the calDav server
     * @return string
     */
    public function get_uid();

    /**
     * returns the description of the event. This can typically be a longer text.<br/>
     * The description is not mandatory
     * return string
     */
    public function get_description();

    /**
     * returns the location where the event is taking place
     * return string
     */
    public function get_location();
}
