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
 * Obtain vars from arguments received in the layout file.
 * 
 * @var int                       $booking_id  The reservation ID.
 * @var VBOBookingSubunitMoveset  $moveset     The relocation moveset registry.
 * @var array                     $options     The relocation options.
 */
extract($displayData);

// access VBO application
$vbo_app = VikBooking::getVboApplication();

// get full booking details (booking registry object)
$booking = $moveset->getBooking();

// get the room booking record to relocate
$relocatingRecord = $moveset->getRelocatingRecord();

// make sure to set the proper room record index
$booking->setCurrentRoomIndex($relocatingRecord->getRecordIndex());

// access room details
$roomDetails = $booking->getRoomDetails($booking->getCurrentRoomID());

// access room "features"
$roomFeatures = VikBooking::getRoomParam('features', $roomDetails['params'] ?? '', []);
$roomFeaturesMap = [];
foreach ((array) $roomFeatures as $rind => $rfeatures) {
    foreach ($rfeatures as $fname => $fval) {
        if (strlen((string) $fval)) {
            // map first non-empty feature value for the current room index
            $roomFeaturesMap[$rind] = $fval;
            break;
        }
    }
}

// access relocating booking-customer details
list($customer_nominative, $booking_avatar_src, $booking_avatar_alt) = $booking->getBookingCustomerData();

// access room-level stay timestamps for the relocating record
list($stayFromTs, $stayToTs) = $booking->getStayTimestamps(true);
$stayFromInfo = getdate($stayFromTs);
$stayToInfo = getdate($stayToTs);

