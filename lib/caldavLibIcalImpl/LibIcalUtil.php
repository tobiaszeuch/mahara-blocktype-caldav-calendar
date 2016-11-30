<?php

namespace mahara\blocktype\CaldavCalendarPlugin\libical;

require_once(dirname(dirname(dirname(__FILE__))).'/lib/external/libical/ical.php');
require_once(dirname(__FILE__).'/LibIcalCalendarImpl.php');
require_once(dirname(__FILE__).'/LibIcalEventImpl.php');

// import namespaces
use mahara\blocktype\CaldavCalendarPlugin\libical\LibIcalCalendarImpl;

/**
 * Class that offers util factory-methods to create CaldavCalendar-instances and subobjects
 * from a set of icalFiles
 *
 * @author Tobias Zeuch
 */
class LibIcalUtil {

    const VCALENDAR = "VCALENDAR";

    /**
     *
     * @param array $icalFiles
     * @return LibIcalCalendarImpl
     */
    public static function createCaldavCalendarForIcalFiles(array $icalFiles) {
        $icalFile = new \ical_File();

        foreach ($icalFiles as $icalendar) {
            $array = preg_split ('/$\R?^/m', $icalendar);
            $icalFile->load_ical($array);
        }

        $vCalendars = $icalFile->get(self::VCALENDAR);
        if (null != $vCalendars && sizeof($vCalendars) > 0) {
            $calendar = new LibIcalCalendarImpl($vCalendars);
            return $calendar;
        }
        else {
            throw new Exception("Error: no calendar exported. Exported: " + var_export($vCalendars, true), -1, null);
        }
        return null;
    }
}
