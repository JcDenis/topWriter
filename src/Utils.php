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
    return;
}

class topWriter
{
    public static function posts(string $period, int $limit, bool $sort_desc = true)
    {
        $req = 'SELECT COUNT(*) AS count, U.user_id ' .
            'FROM ' . dcCore::app()->prefix . 'post P ' .
            'INNER JOIN ' . dcCore::app()->prefix . 'user U ON U.user_id = P.user_id ' .
            "WHERE blog_id='" . dcCore::app()->con->escape(dcCore::app()->blog->id) . "' " .
            'AND post_status=1 AND user_status=1 ' .
            self::period('post_dt', $period) .
            'GROUP BY U.user_id ' .
            'ORDER BY count ' . ($sort_desc ? 'DESC' : 'ASC') . ' , U.user_id ASC ' .
            dcCore::app()->con->limit(abs((int) $limit));

        $rs = dcCore::app()->con->select($req);
        if ($rs->isEmpty()) {
            return null;
        }

        dcCore::app()->blog->settings->addNamespace('authormode');

        $res = [];
        $i   = 0;
        while ($rs->fetch()) {
            $user = dcCore::app()->con->select(
                'SELECT * FROM ' . dcCore::app()->prefix . "user WHERE user_id='" . $rs->user_id . "' "
            );
            if ($user->isEmpty()) {
                continue;
            }

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
            if (dcCore::app()->blog->settings->authormode->authormode_active) {
                $res[$i]['author_link'] = '<a href="' .
                    dcCore::app()->blog->url . dcCore::app()->url->getBase('author') . '/' . $user->user_id . '" ' .
                    'title="' . __('Author posts') . '">' . $author . '</a>';
            } elseif ($user->user_url) {
                $res[$i]['author_link'] = '<a href="' . $user->user_url . '" title="' .
                    __('Author link') . '">' . $author . '</a>';
            }
            $res[$i]['author'] = $author;

            if ($rs->count == 0) {
                $res[$i]['count'] = __('no entries');
            } else {
                $res[$i]['count'] = sprintf(__('one entry', '%s entries', $rs->count), $rs->count);
            }
        }

        if (!$i) {
            return null;
        }

        return $res;
    }

    public static function comments(string $period, int $limit, bool $sort_desc = true, $exclude = false)
    {
        $req = 'SELECT COUNT(*) AS count, comment_email ' .
        'FROM ' . dcCore::app()->prefix . 'post P,  ' . dcCore::app()->prefix . 'comment C ' .
        'WHERE P.post_id=C.post_id ' .
        "AND blog_id='" . dcCore::app()->con->escape(dcCore::app()->blog->id) . "' " .
        'AND post_status=1 AND comment_status=1 ' .
        self::period('comment_dt', $period);

        if ($exclude) {
            $req .= 'AND comment_email NOT IN (' .
            ' SELECT U.user_email ' .
            ' FROM ' . dcCore::app()->prefix . 'user U' .
            ' INNER JOIN ' . dcCore::app()->prefix . 'post P ON P.user_id = U.user_id ' .
            " WHERE blog_id='" . dcCore::app()->con->escape(dcCore::app()->blog->id) . "' " .
            ' GROUP BY U.user_email) ';
        }

        $req .= 'GROUP BY comment_email ' .
        'ORDER BY count ' . ($sort_desc ? 'DESC' : 'ASC') . ' ' .
        dcCore::app()->con->limit(abs((int) $limit));

        $rs = dcCore::app()->con->select($req);
        if ($rs->isEmpty()) {
            return null;
        }

        $res = [];
        $i   = 0;
        while ($rs->fetch()) {
            $user = dcCore::app()->con->select(
                'SELECT * FROM ' . dcCore::app()->prefix . 'comment ' .
                "WHERE comment_email='" . $rs->comment_email . "' " .
                'ORDER BY comment_dt DESC'
            );

            if (!$user->comment_author) {
                continue;
            }

            $i++;

            if ($user->comment_site) {
                $res[$i]['author_link'] = '<a href="' . $user->comment_site . '" title="' .
                    __('Author link') . '">' . $user->comment_author . '</a>';
            }
            $res[$i]['author'] = $user->comment_author;

            if ($rs->count == 0) {
                $res[$i]['count'] = __('no comments');
            } else {
                $res[$i]['count'] = sprintf(__('one comment', '%s comments', $rs->count), $rs->count);
            }
        }

        if (!$i) {
            return null;
        }

        return $res;
    }

    private static function period(string $field, string $period): string
    {
        $pattern = '%Y-%m-%d %H:%M:%S';
        $time    = 0;
        switch ($period) {
            case 'day':
                $time = 3600 * 24;

                break;

            case 'week':
                $time = 3600 * 24 * 7;

                break;

            case 'month':
                $time = 3600 * 24 * 30;

                break;

            case 'year':
                $time = 3600 * 24 * 30 * 12;

                break;

            default:
                return '';
        }

        return "AND $field > TIMESTAMP '" . dt::str($pattern, time() - $time) . "' ";
    }

    public static function periods()
    {
        return [
            __('last day')      => 'day',
            __('last week')     => 'week',
            __('last month')    => 'month',
            __('last year')     => 'year',
            __('from begining') => '',
        ];
    }
}