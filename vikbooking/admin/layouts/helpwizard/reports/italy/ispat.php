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
    
        <p>Puoi configurare un driver per automatizzare l'invio dei dati al portale ISPAT, come richiesto dalla normativa provinciale del Trentino.</p>

        <p>Risparmia tempo ed evita errori, garantendo una trasmissione puntuale e conforme dei dati statistici.</p>

        <p>Fai clic sul pulsante in basso per iniziare la configurazione.</p>

    <?php elseif ($autoexport): ?>

        <p>Le impostazioni di trasmissione sono state configurate con successo.</p>

        <p>Attualmente, il sistema supporta <strong>esclusivamente la trasmissione manuale</strong>.<br>Per automatizzare il processo, è necessario creare un <strong>cron job</strong> dedicato.</p>

        <p>Facendo clic sul pulsante sottostante, il sistema completerà la configurazione per te, in base alle impostazioni più appropriate per ISPAT. In pratica, ogni giorno il sistema invierà in modo automatico i dati sulle presenze turistiche del giorno precedente.</p>

        <p>Se non desideri impostare l'automatizzazione, puoi tranquillamente ignorare questa istruzione.</p>

    <?php endif; ?>

</div>
