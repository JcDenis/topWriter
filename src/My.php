<?php

declare(strict_types=1);

namespace Dotclear\Plugin\topWriter;

use Dotclear\Module\MyPlugin;

/**
 * @brief       topWriter My helper.
 * @ingroup     topWriter
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    public const TOP_GROUPS = [
        'Posts',
        'Comments'
    ];
    // Use default persmissions
}
