<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of topWriter, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2021 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    'topWriter',
    'Ranking of the most prolific writers and/or commentators',
    'Jean-Christian Denis, Pierre Van Glabeke',
    '0.8',
    [
        'permissions' => 'admin',
        'type' => 'plugin',
        'dc_min' => '2.19',
        'support' => 'http://forum.dotclear.org/viewtopic.php?pid=333002#p333002',
        'details' => 'http://plugins.dotaddict.org/dc2/details/topWriter',
        'repository' => 'https://raw.githubusercontent.com/JcDenis/topWriter/master/dcstore.xml'
    ]
);