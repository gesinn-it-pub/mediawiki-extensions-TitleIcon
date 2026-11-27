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

namespace MediaWiki\Extension\TitleIcon\Test;

use HtmlArmor;
use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\TitleIcon\IconManager;
use MediaWiki\Extension\TitleIcon\MainHookHandler;
use MediaWiki\Specials\SpecialSearch;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use SearchResult;

/**
 * @group Database
 * @covers \MediaWiki\Extension\TitleIcon\MainHookHandler
 */
class MainHookHandlerTest extends MediaWikiIntegrationTestCase {
	private const ICON_HTML = '<a href="/wiki/Icon_target"><img src="/icon.png" alt="Icon"></a>' . "\u{00A0}";

	private function newHandler( string $iconHtml ): MainHookHandler {
		$iconManager = $this->createMock( IconManager::class );
		$iconManager->method( 'getHTML' )->willReturn( $iconHtml );

		return new MainHookHandler(
			$iconManager,
			new HashConfig( [
				'TitleIcon_EnableIconInPageTitle' => true,
				'TitleIcon_EnableIconInSearchTitle' => true,
				'TitleIcon_CSSSelector' => '#firstHeading',
			] )
		);
	}

	/**
	 * @param MainHookHandler $handler
	 * @param HtmlArmor|string|null &$snippet
	 */
	private function runSearchHook( MainHookHandler $handler, &$snippet ): void {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$query = [];
		$attributes = [];
		$handler->onShowSearchHitTitle(
			$title,
			$snippet,
			$this->createMock( SearchResult::class ),
			[],
			$this->createMock( SpecialSearch::class ),
			$query,
			$attributes
		);
	}

	public function testSearchHitSnippetContainsNoNestedLinks(): void {
		// core wraps the snippet in the link to the search hit, so the snippet must not contain links itself
		$snippet = null;
		$this->runSearchHook( $this->newHandler( self::ICON_HTML ), $snippet );

		$this->assertInstanceOf( HtmlArmor::class, $snippet );
		$html = HtmlArmor::getHtml( $snippet );
		$this->assertStringContainsString( '<img src="/icon.png"', $html, 'icon is rendered' );
		$this->assertStringNotContainsString( '<a ', $html, 'no link inside the snippet' );
	}

	public function testSearchHitKeepsHighlightedTitleSnippet(): void {
		$snippet = new HtmlArmor( '<span class="searchmatch">Fo</span>o' );
		$this->runSearchHook( $this->newHandler( self::ICON_HTML ), $snippet );

		$this->assertStringContainsString(
			'<span class="searchmatch">Fo</span>o',
			HtmlArmor::getHtml( $snippet ),
			'highlighted title from the search engine is kept'
		);
	}

	public function testSearchHitWithoutIconsLeavesSnippetUntouched(): void {
		$snippet = null;
		$this->runSearchHook( $this->newHandler( '' ), $snippet );

		$this->assertNull( $snippet );
	}
}
