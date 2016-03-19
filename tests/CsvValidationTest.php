<?php

namespace IrvBallotCounter\Tests;

use IrvBallotCounter\CsvFile;

/**
 * Class CsvValidationTest
 * @package IrvBallotCounter\Tests
 * @coversDefaultClass \IrvBallotCounter\CsvFile
 */
class CsvValidationTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::readAndValidate
   * @expectedException \Exception
   * @expectedExceptionMessage does not exist
   */
  public function testMissingFile() {
    $file = new CsvFile(__DIR__ . '/fixtures/missing.csv', 2);
    $file->readAndValidate();
  }
}