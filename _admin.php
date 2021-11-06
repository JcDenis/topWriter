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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

require dirname(__FILE__) . '/_widgets.php';

# Dashboard item and user preference
$core->addBehavior(
    'adminDashboardItems',
    ['topWriterAdmin', 'adminDashboardItems']
);
$core->addBehavior(
    'adminDashboardOptionsForm',
    ['topWriterAdmin', 'adminDashboardOptionsForm']
);
$core->addBehavior(
    'adminAfterDashboardOptionsUpdate',
    ['topWriterAdmin', 'adminAfterDashboardOptionsUpdate']
);

/**
 * @ingroup DC_PLUGIN_TOPWRITER
 * @brief Display most active users - admin methods.
 * @since 2.6
 */
class topWriterAdmin
{
    public static function adminDashboardItems(dcCore $core, $__dashboard_items)
    {
        $pref = self::setDefaultPref($core);

        # top posts
        if ($pref['topWriterPostsItems']) {
            $lines = topWriter::posts($core, $pref['topWriterPostsPeriod'], $pref['topWriterPostsLimit']);
            if (empty($lines)) {
                return null;
            }

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

        # top comments
        if ($pref['topWriterCommentsItems']) {
            $lines = topWriter::comments($core, $pref['topWriterCommentsPeriod'], $pref['topWriterCommentsLimit']);
            if (empty($lines)) {
                return null;
            }

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

    public static function adminDashboardOptionsForm(dcCore $core)
    {
        $pref = self::setDefaultPref($core);

        echo
        '<div class="fieldset">' .
        '<h4>' . __('Top writer: entries') . '</h4>' .
        '<p><label class="classic" for="topWriterPostsItems">' .
        form::checkbox('topWriterPostsItems', 1, $pref['topWriterPostsItems']) . ' ' .
        __('Show') . '</label></p>' .
        '<p><label class="classic" for="topWriterPostsPeriod">' . __('Period:') . ' </label>' .
        form::combo('topWriterPostsPeriod', topWriter::periods(), $pref['topWriterPostsPeriod']) . '</p>' .
        '<p><label class="classic" for="topWriterPostsLimit">' . __('Limit:') . ' </label>' .
        form::number('topWriterPostsLimit', ['min' => 1, 'max' => 20, 'default' => $pref['topWriterPostsLimit']]) . '</p>' .
        '</div>' .

        '<div class="fieldset">' .
        '<h4>' . __('Top writer: comments') . '</h4>' .
        '<p><label class="classic" for="topWriterCommentsItems">' .
        form::checkbox('topWriterCommentsItems', 1, $pref['topWriterCommentsItems']) . ' ' .
        __('Show') . '</label></p>' .
        '<p><label class="classic" for="topWriterCommentsPeriod">' . __('Period:') . ' </label>' .
        form::combo('topWriterCommentsPeriod', topWriter::periods(), $pref['topWriterCommentsPeriod']) . '</p>' .
        '<p><label class="classic" for="topWriterCommentsLimit">' . __('Limit:') . ' </label>' .
        form::number('topWriterCommentsLimit', ['min' => 1, 'max' => 20, 'default' => $pref['topWriterCommentsLimit']]) . '</p>' .
        '</div>';
    }

    public static function adminAfterDashboardOptionsUpdate($user_id)
    {
        global $core;

        $core->auth->user_prefs->dashboard->put(
            'topWriterPostsItems',
            !empty($_POST['topWriterPostsItems']),
            'boolean'
        );
        $core->auth->user_prefs->dashboard->put(
            'topWriterPostsPeriod',
            (string) $_POST['topWriterPostsPeriod'],
            'string'
        );
        $core->auth->user_prefs->dashboard->put(
            'topWriterPostsLimit',
            (int) $_POST['topWriterPostsLimit'],
            'integer'
        );

        $core->auth->user_prefs->dashboard->put(
            'topWriterCommentsItems',
            !empty($_POST['topWriterCommentsItems']),
            'boolean'
        );
        $core->auth->user_prefs->dashboard->put(
            'topWriterCommentsPeriod',
            (string) $_POST['topWriterCommentsPeriod'],
            'string'
        );
        $core->auth->user_prefs->dashboard->put(
            'topWriterCommentsLimit',
            (int) $_POST['topWriterCommentsLimit'],
            'integer'
        );
    }

    private static function setDefaultPref($core)
    {
        if (!$core->auth->user_prefs->dashboard->prefExists('topWriterPostsItems')) {
            $core->auth->user_prefs->dashboard->put(
                'topWriterPostsItems',
                false,
                'boolean'
            );
        }
        if (!$core->auth->user_prefs->dashboard->prefExists('topWriterPostsPeriod')) {
            $core->auth->user_prefs->dashboard->put(
                'topWriterPostsPeriod',
                'month',
                'string'
            );
        }
        if (!$core->auth->user_prefs->dashboard->prefExists('topWriterPostsLimit')) {
            $core->auth->user_prefs->dashboard->put(
                'topWriterPostsLimit',
                10,
                'integer'
            );
        }
        if (!$core->auth->user_prefs->dashboard->prefExists('topWriterCommentsItems')) {
            $core->auth->user_prefs->dashboard->put(
                'topWriterCommentsItems',
                false,
                'boolean'
            );
        }
        if (!$core->auth->user_prefs->dashboard->prefExists('topWriterCommentsPeriod')) {
            $core->auth->user_prefs->dashboard->put(
                'topWriterCommentsPeriod',
                'month',
                'string'
            );
        }
        if (!$core->auth->user_prefs->dashboard->prefExists('topWriterCommentsLimit')) {
            $core->auth->user_prefs->dashboard->put(
                'topWriterCommentsLimit',
                10,
                'integer'
            );
        }

        return [
            'topWriterPostsItems'     => $core->auth->user_prefs->dashboard->get('topWriterPostsItems'),
            'topWriterPostsPeriod'    => $core->auth->user_prefs->dashboard->get('topWriterPostsPeriod'),
            'topWriterPostsLimit'     => $core->auth->user_prefs->dashboard->get('topWriterPostsLimit') ?? 10,
            'topWriterCommentsItems'  => $core->auth->user_prefs->dashboard->get('topWriterCommentsItems'),
            'topWriterCommentsPeriod' => $core->auth->user_prefs->dashboard->get('topWriterCommentsPeriod'),
            'topWriterCommentsLimit'  => $core->auth->user_prefs->dashboard->get('topWriterCommentsLimit') ?? 10
        ];
    }
}
