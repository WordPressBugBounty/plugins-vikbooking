<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * AgenziaEntrate driver main settings
 */

$vbo_app = VikBooking::getVboApplication();
$data 	 = !is_array($data) ? array() : $data;

?>

<input type="hidden" name="driveraction" value="saveSettings" />

<fieldset class="vbo-driver-fieldset">
	<legend class="adminlegend">Generazione Fatture</legend>
	<table cellspacing="1" class="admintable table">
		<tbody>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Generazione automatica</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Generazione automatica fatture', 'content' => 'Se abilitato, la generazione di una fattura analogica in formato PDF per qualsiasi prenotazione farà eseguire questo driver per la generazione della copia della fattura elettronica in automatico. La stessa cosa è valida se si usa un Cron Job per la generazione automatica delle fatture.')); ?>
				</td>
				<td><?php echo $vbo_app->printYesNoButtons('automatic', JText::translate('VBYES'), JText::translate('VBNO'), (int)$data['automatic'], 1, 0); ?></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Progressivo invio</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Progressivo invio', 'content' => 'Questo è il numero progressivo di invio che il sistema considera ed incrementa per la generazione delle fatture elettroniche. Cambialo solo se sai quello che stai facendo. Dovresti cambiarlo se per esempio usi anche un altro software per la trasmissione a tuo nome di fatture elettroniche. La prossima generazione di una fattura elettronica userà questo numero progressivo.')); ?>
				</td>
				<td><input type="number" name="progcount" min="1" value="<?php echo isset($data['progcount']) && !empty($data['progcount']) ? $data['progcount'] : '1'; ?>" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Numero Prossima Fattura</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Numero Prossima Fattura', 'content' => 'Questo è il numero che verrà usato per la prossima generazione di fatture elettroniche. È lo stesso numero progressivo usato per la generazione di fatture analogiche in formato PDF, e verrà incrementato in automatico alla generazione di ogni prossima fattura.')); ?>
				</td>
				<td><input type="number" name="invoiceinum" min="1" value="<?php echo VikBooking::getNextInvoiceNumber(); ?>" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Suffisso Numero Fattura</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Suffisso Numero Fattura', 'content' => 'Puoi impostare un suffisso da usare per il numero fattura, per ottenere un numero tipo &quot;numero/E/'.date('Y').'&quot;, per distinguere la numerazione di una fattura elettronica.')); ?>
				</td>
				<td><input type="text" name="einvsuffix" value="<?php echo isset($data['params']) && !empty($data['params']['einvsuffix']) ? $data['params']['einvsuffix'] : '/E/'.date('Y'); ?>" size="10" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Data Fatture</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Data Fatture', 'content' => 'Puoi scegliere quale tipo di data attribuire alle prossime fatture elettroniche generate. Il sistema può usare la data di prenotazione come data fattura, oppure la data odierna in cui viene generata la fattura.')); ?>
				</td>
				<td>
					<select name="einvdttype">
						<option value="today"<?php echo isset($data['params']) && !empty($data['params']['einvdttype']) && $data['params']['einvdttype'] == 'today' ? ' selected="selected"' : ''; ?>>Data odierna</option>
						<option value="ts"<?php echo isset($data['params']) && !empty($data['params']['einvdttype']) && $data['params']['einvdttype'] == 'ts' ? ' selected="selected"' : ''; ?>>Data prenotazione</option>
					</select>
				</td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Modifica Fatture Esistenti</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Numerazione e data fatture esistenti', 'content' => 'Quando devi rigenerare una fattura elettronica già esistente, magari per aver dovuto modificare dei dettagli del cliente o della prenotazione, puoi scegliere se usare il numero e la data della fattura precedente, o se usare una nuova numerazione e data. In entrambi i casi, la vecchia fattura elettronica sarà obliterata e NON eliminata.')); ?>
				</td>
				<td>
					<select name="einvexnumdt">
						<option value="new"<?php echo isset($data['params']) && !empty($data['params']['einvexnumdt']) && $data['params']['einvexnumdt'] == 'new' ? ' selected="selected"' : ''; ?>>Usa nuova numerazione e data</option>
						<option value="old"<?php echo isset($data['params']) && !empty($data['params']['einvexnumdt']) && $data['params']['einvexnumdt'] == 'old' ? ' selected="selected"' : ''; ?>>Usa numerazione e data precedenti</option>
					</select>
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>

