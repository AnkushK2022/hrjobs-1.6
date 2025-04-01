// jQuery(document).ready(function($) {
//     jQuery('.job-application-form').on('submit', function(e) {
//         e.preventDefault();

//         var form = jQuery(this)[0]; 
//         var formData = new FormData(form); 
//         var ajaxurl = form.action; 

//         jQuery.ajax({
//             url: ajaxurl,
//             type: 'POST',
//             data: formData,
//             processData: false, 
//             contentType: false, 
//             success: function(response) {
//                 alert('Form submitted successfully!');
//                 console.log(response);
//             },
//             error: function(error) {
//                 alert('Error submitting form');
//                 console.log(error);
//             }
//         });
//     });
// });
jQuery(document).ready(function($) {
    $('#forms').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);

        $.ajax({
            url: ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('There was an error submitting the form.');
            }
        });
    });
});
