<?php

/*
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace MediaWiki\Extension\TitleIcon;

use Config;
use HtmlArmor;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Search\Hook\ShowSearchHitTitleHook;
use MediaWiki\Title\Title;
use OutputPage;
use SearchResult;
use Skin;
use SpecialSearch;

class MainHookHandler implements BeforePageDisplayHook, ShowSearchHitTitleHook {
	/** @var IconManager */
	private $iconManager;

	/** @var Config */
	private $config;

	/**
	 * @param IconManager $iconManager
	 * @param Config $config
	 */
	public function __construct(
		IconManager $iconManager,
		Config $config
	) {
		$this->iconManager = $iconManager;
		$this->config = $config;
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		if ( !$this->config->get( "TitleIcon_EnableIconInPageTitle" ) ) {
			return;
		}

		$title = $skin->getTitle();
		if ( $title->isSpecialPage() ) {
			return;
		}

		$html = $this->iconManager->getHTML( $this->iconManager->getIcons( $title ) );
		if ( $html !== '' ) {
			$out->addJsConfigVars( 'TitleIconHTML', $html );
			$out->addJsConfigVars( 'TitleIconSelector', $this->config->get( 'TitleIcon_CSSSelector' ) );
			$out->addModules( 'ext.TitleIcon' );
		}
	}

	/**
	 * @param Title &$title
	 * @param HtmlArmor|string|null &$titleSnippet
	 * @param SearchResult $result
	 * @param array $terms
	 * @param SpecialSearch $specialSearch
	 * @param string[] &$query
	 * @param string[] &$attributes
	 * @return bool|void
	 */
	public function onShowSearchHitTitle(
		&$title, &$titleSnippet, $result, $terms, $specialSearch, &$query, &$attributes
	) {
		if ( !$this->config->get( "TitleIcon_EnableIconInSearchTitle" ) ) {
			return;
		}

		$iconHtml = $this->iconManager->getHTML( $this->iconManager->getIcons( $title ) );
		if ( $iconHtml === '' ) {
			return;
		}

		// The snippet is rendered inside the link to the search hit, so the icons must not be links themselves.
		$iconHtml = preg_replace( '#</?a\b[^>]*>#i', '', $iconHtml );
		if ( $titleSnippet === null ) {
			$titleHtml = htmlspecialchars( $title->getPrefixedText() );
		} else {
			$titleHtml = HtmlArmor::getHtml( $titleSnippet );
		}
		$titleSnippet = new HtmlArmor( $iconHtml . $titleHtml );
	}
}
