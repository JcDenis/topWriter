<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of topWriter, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2015 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {

	return null;
}

$core->addBehavior('initWidgets', array('topWriterWidget', 'init'));

class topWriterWidget
{
	public static function init($w)
	{
    #Top comments widget
		$w->create(
			'topcom',
			__('Top Writer: top comments'),
			array('topWriterWidget', 'topCom'),
			null,
			__('List users who write more comments')
		);
		$w->topcom->setting(
			'title',
			__('Title:'),
			__('Top comments'),
			'text'
		);
		$w->topcom->setting(
			'text',
			__('Text:'),
			'%author% (%count%)',
			'text'
		);
		$w->topcom->setting(
			'period',
			__('Period:'),
			'year',
			'combo',
			array(
				__('day')			=> 'day',
				__('week')		=> 'week',
				__('month')		=> 'month',
				__('year')		=> 'year',
				__('from begining')	=> ''
			)
		);
		$w->topcom->setting(
			'sort',
			__('Sort:'),
			'desc',
			'combo',
			array(
				__('Ascending')	=> 'asc',
				__('Descending')	=> 'desc'
			)
		);
		$w->topcom->setting(
			'limit',
			__('Limit:'),
			'10',
			'text'
		);
		$w->topcom->setting(
			'exclude',
			__('Exclude post writer from list'),
			0,
			'check'
		);
		$w->topcom->setting(
			'homeonly',
			__('Display on:'),
			0,
			'combo',
			array(
				__('All pages')			=> 0,
				__('Home page only')		=> 1,
				__('Except on home page')	=> 2
			)
		);
    $w->topcom->setting('content_only',__('Content only'),0,'check');
    $w->topcom->setting('class',__('CSS class:'),'');
		$w->topcom->setting('offline',__('Offline'),0,'check');

    #Top entries widget
		$w->create(
			'toppost',
			__('Top Writer: top entries'),
			array('topWriterWidget', 'topPost'),
			null,
			__('List users who write more posts')
		);
		$w->toppost->setting(
			'title',
			__('Title:'),
			__('Top entries'),
			'text'
		);
		$w->toppost->setting(
			'text',
			__('Text:'),
			'%author% (%count%)',
			'text'
		);
		$w->toppost->setting(
			'period',
			__('Period:'),
			'year',
			'combo',
			array(
				__('day')			=> 'day',
				__('week')		=> 'week',
				__('month')		=> 'month',
				__('year')		=> 'year',
				__('from begining')	=> ''
			)
		);
		$w->toppost->setting(
			'sort',
			__('Sort:'),'desc',
			'combo',
			array(
				__('Ascending')	=> 'asc',
				__('Descending')	=> 'desc'
			)
		);
		$w->toppost->setting(
			'limit',
			__('Limit:'),
			'10',
			'text'
		);
		$w->toppost->setting(
			'homeonly',
			__('Display on:'),
			0,
			'combo',
			array(
				__('All pages')			=> 0,
				__('Home page only')		=> 1,
				__('Except on home page')	=> 2
			)
		);
    $w->toppost->setting('content_only',__('Content only'),0,'check');
    $w->toppost->setting('class',__('CSS class:'),'');
		$w->toppost->setting('offline',__('Offline'),0,'check');
	}