<fieldset class="vbo-driver-fieldset">
	<legend class="adminlegend">Trasmissione Fatture</legend>
	<table cellspacing="1" class="admintable table">
		<tbody>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Indirizzo PEC SdI</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'PEC Sistema di Interscambio', 'content' => 'La trasmissione delle fatture elettroniche verso il Sistema di Interscambio dell\'Agenzia delle Entrate (SdI) avviene tramite PEC. Tuttavia, solo per il primo invio della prima fattura, si deve usare un indirizzo PEC generico (sdi01@pec.fatturapa.it). Dopodiché il Sistema di Interscambio ti comunicherà via PEC il nuovo indirizzo email che ti è stato assegnato per la trasmissione. Una volta che ti viene comunicato il nuovo indirizzo PEC del SdI, cambia quello predefinito per i successivi invii di fatture elettroniche.')); ?>
				</td>
				<td><input type="text" name="pecsdi" value="<?php echo isset($data['params']) && !empty($data['params']['pecsdi']) ? $data['params']['pecsdi'] : 'sdi01@pec.fatturapa.it'; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Indirizzo PEC Società</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Indirizzo PEC', 'content' => 'Inserisci il tuo indirizzo PEC. Sarà inserito nella sezione Contatti Cedente/Prestatore di ogni fattura elettronica, e sarà usato per inviare le fatture al Sistema di Interscambio.')); ?>
				</td>
				<td><input type="email" name="pec" value="<?php echo isset($data['params']) && !empty($data['params']['pec']) ? $data['params']['pec'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Host PEC</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Host Casella PEC', 'content' => 'Il tuo fornitore di PEC dovrebbe comunicarti questo valore. Si tratta del valore Host della tua casella PEC per accederci in lettura o scrittura. Se leggi la tua PEC tramite un client di posta elettronica, allora ti è già stato richiesto questo valore per ricevere o inviare email certificate. Il valore Host è un indirizzo, per esempio per le caselle PEC di Aruba è smtps.pec.aruba.it')); ?>
				</td>
				<td><input type="text" name="hostpec" value="<?php echo isset($data['params']) && !empty($data['params']['hostpec']) ? $data['params']['hostpec'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Porta PEC</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Porta Casella PEC', 'content' => 'Il tuo fornitore di PEC dovrebbe comunicarti questo valore. Si tratta della porta richiesta per la connessione alla casella. Di solito è 587 per le connessioni sicure.')); ?>
				</td>
				<td><input type="number" name="portpec" value="<?php echo isset($data['params']) && !empty($data['params']['portpec']) ? $data['params']['portpec'] : '587'; ?>" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Password PEC</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Password Casella PEC', 'content' => 'Inserisci la password che usi per leggere o inviare messaggi di posta elettronica certificata dalla tua casella PEC.')); ?>
				</td>
				<td><input type="text" name="pwdpec" id="pwdpec" value="<?php echo isset($data['params']) && !empty($data['params']['pwdpec']) ? $data['params']['pwdpec'] : ''; ?>" size="30" /></td>
			</tr>
		</tbody>
	</table>
</fieldset>

