<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
// Simple File List - Copyright 2026
// Author: Mitchell Bennis | support@simplefilelist.com | https://simplefilelist.com
// License: GPLv2 or later | https://www.gnu.org/licenses/gpl-2.0.html

// The Main File List Display Page Used Both Front and Back


eeSFL_Debug_Log("Loading File List Display ...", 'List');

// Initialization is such a sensation
$eeReScan = FALSE;
$eeProceed = FALSE;
$eeSFL_ListClass = 'eeSFL'; // The basic list's CSS class. Extensions might change this.
$eeClass = ''; // Meaning, CSS class
$eeSFL_AllowFrontManage = 'NO'; // Front-side freedom
$eeUploadedFiles = array();
$eeForceSort = FALSE; // Force re-sort of file list
if(empty($eeURL)) { $eeURL = $eeSFL->eeSFL_GetThisURL(); }
$eeDateFormat = get_option('date_format');
$eeSFL_FolderDepth = 0;
$eeMessages = array('File List Loading');

if(!isset($eeSFL_HideName)) { $eeSFL_HideName = FALSE; }
if(!isset($eeSFL_HideType)) { $eeSFL_HideType = FALSE; }

// Who is accessing this list?
$eeThisUser = get_current_user_id();
eeSFL_Debug_Log('- USER ID: ' . $eeThisUser , 'List', $eeSFL->eeListID);

// Load the file list
if( empty($eeSFL->eeAllFiles) OR $eeForceSort) { $eeSFL->eeSFL_GetFileList($eeForceSort); }

// echo '<pre>(' . $eeSFL->eeListID . ') '; print_r($eeSFL->eeListSettings); echo '</pre>'; // exit;
// echo '<pre>'; print_r($eeSFL->eeAllFiles); echo '</pre>'; exit;

eeSFL_Debug_Log('- LIST RUN #' . $eeSFL->eeListRun, 'List', $eeSFL->eeListID);

// Front-side folder — use the shared method to resolve eeCurrentFolder
if( $eeSFL->eeShortcodeFolder ) {

	if( $eeSFLF && !$eeSFLF->eeSFL_ResolveCurrentFolder() ) {
		$eeSFL->eeAllFiles = array(); // Shortcode folder not found; show nothing.
	}

} else {
	$eeSFL->eeShortcodeFolder = FALSE;
}


// This List Folder We Are In
if( $eeSFL->eeCurrentFolder ) { // Sub-Folder

	// Security
	$eeSFL->eeSFL_DetectUpwardTraversal($eeSFL->eeListSettings['FileListDir'] . $eeSFL->eeCurrentFolder); // Die if foolishness

	// How deep are we?  Counting slashes. 1/2/3/
	$eeSFL_FolderDepth = substr_count($eeSFL->eeCurrentFolder, '/') + 1; // /uploads/simple-file-list/ being #1

} else { // Top level folder

	$eeSFL->eeCurrentFolder = '';
}

// echo '<pre>'; print_r($eeSFL->eeAllFiles); echo '</pre>'; exit;

// Process Folder Creation or Bulk Ops
include($eeSFL->eeEnvironment['pluginDir'] . 'includes/ee-list-ops-bar-process.php');


