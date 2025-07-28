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
    
        <p>Em Portugal, os estabelecimentos de alojamento são obrigados a comunicar os dados dos hóspedes ao SEF – Serviço de Estrangeiros e Fronteiras.</p>
        
        <p>Pode gerar um ficheiro XML conforme as especificações do SEF, pronto para ser carregado manualmente no portal oficial (Sistema de Informação de Boletins de Alojamento – SIBA).</p>
        
        <p>Clique no botão abaixo para iniciar a configuração.</p>

    <?php elseif ($autoexport): ?>

        <p>As configurações de geração foram configuradas com sucesso.</p>

        <p>Atualmente, o sistema do SEF suporta <strong>exclusivamente o envio manual</strong>.<br>No entanto, é possível melhorar o processo criando um <strong>cron job</strong> dedicado.</p>

        <p>Ao clicar no botão abaixo, o sistema completará a configuração para você, com base nas definições mais apropriadas para o SEF. Na prática, todos os dias o sistema enviará um anexo por e-mail contendo os dados de presenças turísticas a serem carregados no portal do SEF.</p>

        <p>Se não quiser configurar a automatização, pode simplesmente ignorar esta instrução.</p>

    <?php endif; ?>

</div>
