// Simple File List - Copyright 2026
// Author: Mitchell Bennis | support@simplefilelist.com | https://simplefilelist.com
// License: GPLv2 or later | https://www.gnu.org/licenses/gpl-2.0.html

var eeSFL_isTouchscreen = false;
var eeSFL_ListID = 1;
var eeSFL_FileID = false;
var eeSFL_CheckEmail = false;
var eeSFL_FileDateAdded = false;
var eeSFL_FileDateChanged = false;

// Scroll down to #eeSFL_FileListTop
function eeSFL_ScrollToIt() {

	jQuery('html, body').animate({ scrollTop: jQuery('#eeSFL_FileListTop').offset().top }, 1000);

	return false;

}