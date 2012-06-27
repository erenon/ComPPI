$(document).ready(function(){
	// first input autofokus
	$("input[type='text']:eq(0)").focus();

	// highlighted list lines
	$(".hovered_list li:not(.not_hovered_line)").hover(
		function() { $(this).addClass('hovered_listline'); },
		function() { $(this).removeClass('hovered_listline'); }
	);
	$(".hovered_list tr:not(.not_hovered_line)").hover(
		function() { $(this).addClass('hovered_listline'); },
		function() { $(this).removeClass('hovered_listline'); }
	);
	
	// general toggler for any button
	/*$(".btn").toggle(function() {
		$(this).addClass('btn_green');
		field_name = $(this).attr('id');
		field_name = 1;
	}, function() {
		$(this).removeClass('btn_green');
		field_name = $(this).attr('id');
		field_name = 0;
	});*/
	
	// Protein Search, Download DB
	/*fields = {
		fDownloadSpecHs	: 1,
		fDownloadSpecDm	: 1,
		fDownloadSpecCe	: 1,
		fDownloadSpecSc	: 0
	}*/
	// Protein Advanced Search
	$("#AdvSearchFrame").hide();
	$("#AdvSearchBtn").click(function() {
		$("#AdvSearchFrame").slideToggle(500);
	});
	
	// Download datasets
	$("#fDownloadSpecHsBtn, #fDownloadSpecDmBtn, #fDownloadSpecCeBtn, #fDownloadSpecScBtn").click(function() {
		var field_name = "#" + $(this).attr("id").replace("Btn", "");
		if ( $(this).hasClass('btn_green') ) {
			$(this).removeClass('btn_green');
			$(field_name).attr('value', 0);
		} else {
			$(this).addClass('btn_green');
			$(field_name).attr('value', 1);
		}
		//window.alert( $(field_name).attr('value') );
	});
	
	// dataset buttons can be toggled: only one button can be active, and 0 means no active button
	$("#fDownloadIntByLoc, #fDownloadInts, #fDownloadLocs").click(function() {
		current_is_active = $(this).hasClass('btn_green');
		
		switch($(this).attr("id")) {
			case 'fDownloadIntByLoc': value=1; break; // locsbyint
			case 'fDownloadInts': value=2; break; // interactions
			case 'fDownloadLocs': value=3; break; // localizations
			default: value=0;
		}
		
		$("#fDownloadIntByLoc, #fDownloadInts, #fDownloadLocs").removeClass('btn_green');
		
		if ( current_is_active ) {
			$("#fDownloadDataset").attr('value', 0);
		} else {
			$(this).addClass('btn_green');
			$("#fDownloadDataset").attr('value', value);
		}
	});
	
	$("#fDownloadSubmit").click(function(){
		// no dataset or no species is selected -> error
		/*window.alert( $("#fDownloadSpecHs").attr('value') );
		if ( !$("#fDownloadSpecHs").attr('value') && !$("#fDownloadSpecDm").attr('value') && !$("#fDownloadSpecCe").attr('value') && !$("#fDownloadSpecSc").attr('value') ) {
			window.alert("Please select at least one genus!");
			return false;
		}
		if ( !$("#fDownloadDataset").attr('value') ) {
			window.alert("Please select a dataset by clicking on one button in the second row!");
			return false;
		}*/
		return true;
	});
	
	/*$("#fDownloadSubmit").click(function(){
		$.ajax({
			type: "POST",
			url: "/download/serve",
			data: fields,
			success: function(data) { alert(data); }
		});
		return false;
	});*/
	
	/*$("#fDownloadForm").submit(function(){
		var url=$("#myForm").attr("action");
   
      //start send the post request
       $.post(url,{
           formName:$("#name_id").val(),
           other:"attributes"
       },function(data){
           //the response is in the data variable
   
            if(data.responseCode==200 ){           
                $('#output').html(data.greeting);
                $('#output').css("color","red");
				window.alert('200');
            }
           else if(data.responseCode==400){//bad request
               $('#output').html(data.greeting);
               $('#output').css("color","red");
				window.alert('400');
           }
           else{
              //if we got to this point we know that the controller
              //did not return a json_encoded array. We can assume that           
              //an unexpected PHP error occured
              alert("An unexpeded error occured.");

              //if you want to print the error:
              $('#output').html(data);
           }
       });

      return false;
   });*/
});