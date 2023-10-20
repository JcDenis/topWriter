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
    '1.4',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/issues',
        'details'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository'  => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);
