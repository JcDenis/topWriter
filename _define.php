<?php
/**
 * @brief topWriter, a plugin for Dotclear 2
 * 
 * @package Dotclear
 * @subpackage Plugin
 * 
 * @author Jean-Christian Denis, Pierre Van Glabeke
 * 
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    'Top writer',
    'Ranking of the most prolific writers and/or commentators',
    'Jean-Christian Denis, Pierre Van Glabeke',
    '0.8.1',
    [
        'requires'    => [['core', '2.19']],
        'permissions' => 'admin',
        'type'        => 'plugin',
        'support'     => 'http://forum.dotclear.org/viewtopic.php?pid=333002#p333002',
        'details'     => 'http://plugins.dotaddict.org/dc2/details/topWriter',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/topWriter/master/dcstore.xml'
    ]
);