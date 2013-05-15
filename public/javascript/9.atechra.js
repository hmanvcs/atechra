// Make columns heights equal
function equalHeight(group) {
    tallest = 0;
    group.each(function() {
        thisHeight = $(this).height();
        if(thisHeight > tallest) {
            tallest = thisHeight;
        }
    });
    group.height(tallest);
}
// function to open a pop-up window with specific width
function openPopUpWindow(URL) {
	aWindow=window.open(URL,"newwindow","toolbar=no,scrollbars=no,status=no,location=no,resizable=no,menubar=no,width=650,height=500,left=400,top=100");
}

// function to open a pop-up window with specific width
function openPopUpWindowWithOptions(URL, width, height) {
	aWindow=window.open(URL,"newwindow","toolbar=no,scrollbars=no,status=no,location=no,resizable=no,menubar=no,width="+width+",height="+height+",left=400,top=100");
}
// function to obsfuscate email addresses from spammers
function contactUnobsfuscate() {
	
	// find all links in HTML
	var link = document.getElementsByTagName && document.getElementsByTagName("a");
	var contact, e;
	
	// examine all links
	for (e = 0; link && e < link.length; e++) {
	
		// does the link have use a class named "contact"
		if ((" "+link[e].className+" ").indexOf(" contact ") >= 0) {
		
			// get the obfuscated contact address
			contact = link[e].firstChild.nodeValue.toLowerCase() || "";
			
			// transform into real contact address
			contact = contact.replace(/dot/ig, ".");
			contact = contact.replace(/\(at\)/ig, "@");
			contact = contact.replace(/\s/g, "");
			
			// is contact valid?
			if (/^[^@]+@[a-z0-9]+([_\.\-]{0,1}[a-z0-9]+)*([\.]{1}[a-z0-9]+)+$/.test(contact)) {
			
				// change into a real mailto link
				link[e].href = "mailto:" + contact;
				link[e].firstChild.nodeValue = contact;
		
			}
		}
	}
}
window.onload = contactUnobsfuscate;

//close popup window and reload parent
function closeWindowAndReloadParent(){
	$(window).unload(function () { 
		//alert("Bye now!"); 
		window.opener.location.reload();			
	});
}

//if the default search term is present, clear it
function clearValue(fieldid) {
	if($("#" + fieldid).val() == $("#" + fieldid).attr('title')) {
		$("#" + fieldid).val('');
	} 
}
// if the default search term is empty, set it
function setValue(fieldid) {
	if ($("#" + fieldid).val() == "") {
		$("#" + fieldid).val($("#" + fieldid).attr('title'));
	} 
}

/*
 * Disable the fields specified and add the class disabledfield
 * 
 * @param fieldid - The ID of the field to be disabled
 */
/*
 * Disable the fields specified and add the class disabledfield
 * 
 * @param fieldid - The ID of the field to be disabled
 */
function disableField(fieldid) {
	// disable the respective field and add the class disabled field		
	$("#" + fieldid).attr("disabled", "disabled").addClass('disabledfield');
} 
/*
 * Enable the fields specified and remove the class disabledfield
 * 
 * @param fieldid - The ID of the field to be enabled
 */
function enableField(fieldid) {
	// // hide the respective field and remove the class disabled field
	$("#" + fieldid).attr("disabled", false).removeClass('disabledfield');	
} 

/*
 * Hide the container with the specified id and disable all input fields in it
 * 
 * @param fieldid - The ID of the container to be hidden
 */
function disableContainerByID(fieldid) {
	// hide the respective tbody and disable any HTML controls
	$("#" + fieldid).hide();
	$("#" + fieldid + " input, #" + fieldid + " select, #" + fieldid + " textarea").attr("disabled", true);
} 
/*
 * Show the container with the specified id and enable all input fields in it
 * 
 * @param fieldid - The ID of the container to be shown
 */
function enableContainerByID(fieldid) {
	// hide the respective tbody and disable any HTML controls
	$("#" + fieldid).show();
	$("#" + fieldid + " input, #" + fieldid + " select, #" + fieldid + " textarea").attr("disabled", false);
}
 /*
 * Hide the container with the specified class and disable all input fields in it
 * 
 * @param fieldid - The class of the container to be hidden
 */
