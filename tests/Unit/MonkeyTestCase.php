<?php
/**
 * Base test case wiring Brain Monkey setup/teardown.
 *
 * @package LightweightPlugins\Cookie
 */

declare(strict_types=1);

namespace LightweightPlugins\Cookie\Tests\Unit;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Extends PHPUnit's TestCase with Brain Monkey lifecycle hooks so WordPress
 * functions can be stubbed without loading WordPress. The Mockery
 * integration counts fulfilled expectations as assertions, so tests whose
 * whole point is an expect() are not flagged risky.
 */
abstract class MonkeyTestCase extends TestCase {

	use MockeryPHPUnitIntegration;

	/**
	 * Set up Brain Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down Brain Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