// build stay dates string
$stayFromParts = [
    date('d', $stayFromTs),
    VikBooking::sayMonth($stayFromInfo['mon'], true),
];
$stayToParts = [
    date('d', $stayToTs),
    VikBooking::sayMonth($stayToInfo['mon'], true),
];
if ($stayFromInfo['year'] != $stayToInfo['year']) {
    // push year on both parts
    $stayFromParts[] = date('y', $stayFromTs);
    $stayToParts[] = date('y', $stayToTs);
}
$stayDatesStr = sprintf('%s - %s', implode(' ', $stayFromParts), implode(' ', $stayToParts));
?>
<div class="vbo-room-reassignment-solution-wrap" data-signature="<?php echo JHtml::fetch('esc_attr', $moveset->getSignature()); ?>">
    <h4><?php echo JText::translate('VBO_REASSIGNMENT_SOLUTION'); ?></h4>
    <div class="vbo-room-reassignment-moves-help">
        <span><?php echo JText::sprintf('VBO_REASSIGNMENT_MATRIX_DETAILS', number_format($moveset->getIterationNumber(), 0, ',', '.'), number_format($moveset->getTotalMoves(), 0, ',', '.')); ?></span>
    </div>
    <div class="vbo-room-reassignment-head">
        <div class="vbo-room-reassignment-move-record">
            <div class="vbo-room-reassignment-booking">
                <div class="vbo-room-reassignment-booking-guest">
                <?php
                if (!empty($booking_avatar_src)) {
                    ?>
                    <span class="vbo-booking-guest-avatar"><img src="<?php echo $booking_avatar_src; ?>" class="vbo-tableaux-booking-avatar-img" decoding="async" loading="lazy" <?php echo (!empty($booking_avatar_alt) ? 'alt="' . htmlspecialchars($booking_avatar_alt) . '" ' : ''); ?>/></span>
                    <?php
                }
                ?>
                    <span class="vbo-booking-guest-name"><?php echo $customer_nominative; ?></span>
                    <span class="badge badge-info vbo-booking-guest-id"><?php echo $booking->getID(); ?></span>
                </div>
                <div class="vbo-room-reassignment-booking-info">
                    <span class="vbo-booking-info-party"><?php VikBookingIcons::e('users'); ?> <span><?php echo $booking->countTotalAdults() . ($booking->countTotalChildren() ? ' + ' . $booking->countTotalChildren() : ''); ?></span></span>
                    <span class="vbo-booking-info-nights"><?php VikBookingIcons::e('moon'); ?> <span><?php echo $booking->getTotalNights(); ?></span></span>
                    <span class="vbo-booking-info-dates"><?php VikBookingIcons::e('calendar'); ?> <span><?php echo $stayDatesStr; ?></span></span>
                </div>
            </div>
            <div class="vbo-room-reassignment-move">
                <div class="vbo-room-reassignment-move-details">
                    <span class="label label-info vbo-room-reassignment-move-room"><?php VikBookingIcons::e('bed'); ?> <?php echo $roomDetails['name'] ?? ''; ?></span>
                    <span class="label label-warning vbo-room-reassignment-move-from"><?php echo JText::translate('VBO_MISSING_SUBUNIT'); ?></span>
                    <span class="vbo-room-reassignment-move-icn"><?php VikBookingIcons::e('chevron-right'); ?></span>
                    <span class="label vbo-room-reassignment-move-to"><?php echo '#' . $relocatingRecord->getRoomUnitIndex() . (($roomFeaturesMap[$relocatingRecord->getRoomUnitIndex()] ?? null) ? ' - ' . $roomFeaturesMap[$relocatingRecord->getRoomUnitIndex()] : ''); ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="vbo-room-reassignment-moves">
        <h5><?php VikBookingIcons::e('random'); ?> <?php echo JText::translate('VBO_NECESSARY_MOVES'); ?></h5>
        <div class="vbo-room-reassignment-moves-help">
            <span><?php echo JText::translate('VBO_REASSIGNMENT_NO_STAY_CHANGES'); ?></span>
        </div>
        <div class="vbo-room-reassignment-moves-list">
        <?php
        foreach ($moveset->getRawMoveset() as $movesetNum => $roomRecord) {
            if ($roomRecord->isRelocating()) {
                // do not display the relocating room booking record again
                continue;
            }

            // access current and initial room unit index
            $currentRoomUnitIndex = $roomRecord->getRoomUnitIndex();
            $initialRoomUnitIndex = $roomRecord->getInitialRoomUnitIndex();

            if (!$currentRoomUnitIndex || $currentRoomUnitIndex == $initialRoomUnitIndex) {
                // do not display a room booking that will not move
                continue;
            }

            // wrap the room booking record into a registry
            $roomBookingRegistry = VBOBookingRegistry::getInstance(['id' => $roomRecord->getBookingID()]);

            // make sure to set the proper room record index
            $roomBookingRegistry->setCurrentRoomIndex($roomRecord->getRecordIndex());

            // access room booking customer details
            list($customer_nominative, $booking_avatar_src, $booking_avatar_alt) = $roomBookingRegistry->getBookingCustomerData();

            // access room-level stay timestamps for the relocating record
            list($stayFromTs, $stayToTs) = $roomBookingRegistry->getStayTimestamps(true);
            $stayFromInfo = getdate($stayFromTs);
            $stayToInfo = getdate($stayToTs);

            // build stay dates string
            $stayFromParts = [
                date('d', $stayFromTs),
                VikBooking::sayMonth($stayFromInfo['mon'], true),
            ];
            $stayToParts = [
                date('d', $stayToTs),
                VikBooking::sayMonth($stayToInfo['mon'], true),
            ];
            if ($stayFromInfo['year'] != $stayToInfo['year']) {
                // push year on both parts
                $stayFromParts[] = date('y', $stayFromTs);
                $stayToParts[] = date('y', $stayToTs);
            }
            $stayDatesStr = sprintf('%s - %s', implode(' ', $stayFromParts), implode(' ', $stayToParts));
            ?>
            <div class="vbo-room-reassignment-move-record">
                <div class="vbo-room-reassignment-booking">
                    <div class="vbo-room-reassignment-booking-guest">
                    <?php
                    if (!empty($booking_avatar_src)) {
                        ?>
                        <span class="vbo-booking-guest-avatar"><img src="<?php echo $booking_avatar_src; ?>" class="vbo-tableaux-booking-avatar-img" decoding="async" loading="lazy" <?php echo (!empty($booking_avatar_alt) ? 'alt="' . htmlspecialchars($booking_avatar_alt) . '" ' : ''); ?>/></span>
                        <?php
                    }
                    ?>
                        <span class="vbo-booking-guest-name"><?php echo $customer_nominative; ?></span>
                        <span class="badge badge-info vbo-booking-guest-id"><?php echo $roomBookingRegistry->getID(); ?></span>
                    </div>
                    <div class="vbo-room-reassignment-booking-info">
                        <span class="vbo-booking-info-party"><?php VikBookingIcons::e('users'); ?> <span><?php echo $roomBookingRegistry->countTotalAdults() . ($roomBookingRegistry->countTotalChildren() ? ' + ' . $roomBookingRegistry->countTotalChildren() : ''); ?></span></span>
                        <span class="vbo-booking-info-nights"><?php VikBookingIcons::e('moon'); ?> <span><?php echo $roomBookingRegistry->getTotalNights(); ?></span></span>
                        <span class="vbo-booking-info-dates"><?php VikBookingIcons::e('calendar'); ?> <span><?php echo $stayDatesStr; ?></span></span>
                    </div>
                </div>
                <div class="vbo-room-reassignment-move">
                    <div class="vbo-room-reassignment-move-details">
                        <span class="label vbo-room-reassignment-move-from"><?php
                        if ($initialRoomUnitIndex) {
                            echo '#' . $initialRoomUnitIndex . (($roomFeaturesMap[$initialRoomUnitIndex] ?? null) ? ' - ' . $roomFeaturesMap[$initialRoomUnitIndex] : '');
                        } else {
                            echo JText::translate('VBO_MISSING_SUBUNIT');
                        }
                        ?></span>
                        <span class="vbo-room-reassignment-move-icn"><?php VikBookingIcons::e('chevron-right'); ?></span>
                        <span class="label vbo-room-reassignment-move-to"><?php echo '#' . $currentRoomUnitIndex . (($roomFeaturesMap[$currentRoomUnitIndex] ?? null) ? ' - ' . $roomFeaturesMap[$currentRoomUnitIndex] : ''); ?></span>
                    </div>
                    <div class="vbo-room-reassignment-move-exclude" data-booking-id="<?php echo $roomBookingRegistry->getID(); ?>">
                        <span class="vbo-room-reassignment-exclude-lbl"><?php echo JText::translate('VBO_NEXT_SOLUTION'); ?></span>
                        <span
                            class="vbo-toggle-mini vbo-toggle-double-status vbo-tooltip vbo-tooltip-top"
                            data-tooltiptext="<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_INCLUDED')); ?>"
                            data-text-enabled="<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_INCLUDED')); ?>"
                            data-text-disabled="<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_EXCLUDED')); ?>"
                        ><?php echo $vbo_app->printYesNoButtons('skip_booking_id' . $movesetNum, JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0, '', ['red']); ?></span>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
        </div>
    </div>
</div>
