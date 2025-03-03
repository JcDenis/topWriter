<?php
/**
 * @file
 * @brief       The plugin topWriter definition
 * @ingroup     topWriter
 *
 * @defgroup    topWriter Plugin topWriter.
 *
 * Ranking of the most prolific writers and/or commentators.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Top writer',
    'Ranking of the most prolific writers and/or commentators',
    'Jean-Christian Denis, Pierre Van Glabeke',
    '1.5',
    [
        'requires'    => [['core', '2.33']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-03-03T19:29:19+00:00',
    ]
);
