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

use InvalidArgumentException;
use MediaWiki\Extension\TitleIcon\Icon;
use MediaWiki\Page\PageReferenceValue;
use MediaWikiIntegrationTestCase;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * @covers \MediaWiki\Extension\TitleIcon\Icon
 */
class IconTest extends MediaWikiIntegrationTestCase {
	private function roundTrip( Icon $icon ): Icon {
		$codec = $this->getServiceContainer()->getJsonCodec();
		return $codec->deserialize( $codec->serialize( $icon ) );
	}

	public function testGetters(): void {
		$source = PageReferenceValue::localReference( NS_CATEGORY, 'Source' );
		$link = PageReferenceValue::localReference( NS_MAIN, 'Target' );

		$icon = new Icon( $source, 'wikiText.svg', Icon::ICON_TYPE_OOUI, $link );

		$this->assertSame( $source, $icon->getSource() );
		$this->assertSame( 'wikiText.svg', $icon->getIcon() );
		$this->assertSame( Icon::ICON_TYPE_OOUI, $icon->getType() );
		$this->assertSame( $link, $icon->getLink() );
		$this->assertNull( ( new Icon( $source, 'a', Icon::ICON_TYPE_UNICODE ) )->getLink() );
	}

	public function testFileIconNameIsNormalizedToFileNamespace(): void {
		$source = PageReferenceValue::localReference( NS_MAIN, 'Source' );

		foreach ( [ 'My_logo.png', 'File:My logo.png' ] as $name ) {
			$this->assertSame( 'File:My logo.png', ( new Icon( $source, $name, Icon::ICON_TYPE_FILE ) )->getIcon() );
		}
	}

	public function testInvalidTypeIsRejected(): void {
		$this->expectException( ParameterAssertionException::class );

		new Icon( PageReferenceValue::localReference( NS_MAIN, 'Source' ), 'x', 'bogus' );
	}

	public function testJsonRoundTripKeepsAllFields(): void {
		$icon = new Icon(
			PageReferenceValue::localReference( NS_CATEGORY, 'Source' ),
			'Logo.png',
			Icon::ICON_TYPE_FILE,
			PageReferenceValue::localReference( NS_HELP, 'Target' )
		);

		$copy = $this->roundTrip( $icon );

		$this->assertSame( $icon->getIcon(), $copy->getIcon() );
		$this->assertSame( $icon->getType(), $copy->getType() );
		$this->assertSame( NS_CATEGORY, $copy->getSource()->getNamespace() );
		$this->assertSame( 'Source', $copy->getSource()->getDBkey() );
		$this->assertSame( NS_HELP, $copy->getLink()->getNamespace() );
		$this->assertSame( 'Target', $copy->getLink()->getDBkey() );
	}

	public function testJsonRoundTripWithoutLink(): void {
		$icon = new Icon( PageReferenceValue::localReference( NS_MAIN, 'Source' ), 'x', Icon::ICON_TYPE_UNICODE );

		$this->assertNull( $this->roundTrip( $icon )->getLink() );
	}

	public static function provideIncompleteJson(): iterable {
		$complete = [ 'source-dbkey' => 'S', 'source-namespace' => 0, 'icon' => 'x', 'type' => 'unicode' ];
		foreach ( array_keys( $complete ) as $missing ) {
			$partial = $complete;
			unset( $partial[$missing] );
			yield "missing $missing" => [ $partial ];
		}
	}

	/**
	 * @dataProvider provideIncompleteJson
	 */
	public function testIncompleteJsonIsRejected( array $json ): void {
		$this->expectException( InvalidArgumentException::class );

		Icon::newFromJsonArray( $this->getServiceContainer()->getJsonCodec(), $json );
	}
}
