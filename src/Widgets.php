<?php

declare(strict_types=1);

namespace Dotclear\Plugin\topWriter;

use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
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
        if ($w->isOffline() || !$w->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $lines = Utils::comments(is_string($period = $w->get('period')) ? $period : '', is_numeric($limit = $w->get('limit')) ? (int) $limit : 10, $w->get('sort') === 'desc', (bool) $w->get('exclude'));
        if (empty($lines)) {
            return '';
        }

        return $w->renderDiv(
            (bool) $w->get('content_only'),
            'topcomments ' . (is_string($class = $w->get('class')) ? $class : ''),
            '',
            (is_string($title = $w->get('title')) && $title !== '' ? $w->renderTitle(Html::escapeHTML($title)) : '') .
            (new Ul())->items(self::lines($lines, 'comments', is_string($text = $w->get('text')) ? $text : ''))->render()
        );
    }

    public static function topPostWidget(WidgetsElement $w): string
    {
        if ($w->isOffline() || !$w->checkHomeOnly(App::url()->getType())) {
            return '';
        }

        $lines = Utils::posts(is_string($period = $w->get('period')) ? $period : '', is_numeric($limit = $w->get('limit')) ? (int) $limit : 10, $w->get('sort') === 'desc');
        if (empty($lines)) {
            return '';
        }

        return $w->renderDiv(
            (bool) $w->get('content_only'),
            'topentries ' . (is_string($class = $w->get('class')) ? $class : ''),
            '',
            (is_string($title = $w->get('title')) && $title !== '' ? $w->renderTitle(Html::escapeHTML($title)) : '') .
            (new Ul())->items(self::lines($lines, 'posts', is_string($text = $w->get('text')) ? $text : ''))->render()
        );
    }

    /**
     * @param   array<int, array<string, string>>   $lines
     * @return  array<int, Li>
     */
    private static function lines(array $lines, string $id, string $text): array
    {
        $li = [];
        foreach ($lines as $k => $line) {
            $li[] = (new Li())->items([
                    (new Text(null, str_replace(
                        [
                            '%rank%',
                            '%author%',
                            '%count%'
                        ],
                        [
                            (new Text('span', (string) $k))->class('top' . $id . '-rank')->render(),
                            (new Text('span', empty($line['author_link']) ? $line['author'] : $line['author_link']))->class('top' . $id . '-author')->render(),
                            $line['count']
                        ],
                        $text
                    )))
                ]);
        }

        return $li;
    }
}
