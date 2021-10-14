jQuery(document).ready(function () {

 /*   var dtToday = new Date();
    var month = dtToday.getMonth() + 1;
    var day = dtToday.getDate();
    var year = dtToday.getFullYear();
    if (month < 10){
        month = '0' + month.toString();
    }
    if (day < 10){
        day = '0' + day.toString();
    }

    var maxDate = year + '-' + month + '-' + day;
    jQuery('.picnic_date').attr('min', maxDate);

    if ( jQuery('.picnic_date').prop('type') != 'date' ) {
        jQuery('#date1').datepicker();
    }*/

    jQuery('.picnic_date').datepicker({
        autoclose: true,
        format: "yyyy-mm-dd",
        startDate: new Date(),
        minDate: new Date(),
        beforeShowDay: function(date){
            dmy = date.getDate() + "/" + (date.getMonth() + 1) + "/" + date.getFullYear();
            if(disableDates.indexOf(dmy) != -1){
                return false;
            } else{
                return true;
            }
        }
    });

    jQuery(".picnic_add_to_cart_btn").click(function (e) {
        e.preventDefault();

        if(jQuery(".picnic_locations_option").size() > 0){
            var picnic_location = jQuery(".picnic_locations_option").val();
        }

        if(jQuery(".allowed_num_persons").size() > 0){
            var num_persons = jQuery(".allowed_num_persons").val();
        }
        
        if(jQuery(".picnic_date").size() > 0){
            var date = jQuery(".picnic_date").val();
        }
        
        if(jQuery(".timeslot_option").size() > 0){
             var timeslot = jQuery(".timeslot_option").val();
        }
        
        if (picnic_location == '' || num_persons == '' || timeslot == '' || date == '') {
            jQuery('.selection_response').addClass('alert');
            jQuery('.selection_response').addClass('alert-danger');

            jQuery('.selection_response').removeClass('alert-warning');
            jQuery('.selection_response').removeClass('alert-success');
            jQuery('.selection_response').text('Please fill up all required fields');
            return false;
        } else {
            jQuery('.addons_popup').modal('show');
        }
    });

    jQuery(".addons_popup .add-addons").click(function () {

        var add_ons = [];

        jQuery(".addons_list li").each(function () {

            var Qty_check = jQuery(this).find("input.qty").val();

            if (Qty_check > 0) {

                var addon_id = jQuery(this).find(".addons").val();
                var addon_qty = jQuery("#quantity_"+addon_id+"").val();
                
                add_ons.push({
                    'addon_id': addon_id,
                    'addon_qty': addon_qty
                });
            }

        });

        if (add_ons != '') {
            var addons_choice = add_ons;
        } else{
             var addons_choice = '';
        }
    
        var picnic_location = jQuery(".picnic_locations_option").val();
        var num_persons = jQuery(".allowed_num_persons").val();
        var date = jQuery(".picnic_date").val();
        var timeslot = jQuery(".timeslot_option").val();
        var quantity = jQuery("form.cart .quantity .qty").val();
        var product_id = jQuery(".product_id").val();
        var selected_addons = addons_choice;

        var formData = {
            'picnic_location': picnic_location,
            'num_persons': num_persons,
            'date': date,
            'timeslot': timeslot,
            'product_id': product_id,
            'quantity': quantity,
            'selected_addons': selected_addons

        }

        jQuery.ajax({
            url: AJAXURL,
            type: 'POST',
            data: { 'action': 'ajax_add_to_cart', 'formData': formData },
            success: function (resp) {

                if (resp.fragments) {
                    var $thisButton = jQuery(".shopping_cart_header .shopping_cart_dropdown");
                    jQuery(document.body).trigger('added_to_cart', [resp.fragments, resp.cart_hash, $thisButton]);
                    window.location.href = SITE_URL+"/cart";
                }

                jQuery(".addons_popup").modal("hide");

            }
        });


    });

    jQuery(".addons_popup .modal-close").click(function () {

        var picnic_location = jQuery(".picnic_locations_option").val();
        var num_persons = jQuery(".allowed_num_persons").val();
        var date = jQuery(".picnic_date").val();
        var timeslot = jQuery(".timeslot_option").val();
        var quantity = jQuery("form.cart .quantity .qty").val();
        var product_id = jQuery(".product_id").val();

        var formData = {
            'picnic_location': picnic_location,
            'num_persons': num_persons,
            'date': date,
            'timeslot': timeslot,
            'product_id': product_id,
            'quantity': quantity

        }

        jQuery.ajax({
            url: AJAXURL,
            type: 'POST',
            data: { 'action': 'ajax_add_to_cart', 'formData': formData },
            success: function (resp) {
                if (resp.fragments) {
                    var $thisButton = jQuery(".shopping_cart_header .shopping_cart_dropdown");
                    jQuery(document.body).trigger('added_to_cart', [resp.fragments, resp.cart_hash, $thisButton]);
                    window.location.href = SITE_URL+"/cart";
                }
            }
        });
    });

    jQuery(".picnic_date").on("change", function () {

        var selected_date = jQuery(this).val();
        var selected_location = jQuery(".picnic_locations_option").val();
        var product_id = jQuery(".product_id").val();

        if (selected_location != "") {
            jQuery.ajax({
                url: AJAXURL,
                type: 'POST',
                data: {
                    'action': 'retrive_timeslots',
                    'location_id': selected_location,
                    'product_id': product_id,
                    'picnic_date': selected_date
                },
                success: function (resp) {

                    if (resp != '') {
                        var data = jQuery.parseJSON(resp);
                        jQuery(".timeslot_option").html(data.html);
                    }
                }

            });
        }
    });

    jQuery(document).on("change", ".picnic_locations_option", function () {

        var selected_location = jQuery(this).val();
        var selected_timeslot = jQuery(".timeslot_option").val();
        var selected_date = jQuery(".picnic_date").val();
        var num_of_persons = jQuery(".allowed_num_persons").val();
        var product_id = jQuery(".product_id").val();

        if (selected_timeslot != "" && selected_location != '' && selected_date != '' && num_of_persons != '') {
            jQuery.ajax({
                url: AJAXURL,
                type: 'POST',
                data: {
                    'action': 'check_location_availability',
                    'location_id': selected_location,
                    'timeslot': selected_timeslot,
                    'product_id': product_id,
                    'picnic_date': selected_date,
                    'num_of_persons': num_of_persons
                },
                success: function (resp) {

                    if (resp != '') {
                        var data = jQuery.parseJSON(resp);
                        if (data.status == 'fail') {

                            jQuery(".picnic_add_to_cart_btn").prop('disabled', true);
                            jQuery('.selection_response').addClass('alert');
                            jQuery('.selection_response').addClass('alert-warning');
                            jQuery('.selection_response').removeClass('alert-danger');
                            jQuery('.selection_response').removeClass('alert-success');
                            jQuery('.selection_response').text(data.msg);

                        } else {

                            jQuery(".picnic_add_to_cart_btn").prop('disabled', false);
                             jQuery('.selection_response').addClass('alert');
                            jQuery('.selection_response').addClass('alert-success');
                            jQuery('.selection_response').removeClass('alert-warning');
                            jQuery('.selection_response').removeClass('alert-danger');
                            jQuery('.selection_response').text(data.msg);

                        }
                    }

                }

            });
        }
    });

    jQuery(document).on("change", ".timeslot_option", function () {

        var selected_location = jQuery(".picnic_locations_option").val();
        var selected_timeslot = jQuery(this).val();
        var selected_date = jQuery(".picnic_date").val();
        var num_of_persons = jQuery(".allowed_num_persons").val();
        var product_id = jQuery(".product_id").val();

        if (selected_timeslot != "" && selected_location != '' && selected_date != '' && num_of_persons != '') {
            jQuery.ajax({
                url: AJAXURL,
                type: 'POST',
                data: {
                    'action': 'check_location_availability',
                    'location_id': selected_location,
                    'timeslot': selected_timeslot,
                    'product_id': product_id,
                    'picnic_date': selected_date,
                    'num_of_persons': num_of_persons
                },
                success: function (resp) {

                    if (resp != '') {
                        var data = jQuery.parseJSON(resp);
                        if (data.status == 'fail') {

                            jQuery(".picnic_add_to_cart_btn").prop('disabled', true);
                             jQuery('.selection_response').addClass('alert');
                            jQuery('.selection_response').addClass('alert-warning');
                            jQuery('.selection_response').removeClass('alert-danger');
                            jQuery('.selection_response').removeClass('alert-success');
                            jQuery('.selection_response').text(data.msg);

                        } else {

                            jQuery(".picnic_add_to_cart_btn").prop('disabled', false);
                             jQuery('.selection_response').addClass('alert');
                            jQuery('.selection_response').addClass('alert-success');
                            jQuery('.selection_response').removeClass('alert-warning');
                            jQuery('.selection_response').removeClass('alert-danger');
                            jQuery('.selection_response').text(data.msg);

                        }
                    }

                }

            });
        }
    });

    jQuery('.allowed_num_persons').on('change', function(){

        var selected_location = jQuery(".picnic_locations_option").val();
        var selected_timeslot = jQuery(".timeslot_option").val();
        var selected_date = jQuery(".picnic_date").val();
        var num_of_persons = jQuery(this).val();
        var product_id = jQuery(".product_id").val();

        if (selected_timeslot != "" && selected_location != '' && selected_date != '' && num_of_persons != '') {
            jQuery.ajax({
                url: AJAXURL,
                type: 'POST',
                data: {
                    'action': 'check_location_availability',
                    'location_id': selected_location,
                    'timeslot': selected_timeslot,
                    'product_id': product_id,
                    'picnic_date': selected_date,
                    'num_of_persons': num_of_persons
                },
                success: function (resp) {

                    if (resp != '') {
                        var data = jQuery.parseJSON(resp);
                        if (data.status == 'fail') {

                            jQuery(".picnic_add_to_cart_btn").prop('disabled', true);
                             jQuery('.selection_response').addClass('alert');
                            jQuery('.selection_response').addClass('alert-warning');
                            jQuery('.selection_response').removeClass('alert-danger');
                            jQuery('.selection_response').removeClass('alert-success');
                            jQuery('.selection_response').text(data.msg);

                        } else {

                            jQuery(".picnic_add_to_cart_btn").prop('disabled', false);
                             jQuery('.selection_response').addClass('alert');
                            jQuery('.selection_response').addClass('alert-success');
                            jQuery('.selection_response').removeClass('alert-warning');
                            jQuery('.selection_response').removeClass('alert-danger');
                            jQuery('.selection_response').text(data.msg);

                        }
                    }

                }

            });
        }

    });


    jQuery(".addon_add_to_cart_btn").click(function (e) {
        e.preventDefault();
        var addon_id = jQuery(this).val();
        var addon_qty = jQuery(".quantity input.qty").val();
        var $this = jQuery(this);
        $this.text('Processing...');

         jQuery.ajax({
            url: AJAXURL,
            type: 'POST',
            data: {
                'action': 'add_addons_in_cart',
                'type': 'addons',
                'addon_id': addon_id,
                'addon_qty': addon_qty
            },
            success: function (resp) {
                
                $this.text('Add to basket');

                if (resp.fragments) {
                    var $thisButton = jQuery(".shopping_cart_header .shopping_cart_dropdown");
                    jQuery(document.body).trigger('added_to_cart', [resp.fragments, resp.cart_hash, $thisButton]);
                    window.location.href = SITE_URL+"/cart";
                } else {
                    var data = jQuery.parseJSON(resp);

                    jQuery('.selection_response').addClass('alert');
                    jQuery('.selection_response').addClass('alert-danger');

                    jQuery('.selection_response').removeClass('alert-warning');
                    jQuery('.selection_response').removeClass('alert-success');
                    jQuery('.selection_response').html(data.err_msg);
                }
            }
        });
    
    });

});