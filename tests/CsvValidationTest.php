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
   * @expectedException \IrvBallotCounter\CsvFileException
   * @expectedExceptionMessage does not exist
   */
  public function testMissingFile() {
    $file = new CsvFile(__DIR__ . '/fixtures/missing.csv', 2);
    $file->readAndValidate();
  }

  /**
   * @covers ::readAndValidate
   * @expectedException \IrvBallotCounter\CsvFileException
   * @expectedExceptionMessage does not have a .csv extension
   */
  public function testInvalidFileExtension() {
    $file = new CsvFile(__DIR__ . '/fixtures/invalid_extension.txt', 2);
    $file->readAndValidate();
  }

  /**
   * @covers ::readAndValidate
   * @expectedException \IrvBallotCounter\CsvFileException
   * @expectedExceptionMessage Invalid length row
   */
  public function testInvalidHeader() {
    $file = new CsvFile(__DIR__ . '/fixtures/invalid_header.csv', 2);
    $file->readAndValidate();
  }

  /**
   * @covers ::readAndValidate
   * @expectedException \IrvBallotCounter\CsvFileException
   * @expectedExceptionMessage Invalid length row
   */
  public function testInvalidRow() {
    $file = new CsvFile(__DIR__ . '/fixtures/invalid_row.csv', 2);
    $file->readAndValidate();
  }
}