// Upload Results
if( $eeSFL_Uploaded ) { // Reduce down to upload Results

	foreach( $eeSFL->eeAllFiles as $eeKey => $eeFileArray ) {

		if( in_array($eeFileArray['FilePath'], $eeSFLU->eeUploadedFiles) ) {
			$eeSFL->eeDisplayFiles[$eeKey] = $eeFileArray;
		}
	}

	if(count($eeSFL->eeDisplayFiles) == 0) {
		$eeSFL->eeAllFiles = array();
		$eeSFL->eeUserMessages['errors'][] = 'Upload Processing Issue. The file was likely uploaded, but you will need to refresh the list to see it.';
		$eeSFL->eeUserMessages['errors'][] = $eeSFLU->eeUploadedFiles;
	}

	$eeSFLS = FALSE; // Hide the search form and pagination

} else { // Reduce full list down to just the items for display <<<---------------------------

	eeSFL_Debug_Log('Scanning File Array...', 'List', $eeSFL->eeListID);

	// Remove sub-folder files from the full array
	foreach( $eeSFL->eeAllFiles as $eeKey => $eeFileArray ) {

		if($eeSFL->eeCurrentFolder) { // Sub-Folder

			// Show items that are direct children of current folder
			// Path starts with current folder AND is not the folder itself
			if(strpos($eeFileArray['FilePath'], $eeSFL->eeCurrentFolder) === 0 && $eeFileArray['FilePath'] != $eeSFL->eeCurrentFolder) {

				// Only show direct children (one level deeper)
				$eeRemainder = substr($eeFileArray['FilePath'], strlen($eeSFL->eeCurrentFolder));
				$eeSlashCount = substr_count($eeRemainder, '/');

				// Direct files have 0 slashes: "file.jpg"
				// Direct folders have 1 slash: "SubFolder/"
				// Nested items have 2+ slashes: "SubFolder/file.jpg" or "SubFolder/SubFolder2/"
				if($eeSlashCount == 0 || ($eeSlashCount == 1 && substr($eeRemainder, -1) == '/')) {
					eeSFL_Debug_Log($eeFileArray['FilePath'] . ' -> SHOWING (direct child of ' . $eeSFL->eeCurrentFolder . ')', 'List', $eeSFL->eeListID);
					$eeSFL->eeDisplayFiles[$eeKey] = $eeFileArray; // Add and maintain key
				} else {
					eeSFL_Debug_Log($eeFileArray['FilePath'] . ' -> SKIPPING (nested too deep)', 'List', $eeSFL->eeListID);
				}
			}

		} else { // Main Folder

			if( substr_count($eeFileArray['FilePath'],'/') < 2 ) { // Show only up to the first slash

				if( strpos($eeFileArray['FilePath'],'/') === FALSE AND $eeFileArray['FileExt'] != 'folder' ) { // Files in the main folder

					$eeSFL->eeDisplayFiles[$eeKey] = $eeFileArray; // Add and maintain key

				} elseif( $eeFileArray['FileExt'] == 'folder' AND strpos($eeFileArray['FilePath'],'/') ) { // Only first-level folders

					$eeSFL->eeDisplayFiles[$eeKey] = $eeFileArray; // Add and maintain key

				}
			}
		}
	}

	unset($eeKey);
	unset($eeFileArray);

	// Extension Check
	if($eeSFLS AND isset($_POST['eeSFLS_Searching'])) {
		// Verify nonce for search form submission (Missing nonce security)
		if (!wp_verify_nonce(isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '', 'ee-search-form') && !is_admin()) {
			// Skip search processing if nonce verification fails
		} else {
			include(WP_PLUGIN_DIR . '/ee-simple-file-list-search/includes/ee-search-processor.php'); // Run the Search Processor

			if(isset($_POST['eeSFLS_Searching'])) {

				if($eeSFLF) {
					if($eeSFL->eeShortcodeFolder) {
						$eeSFLF->eeSFLF_CountFilesAndFolders($eeSFL->eeShortcodeFolder);
					} else {
						$eeSFLF->eeSFLF_CountFilesAndFolders();
					}
				}
			}
		} // Close nonce verification block
	}

	// Extension Check
	if($eeSFLA) {

		if( $eeSFLA->eeSFLA_GetNextAvailableID() > 2 ) {

			if($eeAdmin) { $eeShowCopyLink = TRUE; }
			if($eeSFL->eeListSettings['AllowCopyToList'] == 'YES') { $eeShowCopyLink = TRUE; }

			// Build the Select Input
			$eeArray = $eeSFLA->eeSFLA_GetAllListBasics();
			unset($eeArray[$eeSFL->eeListID]); // Remove this list

			$eeSelectListInput = '
			<select name="eeListID">';

			foreach( $eeArray as $eeKey => $eeList ) {
				$eeSelectListInput .= '
				<option value="' . $eeKey . '">' . $eeList['ListTitle'] . '</option>';
			}

			$eeSelectListInput .= '
			</select>';
		}

		if($eeAdmin AND (
			(isset($eeSFL->eeListSettings['Mode']) && $eeSFL->eeListSettings['Mode'] == 'RESTRICTED') OR
			(isset($eeSFL->eeListSettings['Mode']) && $eeSFL->eeListSettings['Mode'] == 'USER')
		)) {

			$eeShowGrantLink = TRUE;
		}
	}
}

