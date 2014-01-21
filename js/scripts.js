//** Serialize to JSON 
(function(b){b.fn.serializeJSON=function(){var d,c;d={};c=this.serializeArray();b.each(c,function(g,e){var f,j,h;f=e.name;j=e.value;h=b.map(f.split("["),function(i){var k;k=i[i.length-1];return k==="]"?i.substring(0,i.length-1):i});if(h[0]===""){h.shift()}b.deepSet(d,h,j)});return d};var a=function(c){return c===Object(c)};b.deepSet=function(c,k,g){if(!k||k.length===0){throw new Error("ArgumentError: keys param expected to be an array with least one key")}var i,d,f,h,j,e;i=k[0];d=k[1];if(d!==undefined&&d!==null){f=k.slice(1);if(i===""){j=c.length-1;e=c[c.length-1];if(a(e)&&!e[d]){i=j}else{c.push({});i=j+1}}if(c[i]===undefined){h=(d===""||!isNaN(parseInt(d,10)))?[]:{};c[i]=h}b.deepSet(c[i],f,g)}else{if(i===""){c.push(g)}else{c[i]=g}}}}(jQuery));

jQuery(document).ready(function($) {
	
	$('#endicia-form').on('submit', function(e) {
		e.preventDefault(); 
		var data = {
			action: 'endicia_post_form', 
			formData: $(this).serializeJSON()
		}; 

		$('#endicia-form input[type="submit"]').hide();
		$('.endicia-loading').show(); 
		
		jQuery.post(ajax_object.ajax_url, data, function(response) {
			if(response.status == "error") { 
				$('#endicia-form input[type="submit"]').show(); 
				$('endicia-loading').show(); 
				alert(response.message); 
			} else { 
				window.location = '/Thanks'; 
			}
		}, "json");
	})

});