<?php

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class ContactPresenter extends \PresenterTestCase
{

	public function __construct()
	{
		$this->openPresenter('Contact:');
	}

	public function testRenderDefault()
	{
		$this->checkAction('default');
	}

}

(new ContactPresenter())->run();
