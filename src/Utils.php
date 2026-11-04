<?php

declare(strict_types=1);

namespace Dotclear\Plugin\topWriter;

use Dotclear\App;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;

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
     * @return  array<int, array<string, string>>
     */
    public static function posts(string $period, int $limit, bool $sort_desc = true): array
    {
        if (!App::blog()->isDefined()) {
            return [];
        }

        $sql = new SelectStatement();
        $sql
            ->from($sql->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
            ->columns([
                $sql->count('*', 'count'),
                'U.user_id',
            ])
            ->join(
                (new JoinStatement())
                    ->inner()
                    ->from($sql->as(App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME, 'U'))
                    ->on('U.user_id = P.user_id')
                    ->statement()
            )
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('post_status = ' . App::status()->post()::PUBLISHED)
            ->and('user_status = 1')
            ->group('U.user_id')
            ->order('count ' . ($sort_desc ? 'DESC' : 'ASC') . ' , U.user_id ASC')
            ->limit(abs((int) $limit));

        self::period($sql, $period, 'post_dt');

        $rs = $sql->select();

        if (is_null($rs) || $rs->isEmpty()) {
            return [];
        }

        $res = [];
        $i   = 0;
        while ($rs->fetch()) {
            $sql  = new SelectStatement();
            $user = $sql
                ->from(App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME)
                ->column('*')
                ->where('user_id = ' . $sql->quote($rs->strField('user_id')))
                ->select();

            if (is_null($user) || $user->isEmpty()) {
                continue;
            }

            $author = App::users()->getUserCN(
                $user->strField('user_id'),
                $user->strField('user_name'),
                $user->strField('user_firstname'),
                $user->strField('user_displayname')
            );
            if (empty($author)) {
                continue;
            }

            $i++;
            if (App::blog()->settings()->get('authormode')->get('authormode_active')) {
                $res[$i]['author_link'] = '<a href="' .
                    App::blog()->url() . App::url()->getBase('author') . '/' . rawurlencode($user->strField('user_id')) . '" ' .
                    'title="' . __('Author posts') . '">' . Html::escapeHTML($author) . '</a>';
            } elseif ($user->strField('user_url') !== '') {
                $res[$i]['author_link'] = '<a href="' . Html::escapeURL($user->strField('user_url')) . '" title="' .
                    __('Author link') . '">' . Html::escapeHTML($author) . '</a>';
            }
            $res[$i]['author'] = Html::escapeHTML($author);

            $count = $rs->intField('count');
            if ($count === 0) {
                $res[$i]['count'] = __('no entries');
            } else {
                $res[$i]['count'] = sprintf(__('one entry', '%s entries', $count), $count);
            }
        }

        return $i ? $res : [];
    }

    /**
     * @return  array<int, array<string, string>>
     */
    public static function comments(string $period, int $limit, bool $sort_desc = true, bool $exclude = false): array
    {
        if (!App::blog()->isDefined()) {
            return [];
        }

        $sql = new SelectStatement();
        $sql
            ->from($sql->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
            ->from($sql->as(App::db()->con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->columns([
                $sql->count('*', 'count'),
                'comment_email',
            ])
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('P.post_id = C.post_id')
            ->and('post_status = ' . App::status()->post()::PUBLISHED)
            ->and('comment_status = ' . App::status()->comment()::PUBLISHED)
            ->group('comment_email')
            ->order('count ' . ($sort_desc ? 'DESC' : 'ASC'))
            ->limit(abs((int) $limit))
        ;

        self::period($sql, $period, 'comment_dt');

        if ($exclude) {
            $sql->and('comment_email NOT IN (' .
                (new SelectStatement())
                    ->from($sql->as(App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME, 'U'))
                    ->column('U.user_email')
                    ->join(
                        (new JoinStatement())
                            ->inner()
                            ->from($sql->as(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                            ->on('P.user_id = U.user_id')
                            ->statement()
                    )
                    ->where('blog_id = ' . $sql->quote(App::blog()->id()))
                    ->group('U.user_email')
                    ->statement() .
            ')');
        }

        $rs = $sql->select();
        if (is_null($rs) || $rs->isEmpty()) {
            return [];
        }

        $res = [];
        $i   = 0;
        while ($rs->fetch()) {
            $sql  = new SelectStatement();
            $user = $sql
                ->from(App::db()->con()->prefix() . App::blog()::COMMENT_TABLE_NAME)
                ->column('*')
                ->where('comment_email = ' . $sql->quote($rs->strField('comment_email')))
                ->order('comment_dt DESC')
                ->select();

            if (is_null($user) || $user->strField('comment_author') === '') {
                continue;
            }

            $i++;

            if ($user->strField('comment_site') !== '') {
                $res[$i]['author_link'] = '<a href="' . Html::escapeURL($user->strField('comment_site')) . '" title="' .
                    __('Author link') . '">' . Html::escapeHTML($user->strField('comment_author')) . '</a>';
            }
            $res[$i]['author'] = Html::escapeHTML($user->strField('comment_author'));

            $count = $rs->intField('count');
            if ($count === 0) {
                $res[$i]['count'] = __('no comments');
            } else {
                $res[$i]['count'] = sprintf(__('one comment', '%s comments', $count), $count);
            }
        }

        return $i ? $res : [];
    }

    private static function period(SelectStatement $sql, string $period, string $field): void
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
                return;
        }

        $sql->and($field . ' > ' . $sql->quote(Date::str($pattern, time() - $time)));
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
            __('from begining') => '',
        ];
    }
}