// echo '<pre>'; print_r($eeSFL->eeDisplayFiles); echo '</pre>'; exit;


// Extension Check
if($eeSFLS) {
	include(WP_PLUGIN_DIR . '/ee-simple-file-list-search/includes/ee-pagination-processing.php'); // Run Pagination Processing
}


// DISPLAY ===================================================

 $eeOutput .= '

<span id="eeSFL_FileListTop"><!-- Simple File List - File List Top --></span>

<div class="eeSFL"';

if($eeSFL->eeListRun == 1) { $eeOutput .= ' id="eeSFL"'; } // 3/20 - Legacy for user CSS

 $eeOutput .= '>';

// User Messaging
 $eeOutput .= $eeSFL->eeSFL_ResultsNotification();

// Check if user needs to log in for RESTRICTED or USER mode
$eeSFLA_ShowLoginForm = false;
$eeSFLA_HasPublicFiles = false;

if( !is_admin() &&
    !get_current_user_id() &&
    isset($eeSFL->eeListSettings['Mode']) &&
    ($eeSFL->eeListSettings['Mode'] == 'RESTRICTED' || $eeSFL->eeListSettings['Mode'] == 'USER') ) {

    $eeSFLA_ShowLoginForm = true;

    // Check if there are any publicly accessible files
    if($eeSFLA && !empty($eeSFL->eeDisplayFiles)) {

        foreach($eeSFL->eeDisplayFiles as $eeFileArray) {
            if($eeSFLA->eeSFLA_FileFirewall($eeFileArray) !== FALSE) {
                $eeSFLA_HasPublicFiles = true;
                break;
            }
        }
    }

    // If no public files, show only login form and return early
    if(!$eeSFLA_HasPublicFiles) {
         $eeOutput .= eeSFLA_DisplayLoginForm();
         $eeOutput .= '</div><!-- .eeSFL -->';
        return  $eeOutput;
    }

    // Otherwise, we'll show the list and add login form at the bottom
}

// Page Setup ----------
 $eeOutput .= '
<span class="eeHide" id="eeSFL_ID">' . $eeSFL->eeListID . '</span>

<script>
	var eeSFL_ThisURL = "' . esc_js($eeURL) . '";
	var eeSFL_ListID = ' . $eeSFL->eeListID . ';
	var eeSFL_PluginURL = "' . esc_js($eeSFL->eeEnvironment['pluginURL']) . '";
	var eeSFL_FileListDir = "' . esc_js($eeSFL->eeListSettings['FileListDir']) . '";
	var eeSFL_SubFolder = "' . esc_js(eeSFL_NormalizeSlashes($eeSFL->eeCurrentFolder)) . '";
	var eeSFL_ShortcodeFolder = "' . esc_js(eeSFL_NormalizeSlashes($eeSFL->eeShortcodeFolder)) . '";
	var eeSFL_ShowListStyle = "' . esc_js($eeSFL->eeListSettings['ShowListStyle']) . '";
</script>

';


