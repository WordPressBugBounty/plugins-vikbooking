<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Display data attributes.
 * 
 * @var VBOHelpWizardInstruction  $instruction
 * @var bool                      $configured   Whether the report has been configured.
 * @var bool                      $autoexport   Whether the report supports an auto-export configuration.
 */
extract($displayData);

?>

<div class="vbo-help-wizard-instruction-description">

    <?php if (!$configured): ?>
    
        <p>Si l’establiment és a Catalunya, podeu generar un fitxer conforme per carregar-lo manualment al portal oficial dels Mossos d’Esquadra, tal com exigeix la normativa regional.</p>

        <p>L’eina us ajuda a preparar correctament les fitxes d’allotjats, evitant errors i estalviant temps en la compilació.</p>

        <p>Feu clic al botó de sota per començar la configuració.</p>

    <?php elseif ($autoexport): ?>

        <p>La configuració de generació s'ha completat correctament.</p>

        <p>Actualment, el sistema dels Mossos d'Esquadra només admet la <strong>transmissió manual</strong>.<br>No obstant això, pots millorar el procés creant una <strong>tasca programada (cron job)</strong> dedicada.</p>

        <p>En fer clic al botó següent, el sistema completarà la configuració per a tu, segons els paràmetres més adequats per als Mossos d'Esquadra. En concret, cada dia el sistema t'enviarà per correu electrònic un fitxer adjunt amb les dades d'ocupació turística que hauràs de carregar al portal dels Mossos d'Esquadra.</p>

        <p>Si no vols configurar l'automatització, pots ignorar aquesta instrucció sense cap problema.</p>

    <?php endif; ?>
    
</div>
