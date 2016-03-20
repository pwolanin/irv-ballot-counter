<?php

namespace IrvBallotCounter\Tests;

use IrvBallotCounter\Manager;

/**
 * Class ManagerTest
 * @package IrvBallotCounter\Tests
 * @coversDefaultClass \IrvBallotCounter\Manager
 */
class ManagerTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::getDifferences
   */
  public function testDifferingRows() {
    $filenames = [
      __DIR__ . '/fixtures/simple_x_3candidate.csv',
      __DIR__ . '/fixtures/simple_123_3candidate.csv',
    ];
    $manager = new Manager($filenames, 3, 2);
    $this->assertEquals(0, count($manager->getDifferences()));
    // A file with the same number of rows, but one row differs.
    $filenames = [
      __DIR__ . '/fixtures/simple_x_3candidate.csv',
      __DIR__ . '/fixtures/second_x_3candidate.csv',
    ];
    $manager = new Manager($filenames, 3, 2);
    $this->assertEquals(1, count($manager->getDifferences()));
  }
}
