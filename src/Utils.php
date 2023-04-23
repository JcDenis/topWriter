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

use dcAuth;
use dcBlog;
use dcCore;
use dcUtils;
use Dotclear\Helper\Date;

class Utils
{
    public static function posts(string $period, int $limit, bool $sort_desc = true): array
    {
        // nullsafe
        if (is_null(dcCore::app()->blog)) {
            return [];
        }

        $req = 'SELECT COUNT(*) AS count, U.user_id ' .
            'FROM ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME . ' P ' .
            'INNER JOIN ' . dcCore::app()->prefix . dcAuth::USER_TABLE_NAME . ' U ON U.user_id = P.user_id ' .
            "WHERE blog_id='" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog->id) . "' " .
            'AND post_status=1 AND user_status=1 ' .
            self::period('post_dt', $period) .
            'GROUP BY U.user_id ' .
            'ORDER BY count ' . ($sort_desc ? 'DESC' : 'ASC') . ' , U.user_id ASC ' .
            dcCore::app()->con->limit(abs((int) $limit));

        $rs = dcCore::app()->con->select($req);
        if ($rs->isEmpty()) {
            return [];
        }

        $res = [];
        $i   = 0;
        while ($rs->fetch()) {
            $user = dcCore::app()->con->select(
                'SELECT * FROM ' . dcCore::app()->prefix . dcAuth::USER_TABLE_NAME . " WHERE user_id='" . $rs->f('user_id') . "' "
            );
            if ($user->isEmpty()) {
                continue;
            }

            $author = dcUtils::getUserCN(
                $user->f('user_id'),
                $user->f('user_name'),
                $user->f('user_firstname'),
                $user->f('user_displayname')
            );
            if (empty($author)) {
                continue;
            }

            $i++;
            if (dcCore::app()->blog->settings->get('authormode')->get('authormode_active')) {
                $res[$i]['author_link'] = '<a href="' .
                    dcCore::app()->blog->url . dcCore::app()->url->getBase('author') . '/' . $user->f('user_id') . '" ' .
                    'title="' . __('Author posts') . '">' . $author . '</a>';
            } elseif ($user->f('user_url')) {
                $res[$i]['author_link'] = '<a href="' . $user->f('user_url') . '" title="' .
                    __('Author link') . '">' . $author . '</a>';
            }
            $res[$i]['author'] = $author;

            if ((int) $rs->f('count') == 0) {
                $res[$i]['count'] = __('no entries');
            } else {
                $res[$i]['count'] = sprintf(__('one entry', '%s entries', (int) $rs->f('count')), $rs->f('count'));
            }
        }

        return $i ? $res : [];
    }

    public static function comments(string $period, int $limit, bool $sort_desc = true, bool $exclude = false): array
    {
        // nullsafe
        if (is_null(dcCore::app()->blog)) {
            return [];
        }

        $req = 'SELECT COUNT(*) AS count, comment_email ' .
        'FROM ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME . ' P,  ' . dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME . ' C ' .
        'WHERE P.post_id=C.post_id ' .
        "AND blog_id='" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog->id) . "' " .
        'AND post_status=1 AND comment_status=1 ' .
        self::period('comment_dt', $period);

        if ($exclude) {
            $req .= 'AND comment_email NOT IN (' .
            ' SELECT U.user_email ' .
            ' FROM ' . dcCore::app()->prefix . dcAuth::USER_TABLE_NAME . ' U' .
            ' INNER JOIN ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME . ' P ON P.user_id = U.user_id ' .
            " WHERE blog_id='" . dcCore::app()->con->escapeStr((string) dcCore::app()->blog->id) . "' " .
            ' GROUP BY U.user_email) ';
        }

        $req .= 'GROUP BY comment_email ' .
        'ORDER BY count ' . ($sort_desc ? 'DESC' : 'ASC') . ' ' .
        dcCore::app()->con->limit(abs((int) $limit));

        $rs = dcCore::app()->con->select($req);
        if ($rs->isEmpty()) {
            return [];
        }

        $res = [];
        $i   = 0;
        while ($rs->fetch()) {
            $user = dcCore::app()->con->select(
                'SELECT * FROM ' . dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME . ' ' .
                "WHERE comment_email='" . $rs->f('comment_email') . "' " .
                'ORDER BY comment_dt DESC'
            );

            if (!$user->f('comment_author')) {
                continue;
            }

            $i++;

            if ($user->f('comment_site')) {
                $res[$i]['author_link'] = '<a href="' . $user->f('comment_site') . '" title="' .
                    __('Author link') . '">' . $user->f('comment_author') . '</a>';
            }
            $res[$i]['author'] = $user->f('comment_author');

            if ((int) $rs->f('count') == 0) {
                $res[$i]['count'] = __('no comments');
            } else {
                $res[$i]['count'] = sprintf(__('one comment', '%s comments', (int) $rs->f('count')), $rs->f('count'));
            }
        }

        return $i ? $res : [];
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

        return "AND $field > TIMESTAMP '" . Date::str($pattern, time() - $time) . "' ";
    }

    public static function periods(): array
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
