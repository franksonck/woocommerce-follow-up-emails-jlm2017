jQuery(document).ready(function($){
    $(":input.datepicker").datepicker();

    $(".set_interval_reminder").click(function(e) {
        e.preventDefault();

        $( '#fue_customer_reminders' ).block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        var data = {
            action:     'fue_add_customer_reminder',
            note:       $("#reminder_note").val(),
            interval:   $("#reminder_interval_days").val(),
            assign:     $("#assign_reminder").is(":checked"),
            assignee:   $("#assignee").val(),
            customer:   $("#customer_id").val()
        };

        $.post(ajaxurl, data, function(response) {
            $( 'ul.customer-reminders' ).prepend( response );
            $( '#fue_customer_reminders' ).unblock();
            $( '#reminder_note' ).val( '' );
        });
    });

    $(".set_date_reminder").click(function(e) {
        e.preventDefault();

        $( '#fue_customer_reminders' ).block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        var data = {
            action:     'fue_add_customer_reminder',
            note:       $("#reminder_note").val(),
            date:       $("#reminder_date").val(),
            hour:       $("#reminder_hour").val(),
            minute:     $("#reminder_minute").val(),
            ampm:       $("#reminder_ampm").val(),
            customer:   $("#customer_id").val(),
            assign:     $("#assign_reminder").is(":checked"),
            assignee:   $("#assignee").val()
        };

        $.post(ajaxurl, data, function(response) {
            $( 'ul.customer-reminders' ).prepend( response );
            $( '#fue_customer_reminders' ).unblock();
            $( '#reminder_note' ).val( '' );
            $("#assign_reminder")
                .attr("checked", false)
                .change();
            $("#assignee").val("");
        });
    });

    $("#fue_customer_reminders").on("click", "a.delete_reminder", function(e) {
        e.preventDefault();

        var reminder = $( this ).closest( 'li.reminder' );

        $( reminder ).block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        var data = {
            action:       'fue_delete_customer_reminder',
            reminder_id:  $( reminder ).attr( 'rel' )
        };

        $.post( ajaxurl, data, function() {
            $( reminder ).remove();
        });

        return false;
    });

    $(".queue-toggle").live("click", function(e) {
        e.preventDefault();

        var that    = this;
        var parent  = $(this).parents("table");
        var status  = $(this).data("status")
        var id      = $(this).data("id");
        var data    = {
            action: 'fue_toggle_queue_status',
            status: status,
            id: id
        };

        $(parent).block({ message: null, overlayCSS: { background: '#fff url('+ FUE.ajax_loader +') no-repeat center', opacity: 0.6 } });

        $.post(ajaxurl, data, function(resp) {
            resp = $.parseJSON(resp);
            if (resp.ack != "OK") {
                alert(resp.error);
            } else {
                var td = $(that).parents("td.status").eq(0);
                $(td).html(resp.new_status + '<br/><small><a href="#" class="queue-toggle" data-id="'+ id +'">'+ resp.new_action +'</a></small>');
            }
            $(parent).unblock();
        });
    });

    function load_customer_notes() {
        $( '#fue_customer_notes' ).block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        var data = {
            action: "fue_load_customer_notes",
            customer: $("#customer_id").val()
        };

        $.get(ajaxurl, data, function(response) {
            $( 'ul.customer-notes' ).html( response );
            $( '#fue_customer_notes' ).unblock();
        });
    }

    $("a.add_note").click(function(e) {
        e.preventDefault();

        if ( $("#add_customer_note").val().length == 0 ) {
            return;
        }

        $( '#fue_customer_notes' ).block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        var data = {
            action: "fue_add_customer_note",
            note:   $("#add_customer_note").val(),
            customer: $("#customer_id").val()
        };

        $.post(ajaxurl, data, function(response) {
            $( 'ul.customer-notes' ).prepend( response );
            $( '#fue_customer_notes' ).unblock();
            $( '#add_customer_note' ).val( '' );
        });
    });

    $("#fue_customer_notes").on("click", "a.delete_note", function(e) {
        e.preventDefault();

        var note = $( this ).closest( 'li.note' );

        $( note ).block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        var data = {
            action:   'fue_delete_customer_note',
            note_id:  $( note ).attr( 'rel' )
        };

        $.post( ajaxurl, data, function() {
            $( note ).remove();
        });

        return false;
    });

    $(":input.user-search-select").filter(":not(.enhanced)").each( function() {
        var select2_args = {
            allowClear:  jQuery( this ).data( 'allow_clear' ) ? true : false,
            placeholder: jQuery( this ).data( 'placeholder' ),
            dropdownAutoWidth: 'true',
            minimumInputLength: jQuery( this ).data( 'minimum_input_length' ) ? jQuery( this ).data( 'minimum_input_length' ) : '3',
            escapeMarkup: function( m ) {
                return m;
            },
            ajax: {
                url:         ajaxurl,
                dataType:    'json',
                quietMillis: 250,
                data: function( term, page ) {
                    return {
                        term:     term,
                        action:   'fue_admin_search'
                    };
                },
                results: function( data, page ) {
                    var terms = [];
                    if ( data ) {
                        jQuery.each( data, function( id, text ) {
                            terms.push( { id: id, text: text } );
                        });
                    }
                    return { results: terms };
                },
                cache: true
            }
        };

        select2_args.multiple = false;
        select2_args.initSelection = function( element, callback ) {
            var data = {id: element.val(), text: element.attr( 'data-selected' )};
            return callback( data );
        };


        jQuery(this).select2(select2_args).addClass( 'enhanced' );
    } );

    $("#assign_reminder").change(function() {
        if ( $(this).is(":checked") ) {
            $("#assignee_block").show();
        } else {
            $("#assignee_block").hide();
            $("#assignee")
                .val("")
                .change();
        }
    }).change();

    $("#send_schedule").change(function() {
        if ( $(this).val() == "now" ) {
            $("p.send-later").hide();
        } else {
            $("p.send-later").show();
        }
    }).change();

    $("#send_again").change(function() {
        if ( $(this).is(":checked") ) {
            $(".send-again").show();
        } else {
            $(".send-again").hide();
        }
    }).change();

    $(".schedule-email").click(function(e) {
        e.preventDefault();

        $( '#fue_customer_followups' ).block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        var email               = $("#email").val();
        var sending_schedule    = $("#send_schedule").val();
        var send_date           = $("#send_date").val();
        var send_hour           = $("#send_time_hour").val();
        var send_minute         = $("#send_time_minute").val();
        var send_ampm           = $("#send_time_ampm").val();
        var send_again          = $("#send_again").is(":checked") ? 1 : 0;
        var send_again_value    = $("#send_again_value").val();
        var send_again_interval = $("#send_again_interval").val();
        var error               = $("#schedule_email_error");

        error.html("");
        $("#schedule_email_success").hide();

        if ( !email ) {
            error.html("Please select the email to send");
            return false;
        }

        if ( sending_schedule == "later" && !send_date ) {
            error.html("Please schedule your email");
            return false;
        }

        if ( send_again && !send_again_value ) {
            error.html("Please set the resending schedule");
            return false;
        }

        var data = {
            action: "fue_schedule_manual_email",
            user: $("#user_id").val(),
            customer: $("#customer_id").val(),
            email: email,
            sending_schedule: sending_schedule,
            send_date: send_date,
            send_hour: send_hour,
            send_minute: send_minute,
            send_ampm: send_ampm,
            send_again: send_again,
            send_again_value: send_again_value,
            send_again_interval: send_again_interval
        };

        $.post(ajaxurl, data, function(resp) {

            if ( resp.status == "error" ) {
                error.html( resp.message );
            } else {
                reset_followup_form();
                load_customer_notes();

                $("#schedule_email_success")
                    .html(resp.message)
                    .show();
            }

            $("#fue_customer_followups").unblock();
        });
    });

    function reset_followup_form() {
        $("#email").val("");

        $("#send_schedule")
            .val("now")
            .change();
        $("#send_date").val("");
        $("#send_time_hour").val("1");
        $("#send_time_minute").val("0");
        $("#send_time_ampm").val("am");

        $("#send_again")
            .attr("checked", false)
            .change();
        $("#send_again_value").val("");
        $("#send_again_interval").val("minutes");
    }
});