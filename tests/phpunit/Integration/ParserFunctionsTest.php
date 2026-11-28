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
use MediaWiki\Extension\TitleIcon\IconManager;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * What the parser functions store in the parser output.
 *
 * @group Database
 * @covers \MediaWiki\Extension\TitleIcon\InitHookHandler
 * @covers \MediaWiki\Extension\TitleIcon\IconManager::parseIcons
 * @covers \MediaWiki\Extension\TitleIcon\IconManager::hideTitleIcon
 */
class ParserFunctionsTest extends MediaWikiIntegrationTestCase {
	private function parse( string $wikitext ): \MediaWiki\Parser\ParserOutput {
		return $this->getServiceContainer()->getParserFactory()->create()->parse(
			$wikitext,
			Title::makeTitle( NS_MAIN, 'Parser Test' ),
			ParserOptions::newFromAnon()
		);
	}

	private function storedIcons( string $wikitext ): array {
		$json = $this->parse( $wikitext )->getPageProperty( Icon::ICON_PROPERTY_NAME );
		$icons = $json ? $this->getServiceContainer()->getJsonCodec()->deserialize( $json ) : [];
		return array_map( static fn ( Icon $icon ) => [ $icon->getType(), $icon->getIcon() ], array_values( $icons ) );
	}

	public function testIconsOfAllTypesAreStoredInOrder(): void {
		$this->assertSame(
			[ [ 'file', 'File:A.png' ], [ 'ooui', 'wikiText.svg' ], [ 'unicode', '&#x1F4BB;' ] ],
			$this->storedIcons(
				'{{#titleicon_file:A.png}}{{#titleicon_ooui:wikiText.svg}}{{#titleicon_unicode:&#x1F4BB;}}'
			)
		);
	}

	public function testSameIconIsStoredOnce(): void {
		$this->assertSame(
			[ [ 'file', 'File:A.png' ] ],
			$this->storedIcons( '{{#titleicon_file:A.png}}{{#titleicon_file:File:A.png}}' )
		);
	}

	public function testEmptyIconIsIgnored(): void {
		$this->assertSame( [], $this->storedIcons( '{{#titleicon_file:}}{{#titleicon_unicode:}}' ) );
	}

	public function testLinkTargetIsStored(): void {
		$json = $this->parse( '{{#titleicon_file:A.png|Help:Icons}}' )->getPageProperty( Icon::ICON_PROPERTY_NAME );
		$icons = $this->getServiceContainer()->getJsonCodec()->deserialize( $json );

		$this->assertSame( NS_HELP, $icons[0]->getLink()->getNamespace() );
		$this->assertSame( 'Icons', $icons[0]->getLink()->getDBkey() );
	}

	public function testMalformedLinkTargetIsIgnored(): void {
		$json = $this->parse( '{{#titleicon_file:A.png|a<b}}' )->getPageProperty( Icon::ICON_PROPERTY_NAME );
		$icons = $this->getServiceContainer()->getJsonCodec()->deserialize( $json );

		$this->assertCount( 1, $icons );
		$this->assertNull( $icons[0]->getLink() );
	}

	public function testPageWithoutIconsHasNoProperty(): void {
		$output = $this->parse( 'plain text' );

		$this->assertNull( $output->getPageProperty( Icon::ICON_PROPERTY_NAME ) ?: null );
		$this->assertNull( $output->getPageProperty( IconManager::HIDE_PROPERTY_NAME ) ?: null );
	}

	public static function provideHide(): array {
		return [
			'page' => [ '{{#hidetitleicon:page}}', 'page' ],
			'category' => [ '{{#hidetitleicon:category}}', 'category' ],
			'namespace' => [ '{{#hidetitleicon:namespace}}', 'namespace' ],
			'all' => [ '{{#hidetitleicon:all}}', 'page,category,namespace' ],
			'combined in canonical order' => [
				'{{#hidetitleicon:namespace}}{{#hidetitleicon:page}}',
				'page,namespace',
			],
			'repeated' => [ '{{#hidetitleicon:page}}{{#hidetitleicon:page}}', 'page' ],
			'all and single' => [ '{{#hidetitleicon:category}}{{#hidetitleicon:all}}', 'page,category,namespace' ],
			'unknown value' => [ '{{#hidetitleicon:bogus}}', null ],
			'no value' => [ '{{#hidetitleicon:}}', null ],
		];
	}

	/**
	 * @dataProvider provideHide
	 */
	public function testHideIsStored( string $wikitext, ?string $expected ): void {
		$stored = $this->parse( $wikitext )->getPageProperty( IconManager::HIDE_PROPERTY_NAME );

		$this->assertSame( $expected, $stored ?: null );
	}

	public function testStateIsNotSharedBetweenParses(): void {
		$this->parse( '{{#titleicon_file:A.png}}{{#hidetitleicon:all}}' );

		$this->assertSame( [], $this->storedIcons( 'nothing' ) );
		$this->assertNull( $this->parse( 'nothing' )->getPageProperty( IconManager::HIDE_PROPERTY_NAME ) ?: null );
	}
}
