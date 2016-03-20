<?php

namespace IrvBallotCounter;

/**
 *
 */

class Manager {

  /**
   * @var \IrvBallotCounter\CsvFile[]
   */
  protected $files = [];

  protected $numCandidates;

  protected $numSeats;

  public function __construct(array $filenames, $num_candidates, $num_seats) {
    foreach ($filenames as $name) {
      $files[] = new CsvFile($name, $num_candidates);
    }
  }

  /**
   * @return array
   *   Array of messages if any files differ.
   */
  public function getDifferences() {
    $messages = [];
    $files = $this->files;
    $reference = array_pop($files);
    foreach ($files as $file) {
      if (!$reference->equals($file)) {
        $diff = $reference->differingRows($file);
        $ballot_nums = [];
        foreach ($diff as $row) {
          $ballot_nums[] = reset($row);
        }
        $tpl = 'Files %s and %s differ. %d rows differ for ballot numbers %s';
        $messages[] = sprintf($tpl, $reference->getFilename(), $file->getFilename(), count($diff), implode(',', $ballot_nums));
      }
    }
    return $messages;
  }


}
