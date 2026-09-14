<?php
// Simple File List - File Sending - Copyright 2026
// Author: Mitchell Bennis | support@simplefilelist.com | https://simplefilelist.com
// License: GPLv2 or later | https://www.gnu.org/licenses/gpl-2.0.html

if ( ! defined( 'WP_CONTENT_DIR' ) ) exit; // Exit if accessed directly

class eeSFLE_class {

	public $eeSFLE_SettingsDefault = array(

		'AllowFrontSend' => 'YES',
		'BccFileSender' => 'NO'
	);


	// The Pop-Up Entry Form
	public function eeSFLE_EmailSendForm() {

		global $eeSFL;

		$eeCount = is_array($eeSFL->eeAllFiles) ? count($eeSFL->eeAllFiles) : 0;

		// Send Files Overlay - Hidden until the Send link is clicked
		 $eeOutput = '

		<script>
			var eeSFL_SendNonce = "' . wp_create_nonce( "eeSFL_SendNonce" ) . '";
			var eeSFL_SendingSpinnerURL = "' . $eeSFL->eeEnvironment['pluginURL'] . 'images/sending.gif";
		</script>

		<div class="eeSFL_Modal" id="eeSFL_Modal_SendFiles">
		<div class="eeSFL_ModalBackground"></div>
		<div class="eeSFL_ModalBody">

			<button id="eeSFL_Modal_Send_Close" class="eeSFL_ModalClose">&times;</button>

			<h1>' . __('Send Files by Email', 'simple-file-list') . '</h1>';

			if($eeCount > 1) {
				 $eeOutput .= '<button class="button eeFont100p" onclick="eeSFLE_Send_AddMoreFiles();">' . __('Add More Files', 'simple-file-list') . '</button> ';
			}

			 $eeOutput .= '

			<p>' . __('Send an email with links to files. Add more files if needed.', 'simple-file-list') . '</p>

			<p id="eeSFLE_SendTheseFilesList">' . __('Sending:', 'simple-file-list') . ' <em></em></p>

			<form id="eeSFLE_SendFileForm" action="' . $eeSFL->eeSFL_GetThisURL() . '" method="POST">

				<input type="hidden" name="eeListID" value="' . $eeSFL->eeListID . '" />

				<fieldset id="eeSFLE_MessageDetials">

				<h2>' . __('Message Details', 'simple-file-list') . '</h2>

					<label for="eeSFL_SendFrom">' . __('Your Address', 'simple-file-list'). '</label>
					<input required type="email" name="eeSFL_SendFrom" value="" size="64" id="eeSFL_SendFrom" />
					<small class="eeSFL_ModalNote">' . __('Enter your email address.', 'simple-file-list') . '</small>

					<label for="eeSFL_SendTo">' . __('The TO Address', 'simple-file-list'). '</label>
					<input required type="email" name="eeSFL_SendTo" value="" size="64" id="eeSFL_SendTo" />
					<small class="eeSFL_ModalNote">' . __('Enter the address to send the message to.', 'simple-file-list') . '</small>

					<label for="eeSFL_SendCc">' . __('The CC Address', 'simple-file-list'). '</label>
					<input type="text" name="eeSFL_SendCc" value="" size="64" id="eeSFL_SendCc" />
					<small class="eeSFL_ModalNote">' . __('Enter an address to copy the message to.', 'simple-file-list') . '</small>

					<label for="eeSFL_SendSubject">' . __('The Subject', 'simple-file-list'). '</label>
					<input type="text" name="eeSFL_SendSubject" value="" size="64" id="eeSFL_SendSubject" />
					<small class="eeSFL_ModalNote">' . __('Enter the email subject.', 'simple-file-list') . '</small>

					<label for="eeSFL_SendMessage">' . __('The Message', 'simple-file-list'). '</label>
					<textarea name="eeSFL_SendMessage" id="eeSFL_SendMessage" cols="64" rows="5"></textarea>

					<br class="eeClear" />

					<span id="SendFilesButton">
						<button class="button" onclick="eeSFLE_DoFileSend()">' . __('Send', 'simple-file-list') . '</button>
					</span>

				</fieldset>';


				if( $eeCount > 1 ) {

					 $eeOutput .= '

					<fieldset id="eeSFLE_SendMoreFiles">

					<h3>' . __('Add More Files', 'simple-file-list') . '</h3>

				<table>
				 <tbody>';

				if(is_array($eeSFL->eeAllFiles)) {
					foreach( $eeSFL->eeAllFiles as $eeKey => $eeFileArray) {

						$eeFileNameDisplay = $eeFileArray['FilePath'];						if($eeSFL->eeCurrentFolder) {
							if( strpos($eeFileArray['FilePath'], $eeSFL->eeCurrentFolder) === 0 ) {
								$eeFileNameDisplay = str_replace($eeSFL->eeCurrentFolder, '', $eeFileArray['FilePath']); // Remove this folder's name from the display
							}
						}

						if($eeFileArray['FileExt'] != 'folder') { // We can't send folders, yet.

							 $eeOutput .= '

							<tr>
								<td class="eeSFLE_AddFileID_' . $eeKey . '"><input type="checkbox" name="eeSFLE_SendTheseFiles[]" value="' . urlencode($eeFileArray['FilePath']) . '"/></td>
								<td>' . $eeFileNameDisplay . '</td>
							</tr>';
						}
					}

					 $eeOutput .= '</tbody>
						</table>

						<p><button class="button eeInline eeFont100p" onclick="eeSFLE_Send_AddTheseFiles();">' . __('Add These Files', 'simple-file-list') . '</button>
							<button class="button eeInline eeFont100p" onclick="eeSFLE_Send_AddMoreCancel();">' . __('Cancel', 'simple-file-list') . '</button></p>

					</fieldset>';

					}

				}

			 $eeOutput .= '

			</form>

		</div>
		</div>';

		return  $eeOutput;

	}





