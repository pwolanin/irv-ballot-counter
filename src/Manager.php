<?php

namespace IrvBallotCounter;

/**
 *
 */

class Manager {

  /**
   * @var \IrvBallotCounter\CsvFile[] $files
   */
  protected $files = [];

  protected $numCandidates;

  protected $numSeats;

  function __construct(array $filenames, $num_candidates, $num_seats) {
    foreach ($filenames as $name) {
      $files[$name] = new CsvFile($name, $num_candidates);
    }
  }
}
