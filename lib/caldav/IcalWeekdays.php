<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace mahara\blocktype\CaldavCalendarPlugin;

/**
 * list of week days as they can appear in the @see CaldavRecur
 */
abstract class IcalWeekdays {
    const SU = 'SU';
    const MO = 'MO';
    const TU = 'TU';
    const WE = 'WE';
    const TH = 'TH';
    const FR = 'FR';
    const SA = 'SA';

}