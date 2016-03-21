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

  /**
   * @throws \IrvBallotCounter\ManagerException
   */
  public function validateFirstRoundVotes() {
    if ($this->getDifferences()) {
      throw new ManagerException('The csv ballot files do not match.');
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
      throw new ManagerException(sprintf('Invalid first round votes for ballot numbers %s', implode(', ', $invalid)));
    }
  }

  /**
   * @return array
   * @throws \Exception
   * @throws \IrvBallotCounter\ManagerException
   */
  public function getFirstRoundResults() {
    if ($this->getDifferences()) {
      throw new ManagerException('The csv ballot files do not match.');
    }
    if (!isset($this->round1Results)) {
      $this->validateFirstRoundVotes();
      // We've already validated that all the files have the same data.
      /** @var CsvFile $file */
      $file = reset($this->files);
      $candidates = array_slice($file->getHeader(), 1, $this->numCandidates);
      $this->round1Results = $this->countBallots($candidates, $file->getBallotRows());
    }
    return $this->round1Results;
  }

  /**
   * @param array $candidates
   * @param array $ballot_rows
   * @param int $offset
   *   The offset for votes in the balloe row.
   * @return array
   */
  public function countBallots(array $candidates, array $ballot_rows, $offset = 1) {
    $results = [];
    $results['ballot_count'] = count($ballot_rows);
    $results['votes'] = [];
    foreach ($candidates as $name) {
      $results['votes'][$name] = 0;
    }
    $results['votes']['no endorsement'] = 0;
    $num_candidates = count($candidates);
    foreach ($ballot_rows as $ballot) {
      // A ballot without votes for any candidate is added to no endorsement.
      $votes = array_slice($ballot, $offset, $num_candidates);
      $no_endorsement = true;
      foreach ($votes as $idx => $v) {
        if ($v) {
          $no_endorsement = false;
          $name = $candidates[$idx];
          $results['votes'][$name]++;
        }
      }
      if ($no_endorsement) {
        $results['votes']['no endorsement']++;
      }
    }
    return $results;
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

  /**
   * @throws \IrvBallotCounter\ManagerException
   */
  public function validateSecondRoundVotes() {
    if ($this->getDifferences()) {
      throw new ManagerException('The csv ballot files do not match.');
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
      // The offset has 2 added since we need to skip the ballot number column
      // and also the "no endorsement" column from the 1st round. For example
      // a header row with 4 candidates could llok like:
      // [0 => 'num', 1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'none', 6 => 'A', 7 => 'B', 8 => 'C', 9 => 'D', 10 => 'none']
      $votes = array_slice($ballot, 2 + $this->numCandidates, $this->numCandidates);
      if (count(array_filter($votes)) > 1) {
        $invalid[] = reset($ballot);
      }
    }
    if ($invalid) {
      throw new ManagerException(sprintf('Invalid second round votes for ballot numbers %s', implode(', ', $invalid)));
    }
  }

  /**
   * Find eliminated candidates, transfer votes, and calculate results.
   *
   * @return array
   *   The results, or an empty array if a runoff is not needed.
   * @throws \IrvBallotCounter\ManagerException
   */
  public function getSecondRoundResults() {
    if ($this->getDifferences()) {
      throw new ManagerException('The csv ballot files do not match.');
    }
    if (!$this->runoffNeeded()) {
      $this->round2Results = [];
    }
    if (!isset($this->round2Results)) {
      $this->validateSecondRoundVotes();
      $this->round2Results = [];
      $eliminated = $this->getEliminatedCandidates();
      $this->round2Results['eliminated'] = $eliminated;
      // We've already validated that all the files have the same data.
      /** @var CsvFile $file */
      $file = reset($this->files);
      $candidates = array_slice($file->getHeader(), 1, $this->numCandidates);
      // In any row where an eliminated candidate got a vote we need to transfer
      // a vote for any other candidate from the second half of the ballot
      // before counting again.
      $ballot_rows = [];
      foreach ($file->getBallotRows() as $row) {
        $transfer_vote = false;
        $round1_vote = array_slice($row, 1, $this->numCandidates);
        $round2_vote = array_slice($row, 2 + $this->numCandidates, $this->numCandidates);
        foreach ($eliminated as $idx) {
          $transfer_vote = $transfer_vote || $round1_vote[$idx];
          // Remove votes for eliminated candidates.
          $round1_vote[$idx] = false;
          $round2_vote[$idx] = false;
        }
        // Special case - if there was no endorsement in the 1st round, but a
        // name specified for the runoff.
        $transfer_vote = $transfer_vote || count(array_filter($round1_vote)) < $this->numSeats;
        if ($transfer_vote) {
          foreach ($round2_vote as $idx => $vote) {
            if ($vote) {
              $round1_vote[$idx] = true;
            }
          }
        }
        $ballot_rows[] = $round1_vote;
      }
      $this->round2Results += $this->countBallots($candidates, $ballot_rows, 0);
    }
    return $this->round2Results;
  }

  /**
   * Using first round results, find the eliminated candidates.
   *
   * @return array
   *   Array where keys are candidate names and values are indexes into votes.
   * @throws \Exception
   */
  protected function getEliminatedCandidates() {
    $eliminated = [];
    $results = $this->getFirstRoundResults();
    $candidates = $results['votes'];
    // Positional indexes are useful for processing the eliminations.
    $candidates_indexes = array_flip(array_keys($candidates));
    unset($candidates['no endorsement']);
    // Eliminate down to the
    while (count($candidates) > ($this->numSeats + 1)) {
      $min = min($candidates);
      foreach ($candidates as $name => $vote_count) {
        if ($vote_count == $min) {
          $eliminated[$name] = $candidates_indexes[$name];
        }
      }
      foreach($eliminated as $name => $idx) {
        unset($candidates[$name]);
      }
    }

    return $eliminated;
  }
}