// Uploaded Files
if(!is_admin() AND $eeSFL_Uploaded) {

	// Build the back button URL with proper folder parameter
	// Start with clean base URL (remove existing parameters)
	$eeBaseURL = strtok($eeURL, '?');
	$eeBackURL = $eeBaseURL . '?ee=1';

	if(!empty($eeSFL->eeCurrentFolder)) {
		$eeBackURL .= '&eeFolder=' . urlencode(rtrim($eeSFL->eeCurrentFolder, '/'));
	}

	 $eeOutput .= '<p class="eeSFL_ListMeta"><a href="' . $eeBackURL . '" class="button eeButton" id="eeSFL_BacktoFilesButton">&larr; ' .
		__('Back to the Files', 'simple-file-list') . '</a></p>';

	$eeSendFilesArray = $eeSFL->eeDisplayFiles; // Restrict to just what was uploaded
}


// echo '<pre>'; print_r($eeSFL->eeDisplayFiles); echo '</pre>'; exit;

// Extension Check - Only show search if there are files to display
if($eeSFLS && !empty($eeSFL->eeDisplayFiles)) {
	include(WP_PLUGIN_DIR . '/ee-simple-file-list-search/includes/ee-search-form.php');
}

// Conditions for File Operations Display -------------

$eeShowOps = FALSE; // Assume No
$eeShowingResults = FALSE;

if( $eeAdmin ) {
	$eeShowOps = TRUE; // Always show to on back-side
} else {
	// Folder creation does work for multiple list runs per page. But Moving does not, so creation is disabled for now.
	if( $eeSFL->eeListSettings['AllowFrontManage'] == 'YES' AND $eeSFL->eeListRun == 1 ) { $eeShowOps = TRUE; }
}

// Never show for upload confirmation or search results
if( isset($_POST['eeSFL_Upload']) ) { // isset($_POST['eeSFLS_Searching']) OR
	// Verify nonce for upload form submission (Missing nonce security)
	if (!wp_verify_nonce(isset($_POST['ee-simple-file-list-upload-form-nonce']) ? sanitize_text_field(wp_unslash($_POST['ee-simple-file-list-upload-form-nonce'])) : '', 'ee-simple-file-list-upload-form') && !is_admin()) {
		// Skip upload confirmation processing if nonce verification fails
		$eeShowingResults = FALSE;
		$eeShowOps = TRUE;
	} else {
		$eeShowingResults = TRUE;
		$eeShowOps = FALSE;
	}
	$eeSFL->eeListSettings['AllowFrontManage'] = 'NO';
	$eeSFL->eeListSettings['AllowBulkFileDownload'] = 'NO';
}

// The List Operations Bar
if( ($eeShowOps OR $eeSFL->eeListSettings['AllowBulkFileDownload'] == 'YES') AND !$eeShowingResults) {
	include($eeSFL->eeEnvironment['pluginDir'] . 'includes/ee-list-ops-bar-display.php'); // File List Managment Controls
}


// Download a Folder - Form completed and submitted via javascript
if($eeAdmin OR ($eeSFL->eeListSettings['AllowFolderDownload'] ?? '') == 'YES') {

	 $eeOutput .= '
	<form action="' . $eeURL . '" method="POST" id="eeSFL_DownloadFolderForm">';
		 $eeOutput .= wp_nonce_field( 'ee-simple-file-list-zip-folder', 'ee-simple-file-list-zip-folder-nonce', TRUE, FALSE);
		 $eeOutput .= '<input type="hidden" name="eeListID" value="' . $eeSFL->eeListID . '" />
		<input type="hidden" id="eeSFL_FolderDownloadZipFileName" name="eeSFL_ZipFileName" value="" />
		<input type="hidden" name="eeSFL_FileOpsAction" value="Download" />
		<input type="hidden" id="eeSFL_FolderToDownload" name="eeSFL_FileOpsFiles" value="" />
		<input type="hidden" name="eeSFL_DownloadFolderForm" value="1" />
		<input type="hidden" name="ee" value="1" />
		<input type="hidden" name="eeSFL_ListOpsBarGo" value="GO" />
	</form>';
}


