<?php

declare(strict_types=1);

namespace Dotclear\Plugin\topWriter;

use DateTimeImmutable;
use DateTimeZone;
use Dotclear\App;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       topWriter utils class.
 * @ingroup     topWriter
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Utils
{
    /**
     * Most active entries writers.
     *
     * Ties are ordered by the most recent entry, then by user ID.
     *
     * @return  array<int, array<string, string>>
     */
    public static function posts(string $period, int $limit, bool $sort_desc = true): array
    {
        if (!App::blog()->isDefined()) {
            return [];
        }

        // One query with users information
        $sql = new SelectStatement();
        $sql
            ->from($sql->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
            ->columns([
                $sql->count('*', 'count'),
                $sql->max('P.post_dt', 'last_dt'),
                'U.user_id',
                'U.user_name',
                'U.user_firstname',
                'U.user_displayname',
                'U.user_url',
            ])
            ->join(
                (new JoinStatement())
                    ->inner()
                    ->from($sql->as(App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME, 'U'))
                    ->on('U.user_id = P.user_id')
                    ->statement()
            )
            ->where('P.blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('post_status = ' . App::status()->post()::PUBLISHED)
            ->and('user_status = 1')
            ->group(['U.user_id', 'U.user_name', 'U.user_firstname', 'U.user_displayname', 'U.user_url'])
            ->order('count ' . ($sort_desc ? 'DESC' : 'ASC') . ', last_dt DESC, U.user_id ASC')
            ->limit(abs($limit));

        self::period($sql, $period, 'P.post_dt');

        $rs = $sql->select();
        if (is_null($rs) || $rs->isEmpty()) {
            return [];
        }

        $authormode = (bool) App::blog()->settings()->get('authormode')->get('authormode_active');

        $res = [];
        $i   = 0;
        while ($rs->fetch()) {
            $author = App::users()->getUserCN(
                $rs->strField('user_id'),
                $rs->strField('user_name'),
                $rs->strField('user_firstname'),
                $rs->strField('user_displayname')
            );
            if ($author === '') {
                continue;
            }

            $i++;
            if ($authormode) {
                $res[$i]['author_link'] = '<a href="' .
                    App::blog()->url() . App::url()->getBase('author') . '/' . rawurlencode($rs->strField('user_id')) . '" ' .
                    'title="' . __('Author posts') . '">' . Html::escapeHTML($author) . '</a>';
            } elseif ($rs->strField('user_url') !== '') {
                $res[$i]['author_link'] = '<a href="' . Html::escapeURL($rs->strField('user_url')) . '" title="' .
                    __('Author link') . '">' . Html::escapeHTML($author) . '</a>';
            }
            $res[$i]['author'] = Html::escapeHTML($author);

            $count            = $rs->intField('count');
            $res[$i]['count'] = $count === 0 ? __('no entries') : sprintf(__('one entry', '%s entries', $count), $count);
        }

        return $res;
    }

    /**
     * Most active commentators.
     *
     * Commentators are grouped by email (whatever the case), or by name when they have no email.
     * Ties are ordered by the most recent comment.
     *
     * @return  array<int, array<string, string>>
     */
    public static function comments(string $period, int $limit, bool $sort_desc = true, bool $exclude = false): array
    {
        if (!App::blog()->isDefined()) {
            return [];
        }

        $key = "CASE WHEN C.comment_email IS NOT NULL AND C.comment_email <> '' THEN LOWER(C.comment_email) ELSE C.comment_author END";

        $sql = new SelectStatement();
        $sql
            ->from($sql->as(App::db()->con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->columns([
                $sql->count('*', 'count'),
                $sql->max('C.comment_dt', 'last_dt'),
                $key . ' AS author_key',
            ])
            ->join(
                (new JoinStatement())
                    ->inner()
                    ->from($sql->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->on('P.post_id = C.post_id')
                    ->statement()
            )
            ->where('P.blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('post_status = ' . App::status()->post()::PUBLISHED)
            ->and('comment_status = ' . App::status()->comment()::PUBLISHED)
            ->and('comment_trackback = 0')
            ->group($key)
            ->order('count ' . ($sort_desc ? 'DESC' : 'ASC') . ', last_dt DESC')
            ->limit(abs($limit));

        self::period($sql, $period, 'C.comment_dt');

        if ($exclude) {
            $sql->and("(C.comment_email IS NULL OR C.comment_email = '' OR LOWER(C.comment_email) NOT IN (" .
                (new SelectStatement())
                    ->from($sql->as(App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME, 'U'))
                    ->column('LOWER(U.user_email)')
                    ->join(
                        (new JoinStatement())
                            ->inner()
                            ->from($sql->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'PU'))
                            ->on('PU.user_id = U.user_id')
                            ->statement()
                    )
                    ->where('PU.blog_id = ' . $sql->quote(App::blog()->id()))
                    ->and('U.user_email IS NOT NULL')
                    ->group('U.user_email')
                    ->statement() .
            '))');
        }

        $rs = $sql->select();
        if (is_null($rs) || $rs->isEmpty()) {
            return [];
        }

        $counts = [];
        while ($rs->fetch()) {
            $counts[$rs->strField('author_key')] = $rs->intField('count');
        }

        // Last name and site used by each commentator, on this blog, in one query
        $sql = new SelectStatement();
        $last = $sql
            ->from($sql->as(App::db()->con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->columns([
                $key . ' AS author_key',
                'C.comment_author',
                'C.comment_site',
            ])
            ->join(
                (new JoinStatement())
                    ->inner()
                    ->from($sql->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                    ->on('P.post_id = C.post_id')
                    ->statement()
            )
            ->where('P.blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('comment_status = ' . App::status()->comment()::PUBLISHED)
            ->and($key . ' ' . $sql->in(array_map(strval(...), array_keys($counts))))
            ->order('C.comment_dt DESC')
            ->select();

        $authors = [];
        if (!is_null($last)) {
            while ($last->fetch()) {
                $authors[$last->strField('author_key')] ??= [$last->strField('comment_author'), $last->strField('comment_site')];
            }
        }

        $res = [];
        $i   = 0;
        foreach ($counts as $author_key => $count) {
            [$author, $site] = $authors[(string) $author_key] ?? ['', ''];
            if ($author === '') {
                continue;
            }

            $i++;
            if ($site !== '') {
                $res[$i]['author_link'] = '<a href="' . Html::escapeURL($site) . '" title="' .
                    __('Author link') . '">' . Html::escapeHTML($author) . '</a>';
            }
            $res[$i]['author'] = Html::escapeHTML($author);
            $res[$i]['count']  = $count === 0 ? __('no comments') : sprintf(__('one comment', '%s comments', $count), $count);
        }

        return $res;
    }

    /**
     * Limit to a calendar period (in blog timezone, as entries and comments dates).
     */
    private static function period(SelectStatement $sql, string $period, string $field): void
    {
        $modify = match ($period) {
            'day'   => '-1 day',
            'week'  => '-1 week',
            'month' => '-1 month',
            'year'  => '-1 year',
            default => '',
        };
        if ($modify === '') {
            return;
        }

        try {
            $tz = new DateTimeZone(App::blog()->settings()->get('system')->getStr('blog_timezone') ?: 'UTC');
        } catch (Exception) {
            $tz = new DateTimeZone('UTC');
        }

        $sql->and($field . ' > ' . $sql->quote((new DateTimeImmutable('now', $tz))->modify($modify)->format('Y-m-d H:i:s')));
    }

    /**
     * @return  array<string, string>
     */
    public static function periods(): array
    {
        return [
            __('last day')      => 'day',
            __('last week')     => 'week',
            __('last month')    => 'month',
            __('last year')     => 'year',
            __('from the beginning') => '',
        ];
    }
}