	public function eeSFLE_SendFilesEmail() {

		// Nonce Check
		if( !check_ajax_referer( 'eeSFL_SendNonce', 'eeSecurity', FALSE ) ) { return 'ERROR 98 - Send Files Ajax'; }

		global $eeSFL, $eeSFLA;

		$eeFilesArray = FALSE;
		$eeFileLinks = '';
		$eeSubject = FALSE;
		$eeBcc = FALSE;

		// Get List Settings
		if(isset($_POST['id'])) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashing before sanitization on next line
			$eeTemp = wp_unslash($_POST['id']);
			$eeSFL->eeListID = sanitize_text_field($eeTemp);
			$eeSFL->eeSFL_GetSettings($eeSFL->eeListID);

			if(!isset($eeSFL->eeListSettings['FileListDir'])) {
				return __('Bad List ID', 'simple-file-list');
			}

		} else {
			return __('Missing List ID', 'simple-file-list');
		}

		// Verify that front-end file sending is enabled for this list
		if($eeSFL->eeListSettings['AllowFrontSend'] != 'YES') {
			eeSFL_Debug_Log("ERROR: Front-end file sending is disabled", 'Send', $eeSFL->eeListID);
			return __('File sending is not enabled', 'simple-file-list');
		}

		// From
		if(isset($_POST['from'])) { // 1 Required
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- sanitize_email handles slashing
			$eeFrom = sanitize_email($_POST['from']);
			if(!$eeFrom) { return __('Bad Address', 'simple-file-list') . ': FROM'; }
		} else {
			return __('Missing Address', 'simple-file-list') . ': FROM';
		}

		// To
		if(isset($_POST['to'])) { // 1 Required
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- sanitize_email handles slashing
			$eeTo = sanitize_email($_POST['to']);
			if(!$eeTo) { return __('Bad Address', 'simple-file-list') . ': TO'; }
		} else {
			return __('Missing Address', 'simple-file-list') . ': TO';
		}