// Folder Navigation Display
if($eeSFL->eeCurrentFolder) {

	// Determine if we should show breadcrumb
	$eeShowBreadcrumb = false;

	// Normalize folder paths (remove trailing slashes) to avoid false positives
	$eeCurrentFolderNorm = rtrim($eeSFL->eeCurrentFolder, '/');
	$eeShortcodeFolderNorm = $eeSFL->eeShortcodeFolder ? rtrim($eeSFL->eeShortcodeFolder, '/') : '';

	if ($eeShortcodeFolderNorm) {
		// If shortcode folder set, show breadcrumb only when CURRENT is different
		// from the shortcode folder AND the current path is beneath it.
		if ($eeCurrentFolderNorm !== $eeShortcodeFolderNorm && strpos($eeCurrentFolderNorm, $eeShortcodeFolderNorm) === 0) {
			$eeShowBreadcrumb = true;
		}
	} else {
		// No shortcode folder = home level, so show breadcrumb if we're in any folder
		if ($eeCurrentFolderNorm !== '') {
			$eeShowBreadcrumb = true;
		}
	}

	// Evaluate search/nonce/admin conditions with explicit grouping to avoid
	// operator precedence surprises. Show breadcrumb when:
	// 1) eeShowBreadcrumb is true AND (admin OR ShowBreadCrumb == 'YES') AND NOT searching
	// OR
	// 2) searching AND nonce invalid AND not admin (special case to show breadcrumb in certain search states)
	$is_searching = isset($_POST['eeSFLS_Searching']);
	$nonce_invalid = $is_searching && !wp_verify_nonce(isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '', 'ee-search-form');

	if (( $eeShowBreadcrumb && ( $eeAdmin || $eeSFL->eeListSettings['ShowBreadCrumb'] == 'YES' ) && ! $is_searching )
		|| ( $nonce_invalid && ! is_admin() )) {
		if($eeSFLF) {
			 $eeOutput .= $eeSFLF->eeSFLF_BreadCrumb($eeSFL->eeCurrentFolder, $eeSFL->eeShortcodeFolder);
		}
	}
}

// $eeSFL->eeFileCount = 0; // Reset
$eeListPosition = FALSE; // eeSFLS

$eeSFL->eeItemCount = count($eeSFL->eeDisplayFiles);
eeSFL_Debug_Log('- Listing ' . $eeSFL->eeItemCount . ' Items ...', 'List', $eeSFL->eeListID);

if( $eeSFL->eeItemCount ) {

	if($eeAdmin OR $eeSFL->eeListSettings['ShowListStyle'] == 'TABLE') {

		include($eeSFL->eeEnvironment['pluginDir'] . 'includes/ee-list-display-table.php');

	} elseif($eeSFL->eeListSettings['ShowListStyle'] == 'TILES') {

		include($eeSFL->eeEnvironment['pluginDir'] . 'includes/ee-list-display-tiles.php');

	} else {

		include($eeSFL->eeEnvironment['pluginDir'] . 'includes/ee-list-display-flex.php');

	}

	// Extension Check
	if($eeSFLS) { // Pagination Controls
		include(WP_PLUGIN_DIR . '/ee-simple-file-list-search/includes/ee-pagination-display.php');
	}


// No Files Found and Not Empty Search
} elseif( $eeSFL->eeFileCount === 0 AND $eeSFL->eeFolderCount === 0 ) {

	eeSFL_Debug_Log("There are no files here :-(", 'List');

	if($eeAdmin) {
		 $eeOutput .= '<div><p>&#8593; ' . __('Upload some files and they will appear here.', 'simple-file-list') . '</p></div>';
	}
}

//  $eeOutput .= '<pre>' . print_r($eeSFL->eeDisplayFiles, TRUE) . '</pre>';


// This allows javascript to access the count
 $eeOutput .= '

<p class="eeHide"><span id="eeSFL_FilesCount">' . $eeSFL->eeFileCount . '</span></p>

</div><!-- END .eeSFL -->';


// Modal Input -------------------------

 $eeOutput .= '
<span class="eeHide" id="eeSFL_ActionNonce">' . wp_create_nonce('eeSFL_ActionNonce') . '</span>
<span class="eeHide" id="eeSFL_Modal_FileID"></span>';

