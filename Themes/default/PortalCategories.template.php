<?php

/**
 * @package SimplePortal
 *
 * @author SimplePortal Team
 * @copyright 2014 SimplePortal Team
 * @license BSD 3-clause
 *
 * @version 2.4
 */

function template_view_categories()
{
	global $context, $txt;

	echo '
	<div id="sp_view_categories">
		<h3 class="category_header">
			', $context['page_title'], '
		</h3>';

	if (empty($context['categories']))
	{
		echo '
		<div class="windowbg2">
			<div class="sp_content_padding">', $txt['error_sp_no_categories'], '</div>
		</div>';
	}

	foreach ($context['categories'] as $category)
	{
		echo '
		<div class="windowbg2">
			<div class="sp_content_padding">
				<h4>', $category['link'], '</h4>
				<p>', $category['description'], '</p>
				<span>', sprintf($category['articles'] == 1 ? $txt['sp_has_article'] : $txt['sp_has_articles'], $category['articles']) ,'</span>
			</div>
		</div>';
	}

	echo '
	</div>';
}

function template_view_category()
{
	global $context, $txt;

	echo '
	<div id="sp_view_category">
		<h3 class="category_header">
			', $context['page_title'], '
		</h3>';

	if (empty($context['articles']))
	{
		echo '
		<div class="windowbg2">
			<div class="sp_content_padding">', $txt['error_sp_no_articles'], '</div>
		</div>';
	}

	foreach ($context['articles'] as $article)
	{
		echo '
		<div class="windowbg2">
			<div class="sp_content_padding">
				<h4>', $article['link'], '</h4>
				<span>', sprintf($txt['sp_posted_in_on_by'], $article['category']['link'], $article['date'], $article['author']['link']), '</span>
				<p>', $article['preview'], '<a href="', $article['href'], '">...</a></p>
				<span>', sprintf($article['views'] == 1 ? $txt['sp_viewed_time'] : $txt['sp_viewed_times'], $article['views']) ,', ', sprintf($article['comments'] == 1 ? $txt['sp_commented_on_time'] : $txt['sp_commented_on_times'], $article['comments']), '</span>
			</div>
		</div>';
	}

	echo '
	</div>';
}