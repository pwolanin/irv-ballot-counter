# irv-ballot-counter

Copyright (c) 2016 by Peter Wolanin

This is free software. You may use it under the terms of the GNU General Public license version 2, or any later verion.

Ballot counting logic for endorsement ballots recorded as csv files with possible 1 round instant runoff.

The rules of the endorsement vote are:

* 60% is the threshold for endorsement.
* N is the number of positions to be elected.
* In the first round section, vote for up to N candidates or for "no endorsement"
* In the runoff section of the ballot vote for up to 1 more candidate.
* If no candidate is endorsed in the 1st round, and there are more than N + 1 candidates, a runoff is held.
* The instant runoff eliminates the lowest voter getters until there are N + 1 candidates left.
* When the number of votes left from the first round is < N, transfer any 2nd round vote.

This code currently handles just a single replacement vote in the 2nd round.
This is valid when there is only one position to be elected, or when the number
of candidates is N + 2 and a tie is seen as unlikely.
