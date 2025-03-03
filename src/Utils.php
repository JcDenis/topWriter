<?php

declare(strict_types=1);

namespace Dotclear\Plugin\topWriter;

use Dotclear\App;
use Dotclear\Database\Statement\{
    JoinStatement,
    SelectStatement
};
use Dotclear\Helper\Date;

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
            ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
            ->columns([
                $sql->count('*', 'count'),
                'U.user_id',
            ])
            ->join(
                (new JoinStatement())
                    ->inner()
                    ->from($sql->as(App::con()->prefix() . App::auth()::USER_TABLE_NAME, 'U'))
                    ->on('U.user_id = P.user_id')
                    ->statement()
            )
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('post_status = ' . App::blog()::POST_PUBLISHED)
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
                ->from(App::con()->prefix() . App::auth()::USER_TABLE_NAME)
                ->column('*')
                ->where('user_id = ' . $sql->quote($rs->f('user_id')))
                ->select();

            if (is_null($user) || $user->isEmpty()) {
                continue;
            }

            $author = App::users()->getUserCN(
                $user->f('user_id'),
                $user->f('user_name'),
                $user->f('user_firstname'),
                $user->f('user_displayname')
            );
            if (empty($author)) {
                continue;
            }

            $i++;
            if (App::blog()->settings()->get('authormode')->get('authormode_active')) {
                $res[$i]['author_link'] = '<a href="' .
                    App::blog()->url() . App::url()->getBase('author') . '/' . $user->f('user_id') . '" ' .
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
            ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
            ->from($sql->as(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME, 'C'))
            ->columns([
                $sql->count('*', 'count'),
                'comment_email',
            ])
            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
            ->and('P.post_id = C.post_id')
            ->and('post_status = ' . App::blog()::POST_PUBLISHED)
            ->and('comment_status = ' . App::blog()::COMMENT_PUBLISHED)
            ->group('comment_email')
            ->order('count ' . ($sort_desc ? 'DESC' : 'ASC'))
            ->limit(abs((int) $limit))
        ;

        self::period($sql, $period, 'comment_dt');

        if ($exclude) {
            $sql->and('comment_email NOT IN (' .
                (new SelectStatement())
                    ->from($sql->as(App::con()->prefix() . App::auth()::USER_TABLE_NAME, 'U'))
                    ->column('U.user_email')
                    ->join(
                        (new JoinStatement())
                            ->inner()
                            ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
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
                ->from(App::con()->prefix() . App::blog()::COMMENT_TABLE_NAME)
                ->column('*')
                ->where('comment_email = ' . $sql->quote($rs->f('comment_email')))
                ->order('comment_dt DESC')
                ->select();

            if (is_null($user) || !$user->f('comment_author')) {
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

        $sql->and($field . ' > TIMESTAMP ' . $sql->quote(Date::str($pattern, time() - $time)));
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
