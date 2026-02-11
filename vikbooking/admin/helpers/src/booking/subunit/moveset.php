<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Room-Booking sub-unit moveset implementation.
 * 
 * @since   1.18.7 (J) - 1.8.7 (WP)
 */
final class VBOBookingSubunitMoveset
{
    /**
     * @var  array
     */
    private array $moveset = [];

    /**
     * @var  VBOBookingRegistry
     */
    private VBOBookingRegistry $registry;

    /**
     * @var  int
     */
    private int $iterationNumber = 0;

    /**
     * @var  int
     */
    private int $totalMoves = 0;

    /**
     * @var  int
     */
    private int $solutionsCount = 0;

    /**
     * @var  ?string
     */
    private ?string $verboseRelocation = null;

    /**
     * Class constructor will check and bind the moveset list and booking registry.
     * 
     * @param   VBOBookingSubunitRecord[]   $moveset    List of (fitting) sub-unit record objects.
     */
    public function __construct(array $moveset, VBOBookingRegistry $registry)
    {
        // accept only sub-unit record objects
        $moveset = array_filter($moveset, function($move) {
            return $move instanceof VBOBookingSubunitRecord;
        });

        if (!$moveset) {
            throw new InvalidArgumentException('Moveset expects a list of sub-unit record objects.', 500);
        }

        // bind moveset list, expected to fit
        $this->moveset = $moveset;

        // bind booking registry
        $this->registry = $registry;
    }

    /**
     * Returns the raw moveset with which the object was constructed.
     * 
     * @return  array
     */
    public function getRawMoveset()
    {
        return $this->moveset;
    }

    /**
     * Gets the matrix iteration number when the moveset was completed.
     * 
     * @return   int
     */
    public function getIterationNumber()
    {
        return $this->iterationNumber;
    }

    /**
     * Sets the matrix iteration number when the moveset was completed.
     * 
     * @param   int     $number     Matrix iteration number when returning the moveset.
     * 
     * @return  static
     */
    public function setIterationNumber(int $number)
    {
        $this->iterationNumber = abs($number);

        return $this;
    }

    /**
     * Gets the matrix total possible moves number.
     * 
     * @return   int
     */
    public function getTotalMoves()
    {
        return $this->totalMoves;
    }

    /**
     * Sets the matrix total possible moves number.
     * 
     * @param   int     $moves     Matrix total combinations count.
     * 
     * @return  static
     */
    public function setTotalMoves(int $moves)
    {
        $this->totalMoves = $moves;

        return $this;
    }

    /**
     * Returns the number of fitting solutions found so far.
     * 
     * @return   int
     */
    public function getSolutionsCount()
    {
        return $this->solutionsCount;
    }

    /**
     * Sets the number of fitting solutions found so far.
     * 
     * @param   int     $count     Solutions found.
     * 
     * @return  static
     */
    public function setSolutionsCount(int $count)
    {
        $this->solutionsCount = $count;

        return $this;
    }

    /**
     * Gets the verbose explanation of how the relocation did fit.
     * 
     * @return  ?string
     */
    public function getVerboseRelocation()
    {
        return $this->verboseRelocation;
    }

    /**
     * Sets the verbose explanation of how the relocation did fit.
     * 
     * @param   string  $relocationText     Verbose explanation string.
     * 
     * @return  static
     */
    public function setVerboseRelocation(string $relocationText)
    {
        $this->verboseRelocation = $relocationText;

        return $this;
    }

    /**
     * Returns the current booking registry.
     * 
     * @return  VBOBookingRegistry
     */
    public function getBooking()
    {
        return $this->registry;
    }

