<?php

/**
 * Required CSV format:
 * header row:  ballot_num, candidate1 ... candidateN, no endorsement, candidate1 ... candidateN, no endorsement
 *
 * The 1st row with no votes is considered to end the ballots.  If there are rows with votes after that the file in invalid.
 */

namespace IrvBallotCounter;

class CsvFile {

  /**
   * @var string
   */
  protected $filename;

  /**
   * @var int
   */
  protected $numCandidates;

  /**
   * @var array
   */
  protected $header;

  /**
   * @var array
   */
  protected $ballotRows;

  /**
   * @var int
   */
  protected $expectedRowLength;

  public function __construct($filename, $num_candidates) {
    $this->filename = $filename;
    $this->numCandidates = (int) $num_candidates;
    // Each row should have ballot number, each candidate in initial round
    // a "no endorsement" each candidate in 2nd round, and another
    // "no endorsement".
    $this->expectedRowLength = 3 + 2 * $this->numCandidates;
  }

  /**
   * Get the filename.
   *
   * @return string
   */
  public function getFilename() {
    return $this->filename;
  }

  /**
   * Get the CSV header row.
   *
   * @return array
   * @throws \IrvBallotCounter\CsvFileException
   */
  public function getHeader() {
    if (!isset($this->header)) {
      $this->readAndValidate();
    }
    return $this->header;
  }

  /**
   * @return int
   * @throws \IrvBallotCounter\CsvFileException
   */
  public function getBallotCount() {
    if (!isset($this->ballotRows)) {
      $this->readAndValidate();
    }
    return count($this->ballotRows);
  }

  /**
   * @return array
   * @throws \IrvBallotCounter\CsvFileException
   */
  public function getBallotRows() {
    if (!isset($this->ballotRows)) {
      $this->readAndValidate();
    }
    return $this->ballotRows;
  }

  /**
   * @param \IrvBallotCounter\CsvFile $other
   * @return bool
   */
  public function equals(CsvFile $other) {
    $equal = $this->getHeader() === $other->getHeader();
    // We could make this faster, but we are using small data sets.
    return $equal && !$this->differingRows($other);
  }

  /**
   * @param \IrvBallotCounter\CsvFile $other
   * @return array
   */
  public function differingRows(CsvFile $other) {
    $differing = [];
    $my_rows = $this->getBallotRows();
    $other_rows = $other->getBallotRows();
    $equal = count($my_rows) == count($other_rows);
    if ($equal) {
      $count = count($my_rows);
      for ($i = 0; $i < $count; $i++) {
        $a = array_pop($my_rows);
        $b = array_pop($other_rows);
        if ($a !== $b) {
          $differing[] = $a;
        }
      }
    }
    return $differing;
  }

   /**
   * Read file and validate data
   *
   * @throws \IrvBallotCounter\CsvFileException
   */
  public function readAndValidate() {
    if (!file_exists($this->filename)) {
      throw new CsvFileException("{$this->filename} does not exist.");
    }
    if (pathinfo($this->filename, PATHINFO_EXTENSION) != 'csv') {
      throw new CsvFileException("{$this->filename} does not have a .csv extension.");
    }
    ini_set('auto_detect_line_endings', true);
    $csv = array_map('str_getcsv', file($this->filename));
    if (count($csv) < 2) {
       throw new CsvFileException("{$this->filename} does not have at least 2 lines.");
    }
    $this->header = $csv[0];
    $this->validateRowLength($this->header);
    // The two halves of the header should be the same.
    $round1 = array_slice($this->header, 1, $this->numCandidates + 1);
    $round2 = array_slice($this->header, $this->numCandidates + 2);
    if ($round1 !== $round2) {
      throw new CsvFileException("{$this->filename} candidates in the first half of the header row don't match the second half: " . print_r($this->header, true));
    }
    unset($csv[0]);
    $count = $this->findBallotCount($csv);
    $ballot_rows = array_slice($csv, 0, $count, true);
    foreach ($ballot_rows as $row) {
      $this->validateRowLength($row);
    }
    $this->ballotRows = $this->normalizeVoteFormat($ballot_rows);
    $empty_rows = array_slice($csv, $count, NULL, true);
    $this->validateEmptyRows($empty_rows);
  }

  /**
   * @param array $csv
   * @return int
   * @throws \IrvBallotCounter\CsvFileException
   */
  protected function findBallotCount(array $csv) {
    $count = 0;
    foreach ($csv as $row) {
      if ($this->ballotRowIsEmpty($row)) {
        break;
      }
      $count++;
    }
    return $count;
  }

  protected function ballotRowIsEmpty(array $row) {
    $empty = true;
    $id = array_shift($row);
    if (empty($id) || !is_numeric($id)) {
      throw new CsvFileException("{$id} is not a valid ballot number.");
    }
    foreach($row as $cell) {
      if (strlen($cell) > 0) {
        $empty = false;
        break;
      }
    }
    return $empty;
  }

  /**
   * Validate a row.
   *
   * @param array $row
   * @throws \IrvBallotCounter\CsvFileException
   */
  protected function validateRowLength(array $row) {
    if (count($row) != $this->expectedRowLength) {
      throw new CsvFileException("Invalid length row. Expected {$this->expectedRowLength} elements: " . print_r($row, true));
    }
  }

  /**
   * @param array $csv
   * @throws \IrvBallotCounter\CsvFileException
   */
  protected function validateEmptyRows(array $csv) {
    foreach($csv as $row) {
      if (!$this->ballotRowIsEmpty($row)) {
        throw new CsvFileException("Row is not empty, but came after an empty row: " . print_r($row, true));
      }
    }
  }

  /**
   * Normalize string votes to boolean values.
   *
   * @param array $ballot_rows
   * @return array
   */
  protected function normalizeVoteFormat(array $ballot_rows) {
    $normalized_rows = [];
    foreach ($ballot_rows as $row) {
      $normalized_rows[] = array_map(function($v) { return strlen($v) != 0; }, $row);
    }
    return $normalized_rows;
  }

}


