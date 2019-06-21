<?php

namespace mahara\blocktype\CaldavCalendarPlugin\libical;

use \mahara\blocktype\CalDavCalendarPlugin\IcalUserAddress;

/**
 *
 * @author Tobias Zeuch
 */
class LibIcalUserAddressImpl implements IcalUserAddress {
    private $ical_Property;

    const MAILTO = "mailto:";

    public function __construct(\ical_Property $attendeeProperty) {
        $this->ical_Property = $attendeeProperty;
    }

    /**
     * returns the value of the user address, which is of type URI
     * "as defined by [RFC 1738] or any other IANA registered form for a URI"
     * it may be a mail-address, in that case it is preceded by MAILTO:, like in
     * MAILTO:jane_doe@host.com
     * return string
     */
    public function get_value() {
        return $this->ical_Property->get_value();
    }
    
    /**
     * returns true, if this address renders an email adress
     * return boolean
     */
    public function is_mail_address() {
        $value = strtolower($this->get_value());
        if (null == $value) {
            return false;
        }
        if (substr($value, 0, strlen(self::MAILTO)) == self::MAILTO) {
            return true;
        }
        return false;
    }

    /**
     * returns the mail-adress, without the MAILTO: prefix
     * return string
     */
    public function get_mail_address() {
        $value = strtolower($this->get_value());
        if ($this->is_mail_address()) {
            return substr($value, strlen(self::MAILTO));
        }
        return null;
    }
}