		// CC
		if(isset($_POST['cc'])) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- sanitize_email handles slashing
			$eeCc = sanitize_email($_POST['cc']);
		}

		// BCC ?
		if($eeSFL->eeListSettings['BccFileSender'] == 'YES') { $eeBcc = $eeFrom; }

		// Subject
		if(isset($_POST['subject'])) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashing before sanitization on next line
			$eeTemp = wp_unslash($_POST['subject']);
			$eeSubject = wp_strip_all_tags($eeTemp);
		}
		if(!$eeSubject) { $eeSubject = __('File Notification', 'simple-file-list'); }

		// Message
		if(isset($_POST['message'])) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashing before sanitization on next line
			$eeTemp = wp_unslash($_POST['message']);
			$eeMessage = wp_strip_all_tags($eeTemp);
		}

		// Files
		if(isset($_POST['files'])) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Unslashing before sanitization on next line
			$eeTemp = wp_unslash($_POST['files']);
			$eeFiles = sanitize_text_field($eeTemp);
			if(strpos($eeFiles, ',')) {
				$eeFilesArray = explode(',', $eeFiles);
			} else {
				$eeFilesArray = array($eeFiles);
			}

		} else {
			return __('No Files to Send', 'simple-file-list');
		}

		if( is_array($eeFilesArray) ) { // The files array checkboxes

			foreach($eeFilesArray as $eeFile) {

				if (isset($eeSFLA->eePluginName)) {
					if($eeSFL->eeListSettings['Mode'] != 'Normal') {
						$eeFileLinks .= $eeSFL->eeEnvironment['wpSiteURL'] . 'ee-get-file/?list=' . $eeSFL->eeListID . '&file=' . trim($eeFile) . PHP_EOL . PHP_EOL;
					}
				} else {
					$eeFileLinks .= $eeSFL->eeEnvironment['wpSiteURL'] . $eeSFL->eeListSettings['FileListDir'] . trim($eeFile) . PHP_EOL . PHP_EOL;
				}
			}

		} else {
			return __('Bad File Array', 'simple-file-list');
		}

		// Footer
		$eeFooter = PHP_EOL .  PHP_EOL . '';

		// The Body
		$eeMessage .= PHP_EOL .  PHP_EOL . $eeFileLinks . $eeFooter; // with Custom Footer

		// Email Headers
		$eeHeaders = eeSFL_ReturnHeaderString($eeFrom, $eeCc, $eeBcc);

		// TO DO -- Allow files to be attached if less than X MB
		// $eeAttached = array();

		// Send the Message
		if( wp_mail($eeTo, $eeSubject, $eeMessage, $eeHeaders)) { // , $eeAttached
			return 'SUCCESS';
		} else {
			return 'Email Failed to Send';
		}
	}








	// Settings Inputs
	public function eeSFLE_SettingsInputsDisplay() {

		global $eeSFL;

		 $eeOutput = '

		<div class="eeSettingsTile">

			<h2>' . __('Sending Files', 'simple-file-list') . '</h2>

			<fieldset>

				<legend>' . __('Allow File Sending', 'simple-file-list') . '</legend>

				<div><label for="eeAllowFrontSend">' . __('Allow', 'simple-file-list') . '</label>
				<input type="checkbox" name="eeAllowFrontSend" value="YES" id="eeAllowFrontSend"';

				if( $eeSFL->eeListSettings['AllowFrontSend'] == 'YES') {  $eeOutput .= ' checked="checked"'; }

				 $eeOutput .= ' /></div>

				<div class="eeNote">' . __('Allow front-side users to send email messages containing links to files.', 'simple-file-list') . '</div>

			</fieldset>

			<fieldset>

				<legend>' . __('Blind Copy Self', 'simple-file-list') . '</legend>

				<div><label for="eeBccFileSender">BCC ' . __('Self', 'simple-file-list') . '</label>
				<input type="checkbox" name="eeBccFileSender" value="YES" id="eeBccFileSender"';

				if( $eeSFL->eeListSettings['BccFileSender'] == 'YES') {  $eeOutput .= ' checked="checked"'; }

				 $eeOutput .= ' /></div>

				<div class="eeNote">' . __('Send a hidden copy of the message to the your reply address.', 'simple-file-list') . ' (BCC)</div>

			</fieldset>

		</div>';

		return  $eeOutput;

	}



}


?>