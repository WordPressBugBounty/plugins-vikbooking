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
    
        <p>Puoi attivare un'integrazione con la Trentino Guest Card per inviare automaticamente i dati degli ospiti e generare le card in modo semplice e veloce.</p>

        <p>Offri un servizio in più ai tuoi clienti e risparmia tempo nella gestione, rispettando gli standard richiesti dalla Provincia autonoma di Trento.</p>

        <p>Fai clic sul pulsante in basso per iniziare la configurazione.</p>

    <?php elseif ($autoexport): ?>

        <p>Le impostazioni di trasmissione sono state configurate con successo.</p>

        <p>Attualmente, il sistema supporta <strong>esclusivamente la trasmissione manuale</strong>.<br>Per automatizzare il processo, è necessario creare un <strong>cron job</strong> dedicato.</p>

        <p>Facendo clic sul pulsante sottostante, il sistema completerà la configurazione per te, in base alle impostazioni più appropriate per Trentino Guest Card. In pratica, ogni giorno il sistema genererà in modo automatico le guest card per le prenotazioni con check-in per il giorno successivo.</p>

        <p>Se non desideri impostare l'automatizzazione, puoi tranquillamente ignorare questa istruzione.</p>

    <?php endif; ?>

</div>
