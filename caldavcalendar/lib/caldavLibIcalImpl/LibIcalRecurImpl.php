<?php

namespace mahara\blocktype\CaldavCalendarPlugin\libical;

use mahara\blocktype\CaldavCalendarPlugin\IcalRecur;
use mahara\blocktype\CaldavCalendarPlugin\IcalNumberedWeekday;

/**
 * Descripbes RECUR elements, like the recurrence rule
 * @author Tobias Zeuch
 */
class LibIcalRecurImpl implements IcalRecur {


    /** = "SECONDLY" / "MINUTELY" / "HOURLY" / "DAILY" / "WEEKLY" / "MONTHLY" / "YEARLY"*/
    const FREQ = 'FREQ';
    /** 1*DIGIT */
    const INTERVAL = 'INTERVAL';
    /** 1*DIGIT */
    const COUNT = 'COUNT';
    /**
     * date
     * date-time            ;An UTC value
     */
    const UNTIL = 'UNTIL';
    /**
     * seconds / ( seconds *("," seconds) )
     * seconds    = 1DIGIT / 2DIGIT       ;0 to 59
     */
    const BYSECOND = 'BYSECOND';
    /**
     * minutes / ( minutes *("," minutes) )
     * minutes    = 1DIGIT / 2DIGIT       ;0 to 59
     */
    const BYMINUTE = 'BYMINUTE';
    /**
     * hour / ( hour *("," hour) )
     * hour       = 1DIGIT / 2DIGIT       ;0 to 23
     */
    const BYHOUR = 'BYHOUR';
    /**
     * weekdaynum / ( weekdaynum *("," weekdaynum) )
     * weekdaynum = [([plus] ordwk / minus ordwk)] weekday
     * plus       = "+"
     * minus      = "-"
     * ordwk      = 1DIGIT / 2DIGIT       ;1 to 53
     * weekday    = "SU" / "MO" / "TU" / "WE" / "TH" / "FR" / "SA"
     * ;Corresponding to SUNDAY, MONDAY, TUESDAY, WEDNESDAY, THURSDAY,
     * ;FRIDAY, SATURDAY and SUNDAY days of the week.
     */
    const BYDAY = 'BYDAY';
    /**
     * monthdaynum / ( monthdaynum *("," monthdaynum) )
     * monthdaynum = ([plus] ordmoday) / (minus ordmoday)
     * ordmoday   = 1DIGIT / 2DIGIT       ;1 to 31
     */
    const BYMONTHDAY = 'BYMONTHDAY';
    /**
     * yeardaynum / ( yeardaynum *("," yeardaynum) )
     * yeardaynum = ([plus] ordyrday) / (minus ordyrday)
     * ordyrday   = 1DIGIT / 2DIGIT / 3DIGIT      ;1 to 366
     */
    const BYYEARDAY = 'BYYEARDAY';
    /**
     * weeknum / ( weeknum *("," weeknum) )
     * weeknum = ([plus] ordwk) / (minus ordwk)
     * ordwk      = 1DIGIT / 2DIGIT       ;1 to 53
     */
    const BYWEEKNO = 'BYWEEKNO';
    /**
     * monthnum / ( monthnum *("," monthnum) )
     * monthnum   = 1DIGIT / 2DIGIT       ;1 to 12
     */
    const BYMONTH = 'BYMONTH';
    /**
     * setposday / ( setposday *("," setposday) )
     * setposday  = yeardaynum
     */
    const BYSETPOS = 'BYSETPOS';

    /**
     *the wrapped \Rrule
     * @var Rrule
     */
    private $rrule;

    public function __construct(\Rrule $rrule) {
        $this->rrule = $rrule;
    }

    /**
     * returns the number of repetitions this rule defines. Can be empty. In
     * that case, null is returned
     * @return int
     */
    public function get_count() {
        if (array_key_exists(self::COUNT, $this->rrule->params)) {
            return $this->rrule->params[self::COUNT];
        }
        return null;
    }

    /**
     * returns the until-date, that is, the date when the last occurence of
     * the repetition will take place. Can be empty, in which case null is
     * returned
     * @return DateTime
     */
    public function get_until() {
        if (array_key_exists(self::UNTIL, $this->rrule->params)) {
            return \RemoteCalendarUtil::ical_date_to_DateTime($this->rrule->params[self::UNTIL]);
        }
        return null;
    }

