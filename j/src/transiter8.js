// transaction iterator routines
// NOTE:  this is a specially formatted js file.  Comments and function bracketing are NOT arbitrary!
// especially, do not use the comment sequence //- except intentinally!

// sets up iter8 functions and call iter8tick once -- meant to be run in '$' 
//-!iter8setup
//-<jquery.js
//-<fglib.js
function () {
	fg.iter8={};
	
	fg.iter8.status='iterating';
	fg.iter8.transaction=0;		// re-set dynamically
	fg.iter8.localcancel=false;

	// iteration 'tick' function called until there is no more 	
	fg.iter8.tick=function() {
		var cd={};
		cd.u="QQproject";
		cd.q=window.location.pathname;
		cd.r="QQaushort";
		cd.s='iterator';
		cd.t=fg.iter8.transaction;
		cd.v="QQpage";
		var options={url:'QQsrcbaseajax.php',data:cd,dataType:'script'};
		options.success=function(body,status) {
			if (typeof(calcpos) == 'function')	 
				calcpos();
			if (fg.iter8.localcancel)			
				alert("operation cancelled");
			else if (fg.iter8.status == 'iterating')
				fg.iter8.tick();
		};
		options.error=function(xhr,status,exception) {
			if (typeof(calcpos) == 'function')	 
				calcpos();
			if (fg.iter8.status == 'cancelled')
				alert("operation cancelled");
			else {
				fg.iter8.tick()	
				// alert("network or software error occurred in iteration.  terminated");
				//fg.iter8.status='error';
			}	
		};
		$.ajax(options);
	};
	
	fg.iter8.setStatusDisplay=function(statusline) {
		$("#<!status!>").html(statusline);
	};

	fg.iter8.addLogLine=function(logline) {
		$("#<!log!>").html(logline+"<br />"+$("#<!log!>").html());
	};
		
}//-}

// gets the iterations started - must be called dynamically
//-!iter8start
//-:transaction 44 transaction number
//-:initialstatus initialstatus
//-<jquery.js
function () {
	fg.iter8.transaction=44;
	fg.iter8.processstatus='initialstatus'; 
	fg.iter8.tick();
}//-}