    /**
     * Calculates and returns the moveset signature.
     * 
     * @return  string
     */
    public function getSignature()
    {
        $signatureSteps = [];

        foreach ($this->moveset as $roomRecord) {
            // access current and initial room unit index
            $currentRoomUnitIndex = $roomRecord->getRoomUnitIndex();
            $initialRoomUnitIndex = $roomRecord->getInitialRoomUnitIndex();

            if (!$currentRoomUnitIndex || $currentRoomUnitIndex == $initialRoomUnitIndex) {
                // no moves for this room record
                continue;
            }

            // build move elements
            $moveElements = [
                $roomRecord->getBookingID(),
                $roomRecord->getRoomBookingID(),
                (int) $initialRoomUnitIndex,
                (int) $currentRoomUnitIndex,
            ];

            // represent move elements
            $signatureSteps[] = implode('.', $moveElements);
        }

        return implode('-', $signatureSteps);
    }

    /**
     * Returns a list of booking IDs involved in the moveset.
     * 
     * @param   bool    $unique     Whether to make the list unique.
     * 
     * @return  array
     */
    public function getBookingIDs(bool $unique = true)
    {
        $bidsInvolved = [];

        foreach ($this->moveset as $roomRecord) {
            // access current and initial room unit index
            $currentRoomUnitIndex = $roomRecord->getRoomUnitIndex();
            $initialRoomUnitIndex = $roomRecord->getInitialRoomUnitIndex();

            if (!$currentRoomUnitIndex || $currentRoomUnitIndex == $initialRoomUnitIndex) {
                // no moves for this room record
                continue;
            }

            // push involved booking ID
            $bidsInvolved[] = (int) $roomRecord->getBookingID();
        }

        if ($unique) {
            $bidsInvolved = array_values(array_unique($bidsInvolved));
        }

        return $bidsInvolved;
    }

    /**
     * Returns the current room record being relocated.
     * 
     * @return  VBOBookingSubunitRecord
     * 
     * @throws  Exception
     */
    public function getRelocatingRecord()
    {
        foreach ($this->moveset as $roomRecord) {
            if ($roomRecord->isRelocating()) {
                return $roomRecord;
            }
        }

        throw new Exception('Missing room relocating record in moveset.', 404);
    }

    /**
     * Describes the moveset operations.
     * 
     * @return  string
     */
    public function describeMoveset()
    {
        // moveset title
        $description = sprintf(
            "Fitting moveset found after %s matrix iterations over %s possible combinations.\n\n",
            number_format($this->getIterationNumber(), 0, ',', '.'),
            number_format($this->getTotalMoves(), 0, ',', '.')
        );

        // describe the booking to relocate
        foreach ($this->moveset as $roomRecord) {
            if ($roomRecord->isRelocating()) {
                // describe the record to relocate
                $description .= sprintf(
                    "Allocating booking ID %d (room reservation record ID %d) to sub-unit index number: [%d].\n\n",
                    $roomRecord->getBookingID(),
                    $roomRecord->getRoomBookingID(),
                    (int) $roomRecord->getRoomUnitIndex()
                );

                // just one
                break;
            }
        }

        // describe all necessary moves
        $stepCounter = 1;
        foreach ($this->moveset as $roomRecord) {
            if ($roomRecord->isRelocating()) {
                // do not describe it here
                continue;
            }

            // access current and initial room unit index
            $currentRoomUnitIndex = $roomRecord->getRoomUnitIndex();
            $initialRoomUnitIndex = $roomRecord->getInitialRoomUnitIndex();

            if (!$currentRoomUnitIndex || $currentRoomUnitIndex == $initialRoomUnitIndex) {
                // no moves for this room record
                continue;
            }

            // describe move
            $description .= sprintf(
                "Move booking ID %d (room reservation record ID %d): from index number [%d] --> to [%d]\n",
                $roomRecord->getBookingID(),
                $roomRecord->getRoomBookingID(),
                (int) $initialRoomUnitIndex,
                (int) $currentRoomUnitIndex
            );
        }

        return $description;
    }

    /**
     * Describes the moveset.
     * 
     * @return  string
     */
    public function __toString()
    {
        return $this->describeMoveset();
    }
}