<fieldset class="vbo-driver-fieldset">
	<legend class="adminlegend">Dati Fiscali Generali</legend>
	<table cellspacing="1" class="admintable table">
		<tbody>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Ragione Sociale</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Denominazione', 'content' => 'Inserisci la tua denominazione (Ragione Sociale) solo in caso di Persona Non Fisica. In caso di Persona Fisica lasciare questo campo vuoto e popolare i campi Nome e Cognome sotto.')); ?>
				</td>
				<td><input type="text" name="companyname" value="<?php echo isset($data['params']) && !empty($data['params']['companyname']) ? $data['params']['companyname'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> 
					<b>Nome e Cognome</b> 
					<?php echo $vbo_app->createPopover(array('title' => 'Nome e Cognome', 'content' => 'Da popolare solo in caso di Persona Fisica. In caso di azienda (Persona Non Fisica) lasciare questi campi vuoti e popolare il campo Ragione Sociale sopra.')); ?>
				</td>
				<td>
					<input type="text" name="name" value="<?php echo isset($data['params']) && !empty($data['params']['name']) ? $data['params']['name'] : ''; ?>" size="12" placeholder="Nome" />
					<input type="text" name="lname" value="<?php echo isset($data['params']) && !empty($data['params']['lname']) ? $data['params']['lname'] : ''; ?>" size="12" placeholder="Cognome" />
				</td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b>Partita IVA</b> </td>
				<td><input type="text" name="vatid" value="<?php echo isset($data['params']) && !empty($data['params']['vatid']) ? $data['params']['vatid'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b>Codice Fiscale</b> </td>
				<td><input type="text" name="fisccode" value="<?php echo isset($data['params']) && !empty($data['params']['fisccode']) ? $data['params']['fisccode'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b>Regime Fiscale</b> </td>
				<td>
					<select name="regimfisc" style="max-width: 250px;">
						<option value="RF01"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF01' ? ' selected="selected"' : ''; ?>>Regime Ordinario</option>
						<option value="RF02"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF02' ? ' selected="selected"' : ''; ?>>Regime Contribuenti minimi (art. 1, c.96-117, L.244/07)</option>
						<option value="RF04"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF04' ? ' selected="selected"' : ''; ?>>Regime Agricoltura e attività connesse e pesca (art.34 e 34-bis, D.P.R. 633/72)</option>
						<option value="RF05"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF05' ? ' selected="selected"' : ''; ?>>Regime Vendita sali e tabacchi (art.74, c.1, D.P.R. 633/72)</option>
						<option value="RF06"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF06' ? ' selected="selected"' : ''; ?>>Regime Commercio fiammiferi (art.74, c.1, DPR 633/72)</option>
						<option value="RF07"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF07' ? ' selected="selected"' : ''; ?>>Regime Editoria (art.74, c.1, D.P.R. 633/72)</option>
						<option value="RF08"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF08' ? ' selected="selected"' : ''; ?>>Regime Gestione servizi telefonia pubblica (art.74, c.1, D.P.R. 633/72)</option>
						<option value="RF09"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF09' ? ' selected="selected"' : ''; ?>>Regime Rivendita documenti di trasporto pubblico e di sosta (art.74, c.1, D.P.R. 633/72)</option>
						<option value="RF10"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF10' ? ' selected="selected"' : ''; ?>>Regime Intrattenimenti, giochi e altre attività di cui alla tariffa allegata al D.P.R. 640/72 (art.74, c.6, D.P.R. 633/72)</option>
						<option value="RF11"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF11' ? ' selected="selected"' : ''; ?>>Regime Agenzie viaggi e turismo (art.74-ter, D.P.R. 633/72)</option>
						<option value="RF12"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF12' ? ' selected="selected"' : ''; ?>>Regime Agriturismo (art.5, c.2, L. 413/91)</option>
						<option value="RF13"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF13' ? ' selected="selected"' : ''; ?>>Regime Vendite a domicilio (art.25-bis, c.6, D.P.R. 600/73)</option>
						<option value="RF14"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF14' ? ' selected="selected"' : ''; ?>>Regime Rivendita beni usati, oggetti d'arte, d'antiquariato o da collezione (art.36, DL 41/95)</option>
						<option value="RF15"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF15' ? ' selected="selected"' : ''; ?>>Regime Agenzie di vendite all'asta di oggetti d'arte, antiquariato o da collezione (art.40-bis, DL 41/95)</option>
						<option value="RF16"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF16' ? ' selected="selected"' : ''; ?>>IVA per cassa P.A. (art.6, c.5, D.P.R. 633/72)</option>
						<option value="RF17"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF17' ? ' selected="selected"' : ''; ?>>IVA per cassa (art. 32-bis, DL 83/2012)</option>
						<option value="RF18"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF18' ? ' selected="selected"' : ''; ?>>Altro</option>
						<option value="RF19"<?php echo isset($data['params']) && !empty($data['params']['regimfisc']) && $data['params']['regimfisc'] == 'RF19' ? ' selected="selected"' : ''; ?>>Regime forfettario (art.1, c.54-89, L. 190/2014)</option>
					</select>
				</td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b>Indirizzo</b> </td>
				<td><input type="text" name="address" value="<?php echo isset($data['params']) && !empty($data['params']['address']) ? $data['params']['address'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b>Numero Civico</b> </td>
				<td><input type="text" name="nciv" value="<?php echo isset($data['params']) && !empty($data['params']['nciv']) ? $data['params']['nciv'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b>CAP</b> </td>
				<td><input type="text" name="zip" value="<?php echo isset($data['params']) && !empty($data['params']['zip']) ? $data['params']['zip'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b>Comune</b> </td>
				<td><input type="text" name="city" value="<?php echo isset($data['params']) && !empty($data['params']['city']) ? $data['params']['city'] : ''; ?>" size="30" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b>Provincia</b> </td>
				<td><input type="text" name="province" value="<?php echo isset($data['params']) && !empty($data['params']['province']) ? $data['params']['province'] : ''; ?>" size="4" maxlength="2" /></td>
			</tr>
			<tr>
				<td width="200" class="vbo-config-param-cell"> <b>Telefono</b> </td>
				<td><input type="text" name="phone" value="<?php echo isset($data['params']) && !empty($data['params']['phone']) ? $data['params']['phone'] : ''; ?>" size="30" /></td>
			</tr>
		</tbody>
	</table>
</fieldset>

<script type="text/javascript">
/** some browsers may be overriding any password field with what was saved, so we change the type of field after 1 second **/
setTimeout(function() {
	document.getElementById('pwdpec').setAttribute('type', 'password');
}, 1000);
</script>
