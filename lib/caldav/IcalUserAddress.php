<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace mahara\blocktype\CalDavCalendarPlugin;

/**
 *
 * @author tobias
 */
interface IcalUserAddress {
    /**
     * returns the value of the user address, which is of type URI
     * "as defined by [RFC 1738] or any other IANA registered form for a URI"
     * it may be a mail-address, in that case it is preceded by MAILTO:, like in
     * MAILTO:jane_doe@host.com
     * return string
     */
    public function get_value();
    
    /**
     * returns true, if this address renders an email adress
     * return boolean
     */
    public function is_mail_address();

    /**
     * returns the mail-adress, without the MAILTO: prefix
     * return string
     */
    public function get_mail_address();
}
