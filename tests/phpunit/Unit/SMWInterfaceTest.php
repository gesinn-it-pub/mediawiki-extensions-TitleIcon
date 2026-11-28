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

use MediaWiki\Extension\TitleIcon\SMWInterface;
use MediaWiki\Page\PageReferenceValue;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\TitleIcon\SMWInterface
 */
class SMWInterfaceTest extends MediaWikiUnitTestCase {
	private function newInterface( bool $smwLoaded ): SMWInterface {
		$registry = $this->createMock( ExtensionRegistry::class );
		$registry->expects( $this->once() )
			->method( 'isLoaded' )
			->with( 'SemanticMediaWiki' )
			->willReturn( $smwLoaded );

		return new SMWInterface( $registry );
	}

	public function testReportsWhetherSmwIsLoaded(): void {
		$this->assertTrue( $this->newInterface( true )->isLoaded() );
		$this->assertFalse( $this->newInterface( false )->isLoaded() );
	}

	public function testWithoutSmwThereAreNoPropertyValues(): void {
		$page = PageReferenceValue::localReference( NS_MAIN, 'Test Page' );

		$this->assertSame( [], $this->newInterface( false )->getPropertyValues( $page, 'Title Icon' ) );
	}
}
