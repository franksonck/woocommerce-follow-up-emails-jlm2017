<?php
$defaults = apply_filters( 'fue_manual_email_defaults', array(
    'type'              => $email->type,
    'always_send'       => $email->always_send,
    'name'              => $email->name,
    'interval'          => $email->interval_num,
    'interval_duration' => $email->interval_duration,
    'interval_type'     => $email->interval_type,
    'send_date'         => $email->send_date,
    'send_date_hour'    => $email->send_date_hour,
    'send_date_minute'  => $email->send_date_minute,
    'product_id'        => $email->product_id,
    'category_id'       => $email->category_id,
    'subject'           => $email->subject,
    'message'           => $email->message,
    'tracking_on'       => (!empty($email->tracking_code)) ? 1 : 0,
    'tracking'          => $email->tracking_code
), $email);

// if type is date, switch columns
if ( $defaults['interval_type'] == 'date' ) {
    $defaults['interval_type'] = $defaults['interval_duration'];
    $defaults['interval_duration'] = 'date';
}

if ( isset($_POST) && !empty($_POST) ) {
    $defaults = array_merge( $defaults, $_POST );
}
?>
<div class="wrap email-form">
    <div class="icon32"><img src="<?php echo FUE_TEMPLATES_URL .'/images/send_mail.png'; ?>" /></div>
    <h2><?php _e('Follow-Up Emails &raquo; Send Manual Email', 'follow_up_emails'); ?></h2>

    <form action="admin-post.php" method="post" id="frm">
        <h3><?php printf(__('Send Email: %s', 'follow_up_emails'), $email->name); ?></h3>

        <table class="form-table">
            <tbody>
                <tr valign="top" class="send_type_tr">
                    <th scope="row" class="send_type_th">
                        <label for="send_type"><?php _e('Send Email To', 'follow_up_emails'); ?></label>
                    </th>
                    <td class="send_type_td">
                        <select name="send_type" id="send_type" style="min-width: 25em;">
                            <option value="email"><?php _e('This email address', 'follow_up_emails'); ?></option>
                            <option value="subscribers"><?php _e('Subscribers and Lists', 'follow_up_emails'); ?></option>
                            <option value="roles"><?php _e('User Role', 'follow_up_emails'); ?></option>
                            <?php do_action( 'fue_manual_types', $email ); ?>
                        </select>

                        <div class="send-type-email send-type-div">
                            <input type="text" name="recipient_email" id="recipients" class="email-recipients" placeholder="someone@example.com" style="width: 600px;" />
                        </div>

                        <div class="send-type-subscribers send-type-div">
                            <select name="email_list" id="email_list" style="min-width: 25em;">
                                <option value=""><?php _e('All Subscribers', 'follow_up_emails'); ?></option>
                                <?php foreach ( fue_get_subscription_lists() as $list ): ?>
                                <option value="<?php echo esc_attr( $list['list_name'] ); ?>"><?php echo wp_kses_post( $list['list_name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="send-type-roles send-type-div">
                            <select name="roles[]" multiple id="roles" class="select2" data-placeholder="<?php _e('Select the Roles to send to', 'follow_up_emails'); ?>" style="width: 400px;">
                                <?php wp_dropdown_roles(); ?>
                            </select>
                        </div>

                        <?php do_action( 'fue_manual_type_actions', $email ); ?>
                    </td>
                </tr>

                <tr valign="top" class="sending_schedule">
                    <th scope="row">
                        <label for="schedule_email"><?php _e('Send at a specific time', 'follow_up_emails'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="schedule_email" id="schedule_email" value="1" />
                    </td>
                </tr>

                <tr valign="top" class="sending_schedule_picker">
                    <th>&nbsp;</th>
                    <td>
                        <input type="text" class="date" size="10" name="sending_schedule_date" value="" />

                        <input type="number" min="1" max="12" step="1" name="sending_schedule_hour" value="" placeholder="<?php _e('Hour', 'follow_up_emails'); ?>" style="width:80px;" />
                        <input type="number" min="0" max="59" step="1" name="sending_schedule_minute" value="" placeholder="<?php _e('Minute', 'follow_up_emails'); ?>" style="width:80px;" />
                        <select name="sending_schedule_ampm">
                            <option value="am">AM</option>
                            <option value="pm">PM</option>
                        </select>

                    </td>
                </tr>

                <tr valign="top" class="send_again_tr">
                    <th scope="row">
                        <label for="send_again"><?php _e('Send again', 'follow_up_emails'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="send_again" id="send_again" value="1" />
                    </td>
                </tr>

                <tr valign="top" class="class_send_again send_again_interval_tr">
                    <th scope="row" class="interval_th">
                        <label for="interval_type"><?php _e('Send again in:', 'follow_up_emails'); ?></label>
                    </th>
                    <td class="interval_td">
                        <span class="hide-if-date interval_span hideable">
                            <input type="text" name="interval" id="interval" value="<?php echo esc_attr($defaults['interval']); ?>" size="2" placeholder="0" />
                        </span>
                        <select name="interval_duration" id="interval_duration" class="interval_duration hideable">
                            <?php
                            /* @var FUE_Email $email */
                            $durations = Follow_Up_Emails::get_durations();

                            foreach ( $durations as $key => $value ):
                                if ( $key == 'date') continue;
                            ?>
                            <option class="interval_duration_<?php echo $key; ?> hideable" value="<?php echo esc_attr($key); ?>"><?php echo Follow_Up_Emails::get_duration( $key, $value ); ?></option>
                            <?php endforeach; ?>
                        </select>

                    </td>
                </tr>

                <?php do_action( 'fue_manual_email_form_before_message', $defaults ); ?>

                <tr valign="top">
                    <th scope="row">
                        <label for="subject"><?php _e('Email Subject', 'follow_up_emails'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="email_subject" id="email_subject" value="<?php echo esc_attr($defaults['subject']); ?>" class="regular-text" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">
                        <label for="message"><?php _e('Email Body', 'follow_up_emails'); ?></label>
                        <br />
                        <span class="description">
                            <?php _e('You may use the following variables in the Email Subject and Body', 'follow_up_emails'); ?>
                            <ul>
                                <?php do_action('fue_email_manual_variables_list', $email); ?>
                                <li class="var hideable var_web_version_url"><strong>{webversion_url}</strong> <img class="help_tip" title="<?php _e('The URL to the web version of the email.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_web_version_link"><strong>{webversion_link}</strong> <img class="help_tip" title="<?php _e('Renders a <em>View in browser</em> link that points to the web version of the email.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_customer_username"><strong>{customer_username}</strong> <img class="help_tip" title="<?php _e('The first name of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_customer_first_name"><strong>{customer_first_name}</strong> <img class="help_tip" title="<?php _e('The first name of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_customer_name"><strong>{customer_name}</strong> <img class="help_tip" title="<?php _e('The full name of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_customer_email"><strong>{customer_email}</strong> <img class="help_tip" title="<?php _e('The email address of the customer who purchased from your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_store_url"><strong>{store_url}</strong> <img class="help_tip" title="<?php _e('The URL/Address of your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_store_url_path"><strong>{store_url=path}</strong> <img class="help_tip" title="<?php _e('The URL/Address of your store with path added at the end. Ex. {store_url=/categories}', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_store_name"><strong>{store_name}</strong> <img class="help_tip" title="<?php _e('The name of your store.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_unsubscribe_url"><strong>{unsubscribe_url}</strong> <img class="help_tip" title="<?php _e('URL where users will be able to opt-out of the email list.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                                <li class="var hideable var_post_id"><strong>{post_id=xx}</strong> <img class="help_tip" title="<?php _e('Include the excerpt of the specified Post ID.', 'follow_up_emails'); ?>" src="<?php echo FUE_TEMPLATES_URL; ?>/images/help.png" width="16" height="16" /></li>
                            </ul>
                        </span>
                    </th>
                    <td>
                        <?php
                        $settings = array(
                            'textarea_rows' => 20,
                            'teeny'         => false
                        );
                        wp_editor($defaults['message'], 'email_message', $settings); ?>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="tracking_on"><?php _e('Add Google Analytics tracking to links', 'follow_up_emails'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="tracking_on" id="tracking_on" value="1" <?php if ($defaults['tracking_on'] == 1) echo 'checked'; ?> />
                    </td>
                </tr>
                <tr class="tracking_on">
                    <th scope="row">
                        <label for="tracking"><?php _e('Link Tracking', 'follow_up_emails'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="tracking" id="tracking" class="test-email-field" value="<?php echo esc_attr($defaults['tracking']); ?>" placeholder="e.g. utm_campaign=Follow-up-Emails-by-75nineteen" size="40" />
                        <p class="description">
                            <?php _e('The value inserted here will be appended to all URLs in the Email Body', 'follow_up_emails'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="test_email"><strong>Send a test email</strong></label>
                    </th>
                    <td>
                        <input type="hidden" id="email_type" value="manual" class="test-email-field" />
                        <input type="text" id="email" placeholder="Email Address" value="" class="test-email-field" />
                        <input type="button" id="test_send" value="<?php _e('Send Test', 'follow_up_emails'); ?>" class="button" />
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="hidden" name="woo_nonce" value="" />
            <input type="hidden" name="action" value="fue_followup_send_manual" />
            <input type="hidden" name="id" id="id" value="<?php echo $_GET['id']; ?>" />
            <input type="submit" name="save" id="save" value="<?php _e('Send Email Now', 'follow_up_emails'); ?>" class="button-primary" />
        </p>
    </form>
</div>

<script type="text/javascript">
var interval_types = <?php echo json_encode($email->get_email_type()->triggers); ?>;
jQuery(document).ready(function($) {
    $("#frm").submit(function() {
        $("#save").attr("disabled", true);
    });

    jQuery(".send-type-div").hide();

    jQuery("#send_type").change(function() {
        jQuery(".send-type-div").hide();
        switch (jQuery(this).val()) {

            case "email":
                jQuery(".send-type-email").show();
                break;

            case "subscribers":
                $(".send-type-subscribers").show();
                break;

            case "roles":
                $(".send-type-roles").show();
                break;

            default:
                break;

        }
    }).change();

    jQuery("#tracking_on").change(function() {
        if (jQuery(this).attr("checked")) {
            jQuery(".tracking_on").show();
        } else {
            jQuery(".tracking_on").hide();
        }
    }).change();

    jQuery("#interval_type").change(function() {
        if (jQuery(this).val() != "cart") {
            jQuery(".not-cart").show();
        } else {
            jQuery(".not-cart").hide();
        }
    }).change();

    jQuery("#interval_duration").change(function() {
        if (jQuery(this).val() == "date") {
            jQuery(".hide-if-date").hide();
            jQuery(".show-if-date").show();
        } else {
            jQuery(".hide-if-date").show();
            jQuery(".show-if-date").hide();
        }

        jQuery("#email_type").change();
    }).change();

    jQuery(".date").datepicker();

    jQuery("#timeframe_from").datepicker({
        onClose: function( selectedDate ) {
            $( "#timeframe_to" ).datepicker( "option", "minDate", selectedDate );
        }
    });
    jQuery("#timeframe_to").datepicker();

    <?php do_action('fue_manual_email_form_script'); ?>

    jQuery("#send_again").change(function() {
        if (jQuery(this).attr("checked")) {
            jQuery(".class_send_again").show();
        } else {
            jQuery(".class_send_again").hide();
        }
    }).change();

    jQuery("#schedule_email").change(function() {
        if (jQuery(this).attr("checked")) {
            jQuery(".sending_schedule_picker").show();
        } else {
            jQuery(".sending_schedule_picker").hide();
        }
    }).change();

    // Test Email
    jQuery("div.email-form").on("click", "#test_send", function() {
        var $btn    = jQuery(this);
        var old_val = $btn.val();

        if (jQuery("#wp-email_message-wrap").hasClass("tmce-active")){
            var message = tinyMCE.activeEditor.getContent();
        }else{
            var message = jQuery('#email_message').val();
        }

        $btn
            .val("Please wait...")
            .attr("disabled", true);

        var data = {
            'action'    : 'fue_send_test_email',
            'id'        : jQuery("#id").val(),
            'subject'   : jQuery("#email_subject").val(),
            'message'   : message
        };

        jQuery(".test-email-field").each(function() {
            var field = jQuery(this).data("key") || jQuery(this).attr("id");
            data[field] = jQuery(this).val();
        });

        jQuery.post(ajaxurl, data, function(resp) {
            if (resp == "OK")
                alert("Email sent!");
            else
                alert(resp);

            $btn
                .val(old_val)
                .removeAttr("disabled");
        });
    });

    jQuery(".tips, .help_tip").tipTip({
        'attribute' : 'title',
        'fadeIn' : 50,
        'fadeOut' : 50,
        'delay' : 200
    });

    <?php do_action( 'fue_manual_js' ); ?>

});
</script>
