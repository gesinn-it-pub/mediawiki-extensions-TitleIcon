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

use MediaWiki\Extension\TitleIcon\Icon;
use MediaWiki\Page\PageReferenceValue;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 * @covers \MediaWiki\Extension\TitleIcon\IconManager::getHTML
 */
class IconManagerHtmlTest extends MediaWikiIntegrationTestCase {
	private function getHtml( Icon ...$icons ): string {
		return $this->getServiceContainer()->getService( 'TitleIcon:IconManager' )->getHTML( $icons );
	}

	private static function source(): PageReferenceValue {
		return PageReferenceValue::localReference( NS_MAIN, 'Test Page' );
	}

	public function testOouiIconPointsToTheOouiIconDirectory(): void {
		$html = $this->getHtml( new Icon( self::source(), 'wikiText.svg', Icon::ICON_TYPE_OOUI ) );

		$this->assertStringContainsString( '/resources/lib/ooui/themes/wikimediaui/images/icons/wikiText.svg', $html );
	}

	public static function provideInvalidOouiIcons(): array {
		return [
			'parent directory' => [ '../../../images/secret.svg' ],
			'sub directory' => [ 'sub/icon.svg' ],
			'external url' => [ '//example.org/icon.svg' ],
			'not an svg' => [ 'icon.php' ],
			'empty name' => [ '.svg' ],
		];
	}

	/**
	 * @dataProvider provideInvalidOouiIcons
	 */
	public function testOouiIconWithInvalidNameIsNotRendered( string $name ): void {
		$this->assertSame( '', $this->getHtml( new Icon( self::source(), $name, Icon::ICON_TYPE_OOUI ) ) );
	}

	public function testFileIconWithInvalidFileNameIsNotRendered(): void {
		// an invalid file title is stored as empty icon name
		$icon = new Icon( self::source(), '|', Icon::ICON_TYPE_FILE );
		$this->assertSame( '', $icon->getIcon() );

		$this->assertSame( '', $this->getHtml( $icon ) );
	}

	public function testFileIconOfMissingFileIsNotRendered(): void {
		$this->assertSame( '', $this->getHtml( new Icon( self::source(), 'Missing.png', Icon::ICON_TYPE_FILE ) ) );
	}

	public function testUnicodeIconIsRenderedAsLinkText(): void {
		$html = $this->getHtml( new Icon( self::source(), '&#x1F469;<b>x</b>', Icon::ICON_TYPE_UNICODE ) );

		$this->assertStringContainsString( "\u{1F469}&lt;b&gt;x&lt;/b&gt;", $html, 'character reference decoded, markup escaped' );
		$this->assertStringNotContainsString( '<b>', $html );
	}
}
