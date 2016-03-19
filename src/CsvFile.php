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
   * @var string $filename
   */
  protected $filename;

  protected $numCandidates;

  protected $header;

  protected $ballotRows;

  public function __construct($filename, $num_candidates) {
    $this->filename = $filename;
    $this->numCandidates = (int) $num_candidates;
  }

  public function getHeader() {
  }

  /**
   * Read file and validate data
   *
   * @throws \Exception
   */
  public function readAndValidate() {
    if (!file_exists($this->filename)) {
      throw new \Exception("{$this->filename} does not exist.");
    }
    if (pathinfo($this->filename, PATHINFO_EXTENSION) != 'csv') {
      throw new \Exception("{$this->filename} does not have a .csv extension.");
    }
    ini_set('auto_detect_line_endings', true);
    $csv = array_map('str_getcsv', file($this->filename));
    if (count($csv) < 2) {
       throw new \Exception("{$this->filename} does not have at least 2 lines.");
    }
    $this->header = $csv[0];
    unset($csv[0]);
    $count = $this->findBallotCount($csv);
    $this->ballotRows = array_slice($csv, 0, $count, true);
    $this->validateBallotRows();
    $empty_rows = array_slice($csv, $count, NULL, true);
    $this->validateEmptyRows($empty_rows);
  }

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
      throw new \Exception("{$is} is not a valid ballot number.");
    }
    foreach($row as $cell) {
      if (strlen($cell) > 0) {
        $empty = false;
        break;
      }
    }
    return $empty;
  }

  protected function validateBallotRow($row) {
    // Each row should have ballot number, each candidate in initial round
    // a "no endorsement" each candidate in 2nd round, and another
    // "no endorsement".
    $expected_row_length = 3 + 2 * $this->numCandidates;
    foreach ($this->ballotRows as $row) {
      if (count($row) != $expected_row_length) {
        throw new \Exception("Invalid row: " . print_r($row, true));
      }
    }
  }

  protected function validateEmptyRows($csv) {
    foreach($csv as $row) {
      if (!$this->ballotRowIsEmpty($row)) {
        throw new \Exception("Row is not empty, but came after an empty row: " . print_r($row, true));
      }
    }
  }

}


