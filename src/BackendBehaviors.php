<?php

declare(strict_types=1);

namespace Dotclear\Plugin\topWriter;

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Label,
    Li,
    Number,
    Para,
    Select,
    Text,
    Ul
};
use Dotclear\Helper\Html\Html;

/**
 * @brief       topWriter backend behaviors class.
 * @ingroup     topWriter
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class BackendBehaviors
{
    /**
     * @param   ArrayObject<int, ArrayObject<int,string>>    $__dashboard_items
     */
    public static function adminDashboardItemsV2(ArrayObject $__dashboard_items): void
    {
        $pref = self::setDefaultPref();

        foreach(My::TOP_GROUPS as $id) {
            if ($pref[My::id() . $id . 'Items']) {
                $lines = Utils::comments($pref[My::id() . $id . 'Period'], $pref[My::id() . $id . 'Limit']);
                if (!empty($lines)) {
                    $li = [];
                    foreach ($lines as $k => $line) {
                        $li[] = (new Li())
                            ->separator(' ')
                            ->items([
                                (new Text('strong', (string) $k)),
                                (new Text(null, $line['author'] . ' (' . $line['count'] . ')'))
                            ]);
                    }

                    $__dashboard_items[0][] = (new Div(My::id() . $id . 'Items'))
                        ->class(['box', 'small'])
                        ->items([
                            (new Text('h3', Html::escapeHTML($id == 'Posts' ? __('Top writer: entries') : __('Top writer: comments')))),
                            (new Ul())->items($li),
                        ])->render();
                }
            }
        }
    }

    public static function adminDashboardOptionsFormV2(): void
    {
        $pref  = self::setDefaultPref();
        $items = [];

        foreach(My::TOP_GROUPS as $id) {
            $items[] = 
            (new Div())->class('fieldset')->items([
                (new Text('h4', $id == 'Posts' ? __('Top writer: entries') : __('Top writer: comments'))),
                (new Para())->items([
                    (new Checkbox(My::id() . $id . 'Items', $pref[My::id() . $id . 'Items']))->value(1),
                    (new Label(__('Show'), Label::OUTSIDE_LABEL_AFTER))->for(My::id() . $id . 'Items')->class('classic'),
                ]),
                (new Para())->class('field')->items([
                    (new Label(__('Period:'), Label::OUTSIDE_LABEL_BEFORE))->for(My::id() . $id . 'Period'),
                    (new Select(My::id() . $id . 'Period'))->default($pref[My::id() . $id . 'Period'])->items(Utils::periods()),
                ]),
                (new Para())->class('field')->items([
                    (new Label(__('Limit:'), Label::OUTSIDE_LABEL_BEFORE))->for(My::id() . $id . 'Limit'),
                    (new Number(My::id() . $id . 'Limit'))->min(1)->max(20)->value($pref[My::id() . $id . 'Limit']),
                ]),
            ]);
        }

        echo (new Div())->items($items)->render();
    }

    public static function adminAfterDashboardOptionsUpdate(?string $user_id): void
    {
        foreach(My::TOP_GROUPS as $id) {
            App::auth()->prefs()->get('dashboard')->put(
                My::id() . $id . 'Items',
                !empty($_POST[My::id() . $id . 'Items']),
                'boolean'
            );
            App::auth()->prefs()->get('dashboard')->put(
                My::id() . $id . 'Period',
                (string) $_POST[My::id() . $id . 'Period'],
                'string'
            );
            App::auth()->prefs()->get('dashboard')->put(
                My::id() . $id . 'Limit',
                (int) $_POST[My::id() . $id . 'Limit'],
                'integer'
            );
        }
    }

    /**
     * @return  array<string, mixed>
     */
    private static function setDefaultPref(): array
    {
        $res = [];
        foreach(My::TOP_GROUPS as $id) {
            if (!App::auth()->prefs()->get('dashboard')->prefExists(My::id() . $id . 'Items')) {
                App::auth()->prefs()->get('dashboard')->put(
                    My::id() . $id . 'Items',
                    false,
                    'boolean'
                );
            }
            if (!App::auth()->prefs()->get('dashboard')->prefExists(My::id() . $id . 'Period')) {
                App::auth()->prefs()->get('dashboard')->put(
                    My::id() . $id . 'Period',
                    'month',
                    'string'
                );
            }
            if (!App::auth()->prefs()->get('dashboard')->prefExists(My::id() . $id . 'Limit')) {
                App::auth()->prefs()->get('dashboard')->put(
                    My::id() . $id . 'Limit',
                    10,
                    'integer'
                );
            }

            $res[My::id() . $id . 'Items']  = App::auth()->prefs()->get('dashboard')->get(My::id() . $id . 'Items');
            $res[My::id() . $id . 'Period'] = App::auth()->prefs()->get('dashboard')->get(My::id() . $id . 'Period');
            $res[My::id() . $id . 'Limit']  = App::auth()->prefs()->get('dashboard')->get(My::id() . $id . 'Limit') ?? 10;
        }

        return $res;
    }
}