function disableContainerByClass(classid) {
	// hide the respective tbody and disable any HTML controls
	$("." + classid).hide();
	$("." + classid + " input, ." + classid + " select, ." + classid + " textarea").attr("disabled", true);
} 
/*
 * Show the container with the specified class and enable all input fields in it
 * 
 * @param fieldid - The class of the container to be shown
 */
function enableContainerByClass(classid) {
	// hide the respective tbody and disable any HTML controls
	$("." + classid).show();
	$("." + classid + " input, ." + classid + " select, ." + classid + " textarea").attr("disabled", false);
}

/**
 * Resize the contents of the body to fit due to removal or addition of elements
 * which affect the height of the page
 */
function resizeContentForm() {
		equalHeight($("#contentcolumn, #leftcolumn, form"));
		$("#contentcolumn").css({'height': $("#contentcolumn").height() + 25});
} 
/**
 * Show the details of an item in the calendar schedule 
 */ 
function showCalendarDetails() {
	if($.browser.msie) {
		$("#month_selector").css("margin-top", "5px"); }
	$("table#cal_1 td.has-events").hover(function() {
		var swidth = $(this).outerWidth(); var sheight = $(this).outerHeight(); var position = $(this).position(); $(this).append("<div class=\"hover-box\" />"); var box = $(this).find(".hover-box"); if($.browser.msie) {
			box.css( {
				"position" : "absolute", "top" : position.top - 1, "left" : position.left - 1, "border" : "3px solid #a35252", "display" : "none"}
			); box.width(swidth - 3.5); box.height(sheight - 3); }
		else {
			box.css( {
				"position" : "absolute", "top" : position.top - 2, "left" : position.left - 2.5, "border" : "3px solid #a35252", "display" : "none"}
			); box.width(swidth - 2.5); box.height(sheight - 2.5); }
		box.fadeIn(); }
	, function() {
		$(this).find(".hover-box").stop(true, true).fadeOut(); $(this).find(".hover-box").remove(); }
	); $("table#cal_1 #calendar_month,table#cal_1 #calendar_year").change(function() {
		var jump_month = $("table#cal_1 #calendar_month").val(); var jump_year = $("table#cal_1 #calendar_year").val(); var calendar_displaytype = $("table#cal_1 #calendar_displaytype").val(); $("table#cal_1 #month_selector").submit(); }
	); $("table#cal_1.full td").click(function() {
		$("table#cal_1.full .open-details").remove(); }
	); $("table#cal_1.full td.has-events").click(function() {
		if($(this).find("table#cal_1 .open-details").length <= 0) {
			$(this).find(".hover-box").remove(); var swidth = $(this).outerWidth(); var sheight = $(this).outerHeight(); var contents = $(this).html(); var position = $(this).position(); $("table#cal_1.full .open-details").remove(); if($("#details_1").length <= 0)$(this).append("<div id=\"details_1\" class=\"open-details\" />"); var details = $("#details_1"); details.css( {
				"position" : "absolute", "top" : position.top, "left" : position.left, "z-index" : "9", "opacity" : "0"}
			); details.html("<a href=\"#\" class=\"close-details\">close</a>" + contents); details.width(swidth); details.height(sheight); $("#details_1, #details_1 .close-details").click(function() {
				$(this).closest(".has-events").find(".hover-box").remove(); $("#details_1").remove(); return false; }
			); $("#details_1 a").click(function() {
				window.location = $(this).attr("href"); return false; }
			); var wwidth = $(window).width(); var nright = position.left + swidth * 1.5; var nleft = position.left - swidth / 2; if(nright <= wwidth) {
				if(nleft <= 0) {
					moveleft = "+=0"; /* left */
					}
				else {
					moveleft = "-=" + swidth / 1.58; /* normal */
					}
				}
			else {
				moveleft = "-=" + swidth * 1.135; /* right */
				}
			details.find(".event-time").hide(); details.find("ul.events").hide(); details.animate( {
				opacity : "1", top : "-=" + sheight / 2, left : moveleft, height : "+=" + sheight, width : "+=" + swidth}
			, {
				duration : 500, easing : "easeOutQuint" }
			); details.find("ul.events").delay(400).show(0); details.find(".event-details").show(); }
		}
	); 
}
// function to do nothing
function doNothing(){	
}
/**
 * Resize the contents of the body to fit due to removal or addition of elements
 * which affect the height of the page
 */
function resizeContentForm() {		
		$("#contentcolumn").css({'height': $("form").height() + 200});
		equalHeight($("#contentcolumn, #leftcolumn"));
} 