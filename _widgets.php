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

$core->addBehavior('initWidgets', ['topWriterWidget', 'init']);

class topWriterWidget
{
    public static function init($w)
    {
    #Top comments widget
        $w
            ->create(
                'topcom',
                __('Top Writer: top comments'),
                ['topWriterWidget', 'topCom'],
                null,
                __('List users who write more comments')
            )
            ->addTitle(__('Top comments'))
            ->setting(
                'text',
                __('Text:'),
                '%author% (%count%)',
                'text'
            )
            ->setting(
                'period',
                __('Period:'),
                'year',
                'combo',
                [
                    __('day')           => 'day',
                    __('week')      => 'week',
                    __('month')     => 'month',
                    __('year')      => 'year',
                    __('from begining') => ''
                ]
            )
            ->setting(
                'sort',
                __('Sort:'),
                'desc',
                'combo',
                [
                    __('Ascending') => 'asc',
                    __('Descending')    => 'desc'
                ]
            )
            ->setting(
                'limit',
                __('Limit:'),
                '10',
                'text'
            )
            ->setting(
                'exclude',
                __('Exclude post writer from list'),
                0,
                'check'
            )
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        #Top entries widget
        $w
            ->create(
                'toppost',
                __('Top Writer: top entries'),
                ['topWriterWidget', 'topPost'],
                null,
                __('List users who write more posts')
            )
            ->addTitle(__('Top entries'))
            ->setting(
                'text',
                __('Text:'),
                '%author% (%count%)',
                'text'
            )
            ->setting(
                'period',
                __('Period:'),
                'year',
                'combo',
                [
                    __('day')           => 'day',
                    __('week')      => 'week',
                    __('month')     => 'month',
                    __('year')      => 'year',
                    __('from begining') => ''
                ]
            )
            ->setting(
                'sort',
                __('Sort:'),'desc',
                'combo',
                [
                    __('Ascending') => 'asc',
                    __('Descending')    => 'desc'
                ]
            )
            ->setting(
                'limit',
                __('Limit:'),
                '10',
                'text'
            )
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    public static function topCom($w)
    {
        global $core;

        if ($w->offline) {
            return null;
        }

        if (($w->homeonly == 1 && !$core->url->isHome($core->url->type)) 
         || ($w->homeonly == 2 && $core->url->isHome($core->url->type))) {
            return null;
        }

        $req =
        'SELECT COUNT(*) AS count, comment_email ' .
        "FROM " . $core->prefix . "post P,  " . $core->prefix . "comment C " .
        'WHERE P.post_id=C.post_id ' .
        "AND blog_id='" . $core->con->escape($core->blog->id) . "' " .
        'AND post_status=1 AND comment_status=1 ' .
        self::period('comment_dt', $w->period);

        if ($w->exclude) {
            $req .= 
            'AND comment_email NOT IN (' .
            ' SELECT U.user_email ' .
            ' FROM ' . $core->prefix . 'user U' .
            ' INNER JOIN ' . $core->prefix . 'post P ON P.user_id = U.user_id ' .
            " WHERE blog_id='" . $core->con->escape($core->blog->id) . "' " .
            ' GROUP BY U.user_email) ';
        }

        $req .=
        'GROUP BY comment_email ' .
        'ORDER BY count ' . ($w->sort == 'asc' ? 'ASC' : 'DESC') . ' ' .
        $core->con->limit(abs((integer) $w->limit));

        $rs = $core->con->select($req);

        if ($rs->isEmpty()) {
            return null;
        }

        $content = '';
        $i = 0;
        while($rs->fetch()) {
            $user = $core->con->select(
                "SELECT * FROM " . $core->prefix . "comment " .
                "WHERE comment_email='" . $rs->comment_email . "' " .
                'ORDER BY comment_dt DESC'
            );

            if (!$user->comment_author) {
                continue;
            }

            $i++;
            $rank = '<span class="topcomments-rank">' . $i . '</span>';

            if ($user->comment_site) {
                $author = '<a href="' . $user->comment_site . '" title="' .
                    __('Author link') . '">' . $user->comment_author . '</a>';
            } else {
                $author = $user->comment_author;
            }
            $author = '<span class="topcomments-author">' . $author . '</span>';

            if ($rs->count == 0) {
                $count = __('no comments');
            } else {
                $count = sprintf(__('one comment', '%s comments', $rs->count), $rs->count);
            }

            $content .= sprintf(
                '<li>%s</li>', 
                str_replace(
                    ['%rank%', '%author%', '%count%'],
                    [$rank, $author, $count],
                    $w->text
                )
            );
        }

        if ($i < 1) {
            return null;
        }

        return $w->renderDiv(
            $w->content_only,
            'topcomments ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
                sprintf('<ul>%s</ul>', $content)
        );
    }

    public static function topPost($w)
    {
        global $core;

        if ($w->offline) {
            return null;
        }

        if (($w->homeonly == 1 && !$core->url->isHome($core->url->type)) 
         || ($w->homeonly == 2 && $core->url->isHome($core->url->type))) {
            return null;
        }

        $rs = $core->con->select(
        'SELECT COUNT(*) AS count, U.user_id ' .
        "FROM " . $core->prefix . "post P " .
        'INNER JOIN ' . $core->prefix . 'user U ON U.user_id = P.user_id ' .
        "WHERE blog_id='" . $core->con->escape($core->blog->id) . "' " .
        'AND post_status=1 AND user_status=1 ' .
        self::period('post_dt', $w->period) .
        'GROUP BY U.user_id ' .
        'ORDER BY count ' . ($w->sort == 'asc' ? 'ASC' : 'DESC') . ', U.user_id ASC ' .
        $core->con->limit(abs((integer) $w->limit)));

        if ($rs->isEmpty()) {
            return null;
        }

        $content = '';
        $i = 0;
        while($rs->fetch()) {
            $user = $core->con->select(
                "SELECT * FROM " . $core->prefix . "user WHERE user_id='" . $rs->user_id . "' "
            );

            $author = dcUtils::getUserCN(
                $user->user_id,
                $user->user_name,
                $user->user_firstname,
                $user->user_displayname
            );

            if (empty($author)) {
                continue;
            }

            $i++;
            $rank = '<span class="topentries-rank">' . $i . '</span>';

            $core->blog->settings->addNamespace('authormode');
            if ($core->blog->settings->authormode->authormode_active) {
                $author = '<a href="' .
                    $core->blog->url . $core->url->getBase("author") . '/' . $user->user_id . '" ' .
                    'title="' . __('Author posts') . '">' . $author . '</a>';
            }
            elseif ($user->user_url) {
                $author = '<a href="' . $user->user_url . '" title="' .
                    __('Author link') . '">' . $author . '</a>';
            }
            $author = '<span class="topentries-author">' . $author . '</span>';

            if ($rs->count == 0) {
                $count = __('no entries');
            } else {
                $count = sprintf(__('one entry', '%s entries', $rs->count), $rs->count);
            }

            $content .= sprintf(
                '<li>%s</li>', 
                str_replace(
                    ['%rank%', '%author%', '%count%'],
                    [$rank, $author, $count],
                    $w->text
                )
            );
        }

        if ($i < 1) {
            return null;
        }

        return $w->renderDiv(
            $w->content_only,
            'topentries ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
                sprintf('<ul>%s</ul>', $content)
        );
    }

    private static function period($t, $p)
    {
        $pat = '%Y-%m-%d %H:%M:%S';
        switch($p) {
            case 'day':
                return
                "AND $t > TIMESTAMP '" . dt::str($pat, time() - 3600*24) . "' ";
            break;

            case 'week':
                return 
                "AND $t > TIMESTAMP '" . dt::str($pat, time() - 3600*24*7) . "' ";
            break;

            case 'month':
                return
                "AND $t > TIMESTAMP '" . dt::str($pat, time() - 3600*24*30) . "' ";
            break;

            case 'year':
                return
                "AND $t > TIMESTAMP '" . dt::str($pat, time() - 3600*24*30*12) . "' ";
            break;
        }

        return '';
    }
}