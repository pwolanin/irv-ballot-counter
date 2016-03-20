<?php

namespace IrvBallotCounter\Tests;

use IrvBallotCounter\CsvFile;

/**
 * Class CsvProcessTest
 * @package IrvBallotCounter\Tests
 * @coversDefaultClass \IrvBallotCounter\CsvFile
 */
class CsvProcessTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::readAndValidate
   */
  public function testSimple3Candidates() {
    $file = new CsvFile(__DIR__ . '/fixtures/simple_x_3candidate.csv', 3);
    $file->readAndValidate();
    $this->assertEquals(['ballot_num','candidate_A','candidate_B','candidate_C','none','candidate_A','candidate_B','candidate_C','none'], $file->getHeader());
    $this->assertEquals(10, $file->getBallotCount());
  }

  /**
   * @covers ::equals
   */
  public function testEquals() {
    $file1 = new CsvFile(__DIR__ . '/fixtures/simple_x_3candidate.csv', 3);
    $file2 = new CsvFile(__DIR__ . '/fixtures/simple_123_3candidate.csv', 3);
    // Must be equal if they reference the same file.
    $file3 = clone $file1;
    $this->assertTrue($file1->equals($file2));
    $this->assertTrue($file2->equals($file1));
    $this->assertTrue($file1->equals($file3));
    // A file with the same number of rows, but one row differs.
    $different_file = new CsvFile(__DIR__ . '/fixtures/second_x_3candidate.csv', 3);
    $this->assertFalse($different_file->equals($file1));
    $this->assertFalse($file1->equals($different_file));
    $diff_header_file = new CsvFile(__DIR__ . '/fixtures/diffheader_x_3candidate.csv', 3);
    $this->assertFalse($file1->equals($diff_header_file));
  }

  /**
   * @covers ::differingRows
   */
  public function testDifferingRows() {
    $file1 = new CsvFile(__DIR__ . '/fixtures/simple_x_3candidate.csv', 3);
    $file2 = new CsvFile(__DIR__ . '/fixtures/simple_123_3candidate.csv', 3);
    $this->assertEquals(0, count($file1->differingRows($file2)));
    // A file with the same number of rows, but one row differs.
    $different_file = new CsvFile(__DIR__ . '/fixtures/second_x_3candidate.csv', 3);
    $diff = $file1->differingRows($different_file);
    $this->assertEquals(1, count($diff));
    $row = end($diff);
    $ballot_nam = reset($row);
    $this->assertEquals(8, $ballot_nam);
  }
}
