########################################################################
#
# Project: libical
# URL: http://www.nabber.org/projects/
# E-mail: webmaster@nabber.org
#
# Copyright: (C) 2003-2007, Neil McNab
# License: GNU General Public License Version 2
#   (http://www.gnu.org/copyleft/gpl.html)
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
#
# Filename: $URL: https://libical.svn.sourceforge.net/svnroot/libical/trunk/libical/README.txt $
# Last Updated: $Date: 2007-08-14 18:41:07 -0700 (Tue, 14 Aug 2007) $
# Author(s): Neil McNab
#
# Description:
#   This file contains overview information on the project.
#
########################################################################

VERSION: 0.3

INTRODUCTION

Provides a read/write library of classes for object oriented languages (Initial goals of PHP and Python) that implement and enforce the iCal standard (RFC 2445).

There are many projects out there that allow for viewing of iCal files via a web interface.  However, only a few, if any, have support for updating and editing iCal files via the web.  Hence, I have decided that an object oriented PHP library is needed.  This will help to spawn other projects to use the iCal format and also ensure that these projects implement the iCal standard correctly.

Once a stable PHP version has been created, this should be easily portable to other languages, such as Python.

REQUIREMENTS

1. PHP (Tested using version 5.2.0)
   http://www.php.net

