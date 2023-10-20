<?php

declare(strict_types=1);

namespace Dotclear\Plugin\topWriter;

use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsStack;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * @brief       topWriter widgets class.
 * @ingroup     topWriter
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Widgets
{
    public static function initWidgets(WidgetsStack $w): void
    {
        #Top comments widget
        $w
            ->create(
                'topcom',
                __('Top writer: comments'),
                self::topComWidget(...),
                null,
                __('List users who write more comments')
            )
            ->addTitle(__('Top comments'))
            ->setting(
                'text',
                __('Text:') . ' (%rank%, %author%, %count%)',
                '%author% (%count%)',
                'text'
            )
            ->setting(
                'period',
                __('Period:'),
                'year',
                'combo',
                Utils::periods()
            )
            ->setting(
                'sort',
                __('Sort:'),
                'desc',
                'combo',
                [
                    __('Ascending')  => 'asc',
                    __('Descending') => 'desc',
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
                __('Top writer: entries'),
                self::topPostWidget(...),
                null,
                __('List users who write more posts')
            )
            ->addTitle(__('Top entries'))
            ->setting(
                'text',
                __('Text:') . ' (%rank%, %author%, %count%)',
                '%author% (%count%)',
                'text'
            )
            ->setting(
                'period',
                __('Period:'),
                'year',
                'combo',
                Utils::periods()
            )
            ->setting(
                'sort',
                __('Sort:'),
                'desc',
                'combo',
                [
                    __('Ascending')  => 'asc',
                    __('Descending') => 'desc',
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

    public static function topComWidget(WidgetsElement $w): string
    {
        if ($w->offline || !$w->checkHomeOnly(App::url()->type)) {
            return '';
        }

        $lines = Utils::comments($w->period, (int) $w->limit, $w->sort == 'desc', (bool) $w->exclude);
        if (empty($lines)) {
            return '';
        }

        return $w->renderDiv(
            (bool) $w->content_only,
            'topcomments ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '') .
            sprintf('<ul>%s</ul>', implode('', self::lines($lines, 'comments', $w->text)))
        );
    }

    public static function topPostWidget(WidgetsElement $w): string
    {
        if ($w->offline || !$w->checkHomeOnly(App::url()->type)) {
            return '';
        }

        $lines = Utils::posts($w->period, (int) $w->limit, $w->sort == 'desc');
        if (empty($lines)) {
            return '';
        }

        return $w->renderDiv(
            (bool) $w->content_only,
            'topentries ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(Html::escapeHTML($w->title)) : '') .
            sprintf('<ul>%s</ul>', implode('', self::lines($lines, 'posts', $w->text)))
        );
    }

    private static function lines(array $lines, string $id, string $text): array
    {
        $li = [];
        foreach ($lines as $k => $line) {
            $rank   = '<span class="top' . $id . '-rank">' . $k . '</span>';
            $author = '<span class="top' . $id . '-author">' . (empty($line['author_link']) ? $line['author'] : $line['author_link']) . '</span>';
            $li[]   = sprintf(
                '<li>%s</li>',
                str_replace(
                    ['%rank%', '%author%', '%count%'],
                    [$rank, $author, $line['count']],
                    $text
                )
            );
        }

        return $li;
    }
}
