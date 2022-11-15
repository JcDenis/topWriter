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

dcCore::app()->addBehavior('initWidgets', ['topWriterWidget', 'init']);

class topWriterWidget
{
    public static function init($w)
    {
        #Top comments widget
        $w
            ->create(
                'topcom',
                __('Top writer: comments'),
                ['topWriterWidget', 'topCom'],
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
                topWriter::periods()
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
                ['topWriterWidget', 'topPost'],
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
                topWriter::periods()
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

    public static function topCom($w)
    {
        if ($w->offline
            || ($w->homeonly == 1 && !dcCore::app()->url->isHome(dcCore::app()->url->type))
            || ($w->homeonly == 2 && dcCore::app()->url->isHome(dcCore::app()->url->type))
        ) {
            return null;
        }

        $lines = topWriter::comments($w->period, $w->limit, $w->sort == 'desc', $w->exclude);
        if (empty($lines)) {
            return null;
        }

        return $w->renderDiv(
            $w->content_only,
            'topcomments ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            sprintf('<ul>%s</ul>', implode('', self::lines($lines, 'comments', $w->text)))
        );
    }

    public static function topPost($w)
    {
        if ($w->offline
            || ($w->homeonly == 1 && !dcCore::app()->url->isHome(dcCore::app()->url->type))
            || ($w->homeonly == 2 && dcCore::app()->url->isHome(dcCore::app()->url->type))
        ) {
            return null;
        }

        $lines = topWriter::posts($w->period, $w->limit, $w->sort == 'desc');
        if (empty($lines)) {
            return null;
        }

        return $w->renderDiv(
            $w->content_only,
            'topentries ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            sprintf('<ul>%s</ul>', implode('', self::lines($lines, 'posts', $w->text)))
        );
    }

    private static function lines($lines, $id, $text)
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