if($eeAdmin OR $eeSFL->eeListSettings['AllowFrontManage'] == 'YES') {

	 $eeOutput .= '

	<div class="eeSFL_Modal" id="eeSFL_Modal_EditFile">
	<div class="eeSFL_ModalBackground"></div>
	<div class="eeSFL_ModalBody">

		<button class="eeSFL_ModalClose">&times;</button>

		<h1>' . __('Edit Item', 'simple-file-list') . '</h1>

		<p class="eeSFL_ModalFilePath"></p>

		<p class="eeSFL_ModalFileDetails">' .
		__('Added', 'simple-file-list') . ': <span id="eeSFL_FileDateAdded" >???</span> | ' .
		__('Changed', 'simple-file-list') . ': <span id="eeSFL_FileDateChanged" >???</span> | ' .
		__('Size', 'simple-file-list') . ': <span id="eeSFL_FileSize">???</span>
		</p>

		<label for="eeSFL_FileNameNew">' . __('Item Name', 'simple-file-list') . '</label>
		<input type="text" id="eeSFL_FileNameNew" name="eeSFL_FileNameNew" value="??" size="64" />
		<small class="eeSFL_ModalNote">' . __('Change the name.', 'simple-file-list') . ' ' . __('Some characters are not allowed. These will be automatically replaced.', 'simple-file-list') . '</small>';

		 $eeOutput .= '<label for="eeSFL_FileNiceNameNew">' . __('File Nice Name', 'simple-file-list') . '</label>
		<input type="text" id="eeSFL_FileNiceNameNew" name="eeSFL_FileNiceNameNew" value="" size="64" />
		<small class="eeSFL_ModalNote">' . __('Enter a name that will be shown in place of the real file name.', 'simple-file-list') . ' ' . __('You may use special characters not allowed in the file name.', 'simple-file-list') . '</small>';

		 $eeOutput .= '<label for="eeSFL_FileDescriptionNew">' . __('Item Description', 'simple-file-list') . '</label>
		<textarea cols="64" rows="3" id="eeSFL_FileDescriptionNew" name="eeSFL_FileDescriptionNew"></textarea>
		<small class="eeSFL_ModalNote">' . __('Add a description.', 'simple-file-list') . ' ' . __('Use this field to describe this item and apply keywords for searching.', 'simple-file-list') . '</small>

		<h4>' . __('Item Date Added', 'simple-file-list') . '</h4>

		<div class="eeSFL_DateNew">
		<label>' . __('Year', 'simple-file-list') . '<input min="1970" max="' . gmdate('Y') . '" type="number" name="eeSFL_FileDateAddedYearNew" value="" id="eeSFL_FileDateAddedYearNew" /></label>
		<label>' . __('Month', 'simple-file-list') . '<input min="1" max="12" type="number" name="eeSFL_FileDateAddedMonthNew" value="" id="eeSFL_FileDateAddedMonthNew" /></label>
		<label>' . __('Day', 'simple-file-list') . '<input min="1" max="31" type="number" name="eeSFL_FileDateAddedDayNew" value="" id="eeSFL_FileDateAddedDayNew" /></label>
		</div>
		<small class="eeSFL_ModalNote">' . __('Change the date added to the list.', 'simple-file-list') . '</small>

		<h4>' . __('Item Date Changed', 'simple-file-list') . '</h4>

		<div class="eeSFL_DateNew">
		<label>' . __('Year', 'simple-file-list') . '<input min="1970" max="' . gmdate('Y') . '" type="number" name="eeSFL_FileDateChangedYearNew" value="" id="eeSFL_FileDateChangedYearNew" /></label>
		<label>' . __('Month', 'simple-file-list') . '<input min="1" max="12" type="number" name="eeSFL_FileDateChangedMonthNew" value="" id="eeSFL_FileDateChangedMonthNew" /></label>
		<label>' . __('Day', 'simple-file-list') . '<input min="1" max="31" type="number" name="eeSFL_FileDateChangedDayNew" value="" id="eeSFL_FileDateChangedDayNew" /></label>
		</div>
		<small class="eeSFL_ModalNote">' . __('Change date the file was last modified.', 'simple-file-list') . '</small>

		<button class="button" onclick="eeSFL_FileEditSaved()">' . __('Save', 'simple-file-list') . '</button>

	</div>
	</div>';

	// Move File

	$eeDestinations = $eeSFLF ? $eeSFLF->eeSFLF_MoveToFolderOptions() : ''; // Array of Folders

	 $eeOutput .= '

	<script>
		var eeSFL_FolderDestinations = "' . $eeDestinations . '";
		var eeSFL_MoveNonce = "' . wp_create_nonce( "eeSFL_MoveNonce" ) . '";
	</script>

	<div class="eeSFL_Modal" id="eeSFL_Modal_MoveFile">
	<div class="eeSFL_ModalBackground"></div>
	<div class="eeSFL_ModalBody">

		<button id="eeSFL_Modal_Move_Close" class="eeSFL_ModalClose">&times;</button>

		<h1>' . __('Move Item', 'simple-file-list') . '</h1>

		<p class="eeSFL_ModalFilePath"></p>

		<label>' . __('Choose Folder Destination', 'simple-file-list') . '</label>
		<select name="eeSFL_Destination" id="eeSFL_Destination">
			<option value="">' . __('Choose Folder', 'simple-file-list') . '</option>
		</select>
		<small class="eeSFL_ModalNote">' . __('Choose where to move this item.', 'simple-file-list') . ' ' . __('The move will not complete if the item name is found at the destination.', 'simple-file-list') . '</small>


	<button class="button" onclick="eeSFL_FileDoMove()">' . __('Move', 'simple-file-list') . '</button>

	</div>
	</div>

	';

}

