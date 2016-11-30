<?php

namespace mahara\blocktype\CaldavCalendarPlugin;


/**
 * Descripbes RECUR elements, like the recurrence rule
 * @author Tobias Zeuch
 */
interface IcalRecur {

    /**
     * returns the number of repetitions this rule defines. Can be empty. In
     * that case, null is returned
     * @return int
     */
    public function get_count();

    /**
     * returns the until-date, that is, the date when the last occurence of
     * the repetition will take place. Can be empty, in which case null is
     * returned
     * @return DateTime
     */
    public function get_until();

    /**
     * returns the frequencey, which is a value defined in class Frequencies
     * @return string
     */
    public function get_frequency();

    /**
     * the interval specifies, how often an event occurs in a given time. It
     * combines with the frequency (@see CaldavRecur::get_frequency)
     * @return int
     */
    public function get_interval();

    /**
     * returns a list of numbers that define on which month(s) the event will
     * take place. This can either be a filter, or expansion, depending on whether
     * the frequency  is bigger or smaller than Frequencies::MONTHLY
     * @erturn array;
     */
    public function get_by_months();

    /**
     * gets a list of of the year on that the event takes place. Negative numbers means
     * that these days are excluded. <br/>
     * @return array
     */
    public function get_by_year_days();

    /**
     * returns a list of positions of the day of the year that are always used
     * as a filter for the expanded values
     * @return array
     */
    public function get_by_set_pos();

    /**
     * returns a list of days of the month (1-31)
     * @return array
     */
    public function get_by_days_of_month();

    /**
     * returns an array of numbered weekdays, see @see IcalNumberedWeekday,
     * each of which can be positive or negative which means, occurence or
     * negative filter
     * @return array
     */
    public function get_by_days();

    /**
     * returns al ist of numbers of hours
     * @return array
     */
    public function get_by_hours();

    /**
     * returns al ist of numbers of minutes
     * @return array
     */
    public function get_by_minutes();

    /**
     * returns al ist of numbers of seconds
     * @return array
     */
    public function get_by_seconds();

    /**
     * returns a weekday, represented by class {@link WeekDays}
     * @return string
     */
    public function get_week_start();

    /**
     * returns a list of week numbers
     * @return array
     */
    public function get_by_week_no();
}

abstract class Frequencies {
    const SECONDLY = 'SECONDLY';
    const MINUTELY = 'MINUTELY';
    const HOURLY = 'HOURLY';
    const DAILY = 'DAILY';
    const WEEKLY = 'WEEKLY';
    const MONTHLY = 'MONTHLY';
    const YEARLY = 'YEARLY';
}

abstract class WeekDays {
    const SU = 'SU';
    const MO = 'MO';
    const TU = 'TU';
    const WE = 'WE';
    const TH = 'TH';
    const FR = 'FR';
    const SA = 'SA';

}