    /**
     * returns the frequencey, which is a value defined in class Frequencies
     * @return string
     */
    public function get_frequency() {
        if (array_key_exists(self::FREQ, $this->rrule->params)) {
            return $this->rrule->params[self::FREQ];
        }
        return null;
    }

    /**
     * the interval specifies, how often an event occurs in a given time. It
     * combines with the frequency (@see CaldavRecur::get_frequency)
     * @return string
     */
    public function get_interval() {
        if (array_key_exists(self::INTERVAL, $this->rrule->params)) {
            return $this->rrule->params[self::INTERVAL];
        }
        return null;
    }

    /**
     * returns a list of numbers that define on which month(s) the event will
     * take place. This can either be a filter, or expansion, depending on whether
     * the frequency  is bigger or smaller than Frequencies::MONTHLY
     * @erturn array;
     */
    public function get_by_months() {
        if (array_key_exists(self::BYMONTH, $this->rrule->params)) {
            $monthlist = $this->rrule->params[self::BYMONTH];
            return explode(',', $monthlist);
        }
        return null;
    }

    /**
     * gets a list of of the year on that the event takes place. Negative numbers means
     * that these days are excluded. <br/>
     * @return array
     */
    public function get_by_year_days() {
        if (array_key_exists(self::BYYEARDAY, $this->rrule->params)) {
            $daylist = $this->rrule->params[self::BYYEARDAY];
            return explode(',', $daylist);
        }
        return null;
    }

    /**
     * returns a list of positions of the day of the year that are always used
     * as a filter for the expanded values
     * @return array
     */
    public function get_by_set_pos() {
        if (array_key_exists(self::BYSETPOS, $this->rrule->params)) {
            $poslist = $this->rrule->params[self::BYSETPOS];
            return explode(',', $poslist);
        }
        return null;
    }

    /**
     * returns a list of days of the week
     * each of theses can be positive or negative which means occurence or
     * negative filter
     * @return array
     */
    public function get_by_days() {
        if (array_key_exists(self::BYDAY, $this->rrule->params)) {
            $daylist = $this->rrule->params[self::BYDAY];
            $days = explode(',', $daylist);
            $numberedWeekdays = array();
            foreach ($days as $day) {
                $number = null;
                $weekday = $day;
                if (strlen($day) > 2) {
                    $number = substr($day, 0, strlen($day) - 2);
                    $weekday = substr($day, -2);
                }
                $numberedWeekdays []= new IcalNumberedWeekday($number, $weekday);
            }
            return $numberedWeekdays;
        }
        return null;
    }
    
    /**
     * returns a list of days of the month (1-31)
     * @return array
     */
    public function get_by_days_of_month() {
        if (array_key_exists(self::BYMONTHDAY, $this->rrule->params)) {
            $daylist = $this->rrule->params[self::BYMONTHDAY];
            return explode(',', $daylist);
        }
        return null;
    }

    /**
     * returns al ist of numbers of hours
     * @return array
     */
    public function get_by_hours() {
        if (array_key_exists(self::BYHOUR, $this->rrule->params)) {
            $hourlist = $this->rrule->params[self::BYHOUR];
            return explode(',', $hourlist);
        }
        return null;
    }

    /**
     * returns al ist of numbers of minutes
     * @return array
     */
    public function get_by_minutes() {
        if (array_key_exists(self::BYMINUTE, $this->rrule->params)) {
            $minutelist = $this->rrule->params[self::BYMINUTE];
            return explode(',', $minutelist);
        }
        return null;
    }

    /**
     * returns al ist of numbers of seconds
     * @return array
     */
    public function get_by_seconds() {
        if (array_key_exists(self::BYSECOND, $this->rrule->params)) {
            $secondlist = $this->rrule->params[self::BYSECOND];
            return explode(',', $secondlist);
        }
        return null;
    }

    /**
     * returns a weekday, represented by class {@link WeekDays}
     */
    public function get_week_start() {
        if (array_key_exists(self::WKST, $this->rrule->params)) {
            return $this->rrule->params[self::WKST];
        }
        return null;
    }

    public function get_by_week_no() {
        if (array_key_exists(self::BYWEEKNO, $this->rrule->params)) {
            return $this->rrule->params[self::BYWEEKNO];
        }
        return null;
    }

}