	public static function topCom($w)
	{
		global $core;

		if ($w->offline)
			return;

		if ($w->homeonly == 1 && $core->url->type != 'default'
		 || $w->homeonly == 2 && $core->url->type == 'default'
		) {
			return null;
		}

		$req =
		'SELECT COUNT(*) AS count, comment_email '.
		"FROM ".$core->prefix."post P,  ".$core->prefix."comment C ".
		'WHERE P.post_id=C.post_id '.
		"AND blog_id='".$core->con->escape($core->blog->id)."' ".
		'AND post_status=1 AND comment_status=1 '.
		self::period('comment_dt',$w->period);

		if ($w->exclude) {
			$req .= 
			'AND comment_email NOT IN ('.
			' SELECT U.user_email '.
			' FROM '.$core->prefix.'user U'.
			' INNER JOIN '.$core->prefix.'post P ON P.user_id = U.user_id '.
			" WHERE blog_id='".$core->con->escape($core->blog->id)."' ".
			' GROUP BY U.user_email) ';
		}

		$req .=
		'GROUP BY comment_email '.
		'ORDER BY count '.($w->sort == 'asc' ? 'ASC' : 'DESC').' '.
		$core->con->limit(abs((integer) $w->limit));

		$rs = $core->con->select($req);

		if ($rs->isEmpty()) {

			return null;
		}

		$content = '';
		$i = 0;
		while($rs->fetch()) {
			$user = $core->con->select(
				"SELECT * FROM ".$core->prefix."comment ".
				"WHERE comment_email='".$rs->comment_email."' ".
				'ORDER BY comment_dt DESC'
			);

			if (!$user->comment_author) {
				continue;
			}

			$i++;
			$rank = '<span class="topcomments-rank">'.$i.'</span>';

			if ($user->comment_site) {
				$author = '<a href="'.$user->comment_site.'" title="'.
					__('Author link').'">'.$user->comment_author.'</a>';
			}
			else {
				$author = $user->comment_author;
			}
			$author = '<span class="topcomments-author">'.$author.'</span>';

			if ($rs->count == 0) {
				$count = __('no comment');
			}
			else {
				$count = sprintf(__('one comment', '%s comments', $rs->count), $rs->count);
			}

			$content .= '<li>'.str_replace(
				array('%rank%', '%author%', '%count%'),
				array($rank, $author, $count),
				$w->text
			).'</li>';
		}

		if ($i < 1) {

			return null;
		}

		$res =
		($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '').
		'<ul>'.$content.'</ul>';

		return $w->renderDiv($w->content_only,'topcomments '.$w->class,'',$res);
	}
	
	public static function topPost($w)
	{
		global $core;

		if ($w->offline)
			return;

		if ($w->homeonly == 1 && $core->url->type != 'default'
		 || $w->homeonly == 2 && $core->url->type == 'default'
		) {
			return null;
		}

		$rs = $core->con->select(
		'SELECT COUNT(*) AS count, U.user_id '.
		"FROM ".$core->prefix."post P ".
		'INNER JOIN '.$core->prefix.'user U ON U.user_id = P.user_id '.
		"WHERE blog_id='".$core->con->escape($core->blog->id)."' ".
		'AND post_status=1 AND user_status=1 '.
		self::period('post_dt',$w->period).
		'GROUP BY U.user_id '.
		'ORDER BY count '.($w->sort == 'asc' ? 'ASC' : 'DESC').', U.user_id ASC '.
		$core->con->limit(abs((integer) $w->limit)));

		if ($rs->isEmpty()) {

			return null;
		}

		$content = '';
		$i = 0;
		while($rs->fetch()) {
			$user = $core->con->select(
				"SELECT * FROM ".$core->prefix."user WHERE user_id='".$rs->user_id."' "
			);

			$author = dcUtils::getUserCN($user->user_id,$user->user_name,
				$user->user_firstname,$user->user_displayname);

			if (empty($author)) {
				continue;
			}

			$i++;
			$rank = '<span class="topentries-rank">'.$i.'</span>';

			$core->blog->settings->addNamespace('authormode');
			if ($core->blog->settings->authormode->authormode_active) {
				$author = '<a href="'.
					$core->blog->url.$core->url->getBase("author").'/'.$user->user_id.'" '.
					'title="'.__('Author posts').'">'.$author.'</a>';
			}
			elseif ($user->user_url) {
				$author = '<a href="'.$user->user_url.'" title="'.
					__('Author link').'">'.$author.'</a>';
			}
			$author = '<span class="topentries-author">'.$author.'</span>';

			if ($rs->count == 0) {
				$count = __('no post');
			}
			else {
				$count = sprintf(__('one post', '%s posts', $rs->count), $rs->count);
			}

			$content .= '<li>'.str_replace(
				array('%rank%', '%author%', '%count%'),
				array($rank, $author, $count),
				$w->text
			).'</li>';
		}

		if ($i < 1) {

			return null;
		}

		$res =
		($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '').
		'<ul>'.$content.'</ul>';

		return $w->renderDiv($w->content_only,'topentries '.$w->class,'',$res);
	}

	private static function period($t,$p)
	{
		$pat = '%Y-%m-%d %H:%M:%S';
		switch($p) {
			case 'day':

			return
			"AND $t > TIMESTAMP '".dt::str($pat, time() - 3600*24)."' ";
			break;
			
			case 'week':

			return 
			"AND $t > TIMESTAMP '".dt::str($pat, time() - 3600*24*7)."' ";
			break;

			case 'month':

			return
			"AND $t > TIMESTAMP '".dt::str($pat, time() - 3600*24*30)."' ";
			break;

			case 'year':

			return
			"AND $t > TIMESTAMP '".dt::str($pat, time() - 3600*24*30*12)."' ";
			break;
		}

		return '';
	}
}