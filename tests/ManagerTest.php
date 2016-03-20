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

  /**
   * @covers ::getFirstRoundResults
   * @covers ::runoffNeeded
   */
  public function testSimpleFirstRound() {
    $filenames = [
      __DIR__ . '/fixtures/simple_x_3candidate.csv',
      __DIR__ . '/fixtures/simple_123_3candidate.csv',
    ];
    $manager = new Manager($filenames, 3, 1);
    $results = $manager->getFirstRoundResults();
    $this->assertEquals(10, $results['ballot_count']);
    $this->assertEquals(4, $results['votes']['candidate_A']);
    $this->assertEquals(3, $results['votes']['candidate_B']);
    $this->assertEquals(2, $results['votes']['candidate_C']);
    $this->assertEquals(1, $results['votes']['no endorsement']);
    $this->assertEquals(true, $manager->runoffNeeded());
  }

  /**
   * @covers ::getSecondRoundResults
   */
  public function testSimpleFSecondRound() {
    $filenames = [
      __DIR__ . '/fixtures/simple_x_3candidate.csv',
      __DIR__ . '/fixtures/simple_123_3candidate.csv',
    ];
    $manager = new Manager($filenames, 3, 1);
    $this->assertEquals(true, $manager->runoffNeeded());
    $result = $manager->getSecondRoundResults();
    $this->assertEquals(1, count($result['eliminated']));
    $this->assertEquals('candidate_C', end($result['eliminated']));
  }

  /**
   * @covers ::getFirstRoundResults
   * @covers ::runoffNeeded
   */
  public function testEndorsedFirstRound() {
    $filenames = [
      __DIR__ . '/fixtures/endorsed_x_3candidate.csv',
      __DIR__ . '/fixtures/endorsed_1_3candidate.csv',
    ];
    $manager = new Manager($filenames, 3, 1);
    $results = $manager->getFirstRoundResults();
    $this->assertEquals(15, $results['ballot_count']);
    $this->assertEquals(9, $results['votes']['candidate_A']);
    $this->assertEquals(3, $results['votes']['candidate_B']);
    $this->assertEquals(2, $results['votes']['candidate_C']);
    $this->assertEquals(1, $results['votes']['no endorsement']);
    $this->assertEquals(false, $manager->runoffNeeded());
  }
}
