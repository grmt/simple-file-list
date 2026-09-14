// Upon page load completion...
jQuery(document).ready(function($) {

	// console.log(eeSFLM_Vars);	// Look for Media Files and Add Player
	jQuery( '.eeSFL_Item' ).each(function( index ) {

		// Get the name of this row's ID
		var eeSFLM_ThisID = jQuery(this).attr('id');

		if (eeSFLM_ThisID !== undefined) { // Like in the header row

			var eeSFLM_FileMIME = jQuery('#' + eeSFLM_ThisID + " .eeSFL_FileMimeType" ).text(); // Get the File MIME Type

			if(eeSFLM_FileMIME) {

				// Get File Info
				var eeSFLM_ID = eeSFLM_ThisID.replace( /^\D+/g, ''); // Get just the number
				var eeSFLM_FileName = jQuery('#' + eeSFLM_ThisID + " span.eeSFL_RealFileName" ).text(); // Get the File Name
				var eeSFLM_FileLink = jQuery('#' + eeSFLM_ThisID + " a.eeSFL_FileName" ).attr('href'); // Get the File Link
				var eeSFLM_Ext = eeSFLM_FileName.split('.'); // Get the File Extension

				// Detect Type
				var eeSFLM_MediaType = eeSFLM_FileMIME.split('/');
				var eeSFLM_Player = eeSFLM_MediaType[0].toUpperCase(); // audio or video

				// Setup for Playback
				if(eeSFLM_Player == 'AUDIO' || eeSFLM_Player == 'VIDEO') {

					// console.log(eeSFLM_FileMIME + ' Media File Found: ' + eeSFLM_FileName);

					// Change "Open" to "Play"
					var playLabel = (typeof eeSFLM_Vars !== 'undefined') ? eeSFLM_Vars.eePlayLabel : 'Play';
					jQuery('#' + eeSFLM_ThisID + ' a.eeSFL_FileOpen').text(playLabel);
					jQuery('#' + eeSFLM_ThisID + ' a.eeSFL_FileOpen').removeAttr('target');
					jQuery('#' + eeSFLM_ThisID + ' a.eeSFL_FileOpen').addClass('eeSFLM_Play' + eeSFLM_Player);
					jQuery('#' + eeSFLM_ThisID + ' a.eeSFL_FileOpen').attr('data-ee-id', eeSFLM_ID);

					jQuery('#' + eeSFLM_ThisID + ' a.eeSFL_FileName').addClass('eeSFLM_Play' + eeSFLM_Player);
					jQuery('#' + eeSFLM_ThisID + ' a.eeSFL_FileName').removeAttr('target');
					jQuery('#' + eeSFLM_ThisID + ' a.eeSFL_FileName').attr('data-ee-id', eeSFLM_ID);

					jQuery('#' + eeSFLM_ThisID + ' .eeSFL_Thumbnail a').addClass('eeSFLM_Play' + eeSFLM_Player);
					jQuery('#' + eeSFLM_ThisID + ' .eeSFL_Thumbnail a').removeAttr('target');
					jQuery('#' + eeSFLM_ThisID + ' .eeSFL_Thumbnail a').attr('data-ee-id', eeSFLM_ID);



					// Inline Audio Player
					if(eeSFLM_Player == 'AUDIO' && typeof eeSFLM_Vars !== 'undefined' && eeSFLM_Vars.eeAudioEnabled == 'YES') {


						// Build and Add the Player
						var eeSFLM_AudioPlayer = '<audio controls id="eeSFLM_AudioPlayer' + eeSFLM_ID + '" class="eeSFL_AudioPlayer" style="';

						if(typeof eeSFLM_Vars !== 'undefined' && eeSFLM_Vars.eeAudioHeight >= 1) { eeSFLM_AudioPlayer += 'height:' + eeSFLM_Vars.eeAudioHeight + 'px;'; }

						eeSFLM_AudioPlayer += '"><source src="' + eeSFLM_FileLink + '" type="' + eeSFLM_FileMIME + '">Not Supported</audio>';

						if(eeSFL_ShowListStyle == 'TABLE') {

							jQuery('#' + eeSFLM_ThisID + " td.eeSFL_FileName" ).append('<div class="eeSFL_AudioPlayerWrap">' + eeSFLM_AudioPlayer +  '</div>');

						} else if(eeSFL_ShowListStyle == 'TILES') {

							jQuery('#' + eeSFLM_ThisID + " .eeSFL_FileDesc" ).append('<div class="eeSFL_AudioPlayerWrap">' + eeSFLM_AudioPlayer +  '</div>');

						} else if(eeSFL_ShowListStyle == 'FLEX') {

							jQuery('#' + eeSFLM_ThisID + " .eeSFL_FileLink" ).append('<div class="eeSFL_AudioPlayerWrap">' + eeSFLM_AudioPlayer +  '</div>');

						}

					}


				}
			}
		}
	});


	// Thumb, Name or Play, Go Ahead and Play (using event delegation)
	jQuery(document).on('click', '.eeSFLM_PlayAUDIO', function(event) {

		var eeSFLM_ThisID = jQuery(this).attr('data-ee-id');

		if(eeSFLM_ThisID !== undefined) {

			var audioPlayer = document.getElementById('eeSFLM_AudioPlayer' + eeSFLM_ThisID);
			if(audioPlayer) {
				event.preventDefault();
				audioPlayer.play();
			}
			// If no player exists, let the link work normally (open/download file)
		}

	});


	// VIDEO
	// Produce the Video Player (using event delegation)
	jQuery(document).on('click', '.eeSFLM_PlayVIDEO', function(event) {

		event.preventDefault();

		var eeSFLM_ThisID = jQuery(this).attr('data-ee-id');
		var eeSFLM_ThisURL = jQuery(this).attr('href');
		var eeSFLM_FileMIME = jQuery("eeSFL_FileID-" + eeSFLM_ThisID + " .eeSFL_FileMimeType").text();

		if(eeSFLM_ThisID !== undefined) {

			// alert('PLAY');

			var eeSFLM_VideoPlayer = '<div class="eeSFLM_Modal" id="eeSFLM_Video"><div class="eeSFLM_ModalBackground"></div><div class="eeSFLM_ModalBody">';

			eeSFLM_VideoPlayer += '<button class="eeSFLM_ModalClose">&times;</button>';

			var browserWarning = (typeof eeSFLM_Vars !== 'undefined') ? eeSFLM_Vars.eeBrowserWarning : 'Browser is Not Compatible';
			eeSFLM_VideoPlayer += '<video id="eeSFLM_VideoPlayer" autoplay controls><source src="' + eeSFLM_ThisURL + '" type="' + eeSFLM_FileMIME + '">' + browserWarning + '</video>';

			eeSFLM_VideoPlayer += '</div></div>';

			jQuery('.eeSFL').append(eeSFLM_VideoPlayer);

			jQuery('#eeSFLM_Video').show();

		}

	});

	// Video Modal Close Handlers - Enhanced for Firefox in WordPress Admin
	jQuery(document).on('click mousedown touchstart', '.eeSFLM_ModalClose', function(e) {
		e.preventDefault();
		e.stopPropagation();

		var videoElement = document.getElementById('eeSFLM_VideoPlayer');

		// IMMEDIATE ACTION: Mute and hide first, then clean up
		if (videoElement) {			// STEP 1: Immediate audio cutoff
			videoElement.muted = true;
			videoElement.volume = 0;

			// STEP 2: Hide modal immediately
			jQuery('#eeSFLM_Video').hide();

			// STEP 3: Aggressive cleanup with setTimeout for Firefox
			setTimeout(function() {
				try {
					videoElement.pause();
					videoElement.currentTime = 0;
					videoElement.src = 'data:video/mp4;base64,'; // Empty data URI
					videoElement.load();
					videoElement.remove(); // Remove from DOM entirely
				} catch(e) {
					// Silently handle cleanup errors
				}
				// Force remove the entire modal
				jQuery('#eeSFLM_Video').remove();
			}, 10); // Very short delay to let Firefox process
		} else {
			// No video element found, just remove modal
			jQuery('#eeSFLM_Video').remove();
		}		// Remove modal immediately
		jQuery('#eeSFLM_Video').remove();
	});

	// Close video when clicking on modal background
	jQuery(document).on('click', '.eeSFLM_ModalBackground', function() {
		var videoElement = document.getElementById('eeSFLM_VideoPlayer');
		if (videoElement) {
			// Same Firefox workaround
			videoElement.muted = true;
			videoElement.volume = 0;
			jQuery('#eeSFLM_Video').hide();

			setTimeout(function() {
				try {
					videoElement.pause();
					videoElement.currentTime = 0;
					videoElement.src = 'data:video/mp4;base64,';
					videoElement.load();
					videoElement.remove();
				} catch(e) {}
				jQuery('#eeSFLM_Video').remove();
			}, 10);
		} else {
			jQuery('#eeSFLM_Video').remove();
		}
	});

}); // END Ready Function