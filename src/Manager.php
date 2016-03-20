<?php

namespace IrvBallotCounter;

/**
 *
 */

class Manager {

  const ENDORSEMENT_THRESHOLD = 60.0;

  /**
   * @var \IrvBallotCounter\CsvFile[]
   */
  protected $files = [];

  protected $numCandidates;

  protected $numSeats;

  protected $round1Results;

  protected $round2Results;

  protected $differences;

  public function __construct(array $filenames, $num_candidates, $num_seats) {
    foreach ($filenames as $name) {
      $this->files[] = new CsvFile($name, $num_candidates);
    }
    $this->numCandidates = $num_candidates;
    $this->numSeats = $num_seats;
  }

  /**
   * @return array
   *   Array of messages if any files differ.
   */
  public function getDifferences() {
    if (!isset($this->differences)) {
      $this->differences = [];
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
          $this->differences[] = sprintf($tpl, $reference->getFilename(), $file->getFilename(), count($diff), implode(', ', $ballot_nums));
        }
      }
    }
    return $this->differences;
  }

  public function validateFirstRoundVotes() {
    if ($this->getDifferences()) {
      throw new \Exception('The csv ballot files do not match.');
    }
    if (isset($this->round1Results)) {
      return;
    }
    $invalid = [];
    // We've already validated that all the files have the same data.
    /** @var CsvFile $file */
    $file = reset($this->files);
    foreach ($file->getBallotRows() as $ballot) {
      // A ballot without votes for any candidate is added to no endorsement.
      $votes = array_slice($ballot, 1, $this->numCandidates);
      if (count(array_filter($votes)) > $this->numSeats) {
        $invalid[] = reset($ballot);
      }
    }
    if ($invalid) {
      throw new \Exception(sprintf('Invalid votes for ballot numbers %s', implode(', ', $invalid)));
    }
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function getFirstRoundResults() {
    if ($this->getDifferences()) {
      throw new \Exception('The csv ballot files do not match.');
    }
    if (!isset($this->round1Results)) {
      $this->validateFirstRoundVotes();
      $this->round1Results = [];
      // We've already validated that all the files have the same data.
      /** @var CsvFile $file */
      $file = reset($this->files);
      $candidates = array_slice($file->getHeader(), 1, $this->numCandidates);
      $this->round1Results['ballot_count'] = $file->getBallotCount();
      $this->round1Results['votes'] = [];
      foreach ($candidates as $name) {
        $this->round1Results['votes'][$name] = 0;
      }
      $this->round1Results['votes']['no endorsement'] = 0;
      foreach ($file->getBallotRows() as $ballot) {
        // A ballot without votes for any candidate is added to no endorsement.
        $votes = array_slice($ballot, 1, $this->numCandidates);
        $no_endorsement = true;
        foreach ($votes as $idx => $v) {
          if ($v) {
            $no_endorsement = false;
            $name = $candidates[$idx];
            $this->round1Results['votes'][$name]++;
          }
        }
        if ($no_endorsement) {
          $this->round1Results['votes']['no endorsement']++;
        }
      }
    }
    return $this->round1Results;
  }

  public function runoffNeeded() {
    $needed = $this->numCandidates > ($this->numSeats + 1);
    $results = $this->getFirstRoundResults();
    $divisor = 0.01 * (float) $results['ballot_count'];
    foreach ($results['votes'] as $name => $count) {
      if (($count / $divisor) >= self::ENDORSEMENT_THRESHOLD) {
        $needed = false;
      }
    }
    return $needed;
  }

  public function validateSecondRoundVotes() {
    if ($this->getDifferences()) {
      throw new \Exception('The csv ballot files do not match.');
    }
    if (isset($this->round2Results)) {
      return;
    }
    $invalid = [];
    // We've already validated that all the files have the same data.
    /** @var CsvFile $file */
    $file = reset($this->files);
    foreach ($file->getBallotRows() as $ballot) {
      // A ballot without votes for any candidate is added to no endorsement.
      $votes = array_slice($ballot, 1 + $this->numCandidates, $this->numCandidates);
      if (count(array_filter($votes)) > 1) {
        $invalid[] = reset($ballot);
      }
    }
    if ($invalid) {
      throw new \Exception(sprintf('Invalid votes for ballot numbers %s', implode(', ', $invalid)));
    }
  }

  public function getSecondRoundResults() {
    if ($this->getDifferences()) {
      throw new \Exception('The csv ballot files do not match.');
    }
    if (!$this->runoffNeeded()) {
      $this->round2Results = [];
    }
    if (!isset($this->round2Results)) {
      $this->validateSecondRoundVotes();
      $this->round2Results = [];
      $eliminated = $this->getEliminatedCandidates();
      $this->round2Results['eliminated'] = $eliminated;
    }
    return $this->round2Results;
  }

  protected function getEliminatedCandidates() {
    $eliminated = [];
    $results = $this->getFirstRoundResults();
    $candidates = $results['votes'];
    unset($candidates['no endorsement']);
    // Eliminate down to the
    while (count($candidates) > ($this->numSeats + 1)) {
      $min = min($candidates);
      foreach ($candidates as $name => $vote_count) {
        if ($vote_count == $min) {
          $eliminated[$name] = $name;
        }
      }
      foreach($eliminated as $name) {
        unset($candidates[$name]);
      }
    }

    return $eliminated;
  }
}
