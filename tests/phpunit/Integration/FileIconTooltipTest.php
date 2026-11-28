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
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\TitleIcon\IconManager::getFileIconTooltip
 * @covers \MediaWiki\Extension\TitleIcon\IconManager::getFileTooltip
 */
class FileIconTooltipTest extends MediaWikiIntegrationTestCase {
	private function getTooltip( Icon $icon, bool $useFileName ): string {
		$this->overrideConfigValue( 'TitleIcon_UseFileNameAsToolTip', $useFileName );
		$this->getServiceContainer()->resetServiceForTesting( 'TitleIcon:IconManager' );

		return $this->getServiceContainer()->getService( 'TitleIcon:IconManager' )->getFileIconTooltip( $icon );
	}

	public function testTooltipIsTheFileNameWithoutExtension(): void {
		$source = PageReferenceValue::localReference( NS_MAIN, 'Some page' );
		$icon = new Icon( $source, 'Logo.v2.png', Icon::ICON_TYPE_FILE );

		$this->assertSame( 'Logo.v2', $this->getTooltip( $icon, true ) );
	}

	public function testTooltipIsTheNameOfTheDefiningPageIfConfigured(): void {
		$source = PageReferenceValue::localReference( NS_HELP, 'Some page' );
		$icon = new Icon( $source, 'Logo.png', Icon::ICON_TYPE_FILE );

		$this->assertSame( 'Help:Some page', $this->getTooltip( $icon, false ) );
	}

	public function testTooltipOfAPageObjectSourceIsTheSameAsOfAReference(): void {
		$icon = new Icon( Title::makeTitle( NS_HELP, 'Some page' ), 'Logo.png', Icon::ICON_TYPE_FILE );

		$this->assertSame( 'Help:Some page', $this->getTooltip( $icon, false ) );
	}
}