// Extension Check
if(is_object($eeSFLE) && method_exists($eeSFLE, 'eeSFLE_EmailSendForm')) {

	if( $eeSFL->eeShortcodeFolder ) { // Front-side

		if( $eeSFL->eeListRun == 1 AND $eeSFL->eeListSettings['AllowFrontSend'] == 'YES') {
			if($eeSFLF) {
				$eeSFL->eeAllFiles = $eeSFLF->eeSFLF_GetItemsBelow($eeSFL->eeShortcodeFolder); // Only show items below the shortcode-defined folder
			}
			 $eeOutput .= $eeSFLE->eeSFLE_EmailSendForm();
		}

	} else {

		 $eeOutput .= $eeSFLE->eeSFLE_EmailSendForm();
	}
}

// Extension Check
if($eeSFLA) {

	if($eeAdmin OR $eeSFL->eeListSettings['AllowCopyToList'] == 'YES') {
		include(WP_PLUGIN_DIR . '/ee-simple-file-list-access/includes/eeSFLA_ModalCopy.php');
	}

	if($eeAdmin OR $eeSFLA->eeSFLA_IsListOwner) {
		include(WP_PLUGIN_DIR . '/ee-simple-file-list-access/includes/eeSFLA_ModalAccess.php');
	}
}

// Show login form at the bottom if there are public files but user isn't logged in
if(isset($eeSFLA_ShowLoginForm) && $eeSFLA_ShowLoginForm && isset($eeSFLA_HasPublicFiles) && $eeSFLA_HasPublicFiles) {
	 $eeOutput .= '<div class="eeSFL_LoginPrompt" style="margin-top: 2em; padding-top: 2em; border-top: 1px solid #ddd;">';
	 $eeOutput .= '<p>' . __('Log in to see additional files.', 'simple-file-list') . '</p>';
	 $eeOutput .= eeSFLA_DisplayLoginForm();
	 $eeOutput .= '</div>';
}


eeSFL_Debug_Log("File Listing Completed.", 'List');

$eeMessages[] = $eeURL;
$eeMessages[] = 'Listing ' . $eeSFL->eeFileCount . ' Items';
do_action('eeSFL_Hook_Loaded', $eeMessages);

?>