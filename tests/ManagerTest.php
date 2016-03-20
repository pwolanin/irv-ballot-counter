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
  public function testSimpleSecondRound() {
    $filenames = [
      __DIR__ . '/fixtures/simple_x_3candidate.csv',
      __DIR__ . '/fixtures/simple_123_3candidate.csv',
    ];
    $manager = new Manager($filenames, 3, 1);
    $this->assertEquals(true, $manager->runoffNeeded());
    $results = $manager->getSecondRoundResults();
    $this->assertEquals(1, count($results['eliminated']));
    $this->assertEquals('candidate_C', current(array_keys($results['eliminated'])));
    $this->assertEquals(10, $results['ballot_count']);
    $this->assertEquals(6, $results['votes']['candidate_A']);
    $this->assertEquals(3, $results['votes']['candidate_B']);
    $this->assertEquals(0, $results['votes']['candidate_C']);
    $this->assertEquals(1, $results['votes']['no endorsement']);
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

  /**
   * @covers ::getFirstRoundResults
   * @covers ::runoffNeeded
   */
  public function testFourCandidatesFirstRound() {
    $filenames = [
      __DIR__ . '/fixtures/4candidates_1_example.csv',
      __DIR__ . '/fixtures/4candidates_1x_example2.csv',
    ];
    $manager = new Manager($filenames, 4, 2);
    $results = $manager->getFirstRoundResults();
    $this->assertEquals(29, $results['ballot_count']);
    $this->assertEquals(14, $results['votes']['Hinds']);
    $this->assertEquals(9, $results['votes']['Patterson']);
    $this->assertEquals(15, $results['votes']['Robeson']);
    $this->assertEquals(8, $results['votes']['Smoyer']);
    $this->assertEquals(2, $results['votes']['no endorsement']);
    $this->assertEquals(true, $manager->runoffNeeded());
  }

  /**
   * @covers ::getFirstRoundResults
   * @covers ::runoffNeeded
   */
  public function testFourCandidatesSecondRound() {
    $filenames = [
      __DIR__ . '/fixtures/4candidates_1_example.csv',
      __DIR__ . '/fixtures/4candidates_1x_example2.csv',
    ];
    $manager = new Manager($filenames, 4, 2);
    $results = $manager->getSecondRoundResults();
    $this->assertEquals('Smoyer', current(array_keys($results['eliminated'])));
    $this->assertEquals(29, $results['ballot_count']);
    $this->assertEquals(15, $results['votes']['Hinds']);
    $this->assertEquals(12, $results['votes']['Patterson']);
    $this->assertEquals(16, $results['votes']['Robeson']);
    $this->assertEquals(0, $results['votes']['Smoyer']);
    $this->assertEquals(4, $results['votes']['no endorsement']);
  }
}
