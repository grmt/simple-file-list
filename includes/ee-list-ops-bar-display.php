<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
// Simple File List - Copyright 2026
// Author: Mitchell Bennis | support@simplefilelist | https://simplefilelist.com
// License: GPLv2 or later | https://www.gnu.org/licenses/gpl-2.0.html

if($eeShowOps) {

	// Count Files and Folders
	if(!$eeAdmin) { // Otherwise already counted
		if($eeSFLF) {
			$eeSFLF->eeSFLF_CountFilesAndFolders();
		}
	}

	// Bulk Edit / Folder Creation Input Display
	 $eeOutput .= '<div class="eeSFL_ListOpsBar">

	<form action="' . $eeURL . '" method="POST">

	<input type="hidden" name="ee" value="1" />
	<input type="hidden" name="eeListID" value="' . $eeSFL->eeListID . '" />
	<input type="hidden" id="eeSFL_FileOpsFiles" name="eeSFL_FileOpsFiles" value="" />';

	 $eeOutput .= wp_nonce_field( 'ee-simple-file-list-file-ops-bar', 'ee-simple-file-list-file-ops-bar-nonce', TRUE, FALSE);

	 $eeOutput .= '

	<select id="eeSFL_FileOpsAction" name="eeSFL_FileOpsAction">';

		// Pro Only: Create Folder
		if($eeSFLF) {
			 $eeOutput .= '<option value="Folder" selected="selected">' . __('Create Folder', 'simple-file-list') . '</option>';
			$eeDefaultInputName = 'eeSFL_NewFolderName';
		} else {
			$eeDefaultInputName = 'eeSFL_Description';
			if($eeAdmin) {
				 $eeOutput .= '<option value="GetProFolder">' . __('Create Folder', 'simple-file-list') . '</option>';
			}
		}

		 $eeOutput .= '<option value="Description"' . ($eeSFLF ? '' : ' selected="selected"') . '>' . __('Apply Description', 'simple-file-list') . '</option>';

		// Pro Only: Move Items
		if($eeSFLF && $eeSFL->eeFolderCount) {
			 $eeOutput .= '<option value="Move">' . __('Move Items', 'simple-file-list') . '</option>';
		}

		 $eeOutput .= '
		<option value="Download">' . __('Download Items', 'simple-file-list') . '</option>
		<option value="Delete" class="eeWarning">' . __('Delete Items', 'simple-file-list') . '</option>

	</select>

	<input type="text" id="eeSFL_FileOpsActionInput" name="' . $eeDefaultInputName . '" value="" placeholder="" required="required" />

	<select id="eeSFL_MoveToFolder" name="eeSFL_MoveToFolder">

		<option value="">' . __('Choose Folder', 'simple-file-list') . '</option>';

		// Pro Only: Folder destinations
		if($eeSFLF) {
			$eeDestinations = explode('|', $eeSFLF->eeSFLF_MoveToFolderOptions() );

			if($eeSFL->eeCurrentFolder AND !$eeSFL->eeShortcodeFolder) {
				 $eeOutput .= '
				<option value="/">' . __('Main Folder', 'simple-file-list') . '</option>';
			}

			foreach( $eeDestinations as $eeKey => $eeValue ) {

				if($eeValue) {

					if(strpos($eeSFL->eeCurrentFolder . '/', $eeValue) !== 0) { // Don't show self

						 $eeOutput .= '
						<option value="' . $eeValue . '">' . $eeValue . '</option>';
					}
				}
			}
		}

	 $eeOutput .= '</select>

	<span class="eeHide" id="eeSFL_NewFolderNamePlaceholder">' . __('Insert new folder name here', 'simple-file-list') . '</span>
		<span class="eeHide" id="eeSFL_ZipFileName">' . __('File-Archive', 'simple-file-list') . '-' . gmdate('Y-m-d-H-i') . '</span>
		<span class="eeHide" id="eeSFL_DeleteText">' . __('Delete all of the selected items.', 'simple-file-list') . '</span>
		<span class="eeHide" id="eeSFL_DescriptionPlaceholder">' . __('Insert description text here', 'simple-file-list') . '</span>';

	if($eeAdmin && !$eeSFLF) {
		 $eeOutput .= '<span class="eeHide" id="eeSFL_GetProURL">' . esc_url(admin_url('admin.php?page=' . eeSFL_PluginSlug . '&tab=getpro')) . '</span>';
	}

	 $eeOutput .= '

		<input class="button" type="submit" id="eeSFL_ListOpsBarGo" name="eeSFL_ListOpsBarGo" value="' . __('GO', 'simple-file-list') . '" />

	<br class="eeClearFix" />

	</form>
	</div>';


} elseif($eeSFL->eeListSettings['AllowBulkFileDownload'] == 'YES') { // Bulk Downloading Only

	// Bulk Edit / Folder Creation Input Display
	 $eeOutput .= '<div class="eeSFL_BulkDownloadBar">

	<form action="' . $eeURL . '" method="POST">

	<input type="hidden" name="ee" value="1" />
	<input type="hidden" name="eeListID" value="' . $eeSFL->eeListID . '" />
	<input type="hidden" name="eeSFL_FileOpsAction" value="Download" />
	<input type="hidden" id="eeSFL_FileOpsFiles" name="eeSFL_FileOpsFiles" value="" />';

	 $eeOutput .= wp_nonce_field( 'ee-simple-file-list-file-ops-bar', 'ee-simple-file-list-file-ops-bar-nonce', TRUE, FALSE);

	 $eeOutput .= '<label for="eeSFL_FileOpsActionInput">' . __('Download Files', 'simple-file-list') . '</label><input required type="text" id="eeSFL_FileOpsActionInput" name="eeSFL_ZipFileName" value="' . __('File-Archive', 'simple-file-list') . '-' . gmdate('Y-m-d-H-i') . '" />

	<input class="button" type="submit" id="eeSFL_ListOpsBarGo" name="eeSFL_ListOpsBarGo" value="' . __('GO', 'simple-file-list') . '" />

	<br class="eeClearFix" />

	</form>
	</div>';



}

?>