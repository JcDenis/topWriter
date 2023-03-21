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
declare(strict_types=1);

namespace Dotclear\Plugin\topWriter;

use ArrayObject;
use dcCore;
use form;
use html;

/**
 * @ingroup DC_PLUGIN_TOPWRITER
 * @brief Display most active users - admin methods.
 * @since 2.6
 */
class BackendBehaviors
{
    public static function adminDashboardItemsV2(ArrayObject $__dashboard_items): void
    {
        $pref = self::setDefaultPref();

        # top posts
        if ($pref['topWriterPostsItems']) {
            $lines = Utils::posts($pref['topWriterPostsPeriod'], $pref['topWriterPostsLimit']);
            if (!empty($lines)) {
                $li = [];
                foreach ($lines as $k => $line) {
                    $li[] = sprintf('<li><strong>%s</strong> %s (%s)</li>', $k, $line['author'], $line['count']);
                }

                # Display
                $__dashboard_items[0][] = '<div class="box small" id="topWriterPostsItems">' .
                '<h3>' . html::escapeHTML(__('Top writer: entries')) . '</h3>' .
                '<ul>' . implode('', $li) . '</ul>' .
                '</div>';
            }
        }

        # top comments
        if ($pref['topWriterCommentsItems']) {
            $lines = Utils::comments($pref['topWriterCommentsPeriod'], $pref['topWriterCommentsLimit']);
            if (!empty($lines)) {
                $li = [];
                foreach ($lines as $k => $line) {
                    $li[] = sprintf('<li><strong>%s</strong> %s (%s)</li>', $k, $line['author'], $line['count']);
                }

                # Display
                $__dashboard_items[0][] = '<div class="box small" id="topWriterCommentsItems">' .
                '<h3>' . html::escapeHTML(__('Top writer: comments')) . '</h3>' .
                '<ul>' . implode('', $li) . '</ul>' .
                '</div>';
            }
        }
    }

    public static function adminDashboardOptionsFormV2(): void
    {
        $pref = self::setDefaultPref();

        echo
        '<div class="fieldset">' .
        '<h4>' . __('Top writer: entries') . '</h4>' .
        '<p><label class="classic" for="topWriterPostsItems">' .
        form::checkbox('topWriterPostsItems', 1, $pref['topWriterPostsItems']) . ' ' .
        __('Show') . '</label></p>' .
        '<p><label class="classic" for="topWriterPostsPeriod">' . __('Period:') . ' </label>' .
        form::combo('topWriterPostsPeriod', Utils::periods(), $pref['topWriterPostsPeriod']) . '</p>' .
        '<p><label class="classic" for="topWriterPostsLimit">' . __('Limit:') . ' </label>' .
        form::number('topWriterPostsLimit', ['min' => 1, 'max' => 20, 'default' => $pref['topWriterPostsLimit']]) . '</p>' .
        '</div>' .

        '<div class="fieldset">' .
        '<h4>' . __('Top writer: comments') . '</h4>' .
        '<p><label class="classic" for="topWriterCommentsItems">' .
        form::checkbox('topWriterCommentsItems', 1, $pref['topWriterCommentsItems']) . ' ' .
        __('Show') . '</label></p>' .
        '<p><label class="classic" for="topWriterCommentsPeriod">' . __('Period:') . ' </label>' .
        form::combo('topWriterCommentsPeriod', Utils::periods(), $pref['topWriterCommentsPeriod']) . '</p>' .
        '<p><label class="classic" for="topWriterCommentsLimit">' . __('Limit:') . ' </label>' .
        form::number('topWriterCommentsLimit', ['min' => 1, 'max' => 20, 'default' => $pref['topWriterCommentsLimit']]) . '</p>' .
        '</div>';
    }

    public static function adminAfterDashboardOptionsUpdate(?string $user_id): void
    {
        dcCore::app()->auth->user_prefs->get('dashboard')->put(
            'topWriterPostsItems',
            !empty($_POST['topWriterPostsItems']),
            'boolean'
        );
        dcCore::app()->auth->user_prefs->get('dashboard')->put(
            'topWriterPostsPeriod',
            (string) $_POST['topWriterPostsPeriod'],
            'string'
        );
        dcCore::app()->auth->user_prefs->get('dashboard')->put(
            'topWriterPostsLimit',
            (int) $_POST['topWriterPostsLimit'],
            'integer'
        );

        dcCore::app()->auth->user_prefs->get('dashboard')->put(
            'topWriterCommentsItems',
            !empty($_POST['topWriterCommentsItems']),
            'boolean'
        );
        dcCore::app()->auth->user_prefs->get('dashboard')->put(
            'topWriterCommentsPeriod',
            (string) $_POST['topWriterCommentsPeriod'],
            'string'
        );
        dcCore::app()->auth->user_prefs->get('dashboard')->put(
            'topWriterCommentsLimit',
            (int) $_POST['topWriterCommentsLimit'],
            'integer'
        );
    }

    private static function setDefaultPref(): array
    {
        if (!dcCore::app()->auth->user_prefs->get('dashboard')->prefExists('topWriterPostsItems')) {
            dcCore::app()->auth->user_prefs->get('dashboard')->put(
                'topWriterPostsItems',
                false,
                'boolean'
            );
        }
        if (!dcCore::app()->auth->user_prefs->get('dashboard')->prefExists('topWriterPostsPeriod')) {
            dcCore::app()->auth->user_prefs->get('dashboard')->put(
                'topWriterPostsPeriod',
                'month',
                'string'
            );
        }
        if (!dcCore::app()->auth->user_prefs->get('dashboard')->prefExists('topWriterPostsLimit')) {
            dcCore::app()->auth->user_prefs->get('dashboard')->put(
                'topWriterPostsLimit',
                10,
                'integer'
            );
        }
        if (!dcCore::app()->auth->user_prefs->get('dashboard')->prefExists('topWriterCommentsItems')) {
            dcCore::app()->auth->user_prefs->get('dashboard')->put(
                'topWriterCommentsItems',
                false,
                'boolean'
            );
        }
        if (!dcCore::app()->auth->user_prefs->get('dashboard')->prefExists('topWriterCommentsPeriod')) {
            dcCore::app()->auth->user_prefs->get('dashboard')->put(
                'topWriterCommentsPeriod',
                'month',
                'string'
            );
        }
        if (!dcCore::app()->auth->user_prefs->get('dashboard')->prefExists('topWriterCommentsLimit')) {
            dcCore::app()->auth->user_prefs->get('dashboard')->put(
                'topWriterCommentsLimit',
                10,
                'integer'
            );
        }

        return [
            'topWriterPostsItems'     => dcCore::app()->auth->user_prefs->get('dashboard')->get('topWriterPostsItems'),
            'topWriterPostsPeriod'    => dcCore::app()->auth->user_prefs->get('dashboard')->get('topWriterPostsPeriod'),
            'topWriterPostsLimit'     => dcCore::app()->auth->user_prefs->get('dashboard')->get('topWriterPostsLimit') ?? 10,
            'topWriterCommentsItems'  => dcCore::app()->auth->user_prefs->get('dashboard')->get('topWriterCommentsItems'),
            'topWriterCommentsPeriod' => dcCore::app()->auth->user_prefs->get('dashboard')->get('topWriterCommentsPeriod'),
            'topWriterCommentsLimit'  => dcCore::app()->auth->user_prefs->get('dashboard')->get('topWriterCommentsLimit') ?? 10,
        ];
    }
}
