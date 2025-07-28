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
    
        <p>Puedes configurar una integración con SES Hospedajes, la plataforma oficial para la comunicación de los datos de los huéspedes a las autoridades españolas.</p>
        
        <p>Automatiza el envío de los datos requeridos por la normativa local, evita errores manuales y mantén tu alojamiento siempre en regla.</p>
        
        <p>Haz clic en el botón de abajo para comenzar la configuración.</p>

    <?php elseif ($autoexport): ?>

        <p>La configuración de la transmisión se ha realizado con éxito.</p>

        <p>Actualmente, el sistema <strong>solo admite la transmisión manual</strong>.<br>Para automatizar el proceso, es necesario crear un <strong>cron job</strong> dedicado.</p>

        <p>Al hacer clic en el botón de abajo, el sistema completará la configuración por ti, según los ajustes más adecuados para SES Hospedajes. En la práctica, cada día el sistema enviará automáticamente los datos sobre las estadías turísticas y las reservas del día actual.</p>

        <p>Si no deseas configurar la automatización, puedes ignorar tranquilamente esta instrucción.</p>

    <?php endif; ?>

</div>
