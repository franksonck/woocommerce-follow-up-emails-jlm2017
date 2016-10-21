<div>
    <h3><?php echo $template->name; ?></h3>

    <?php if ( $template->image ): ?>
        <a class="thickbox" href="<?php echo $template->image; ?>?TB_iframe=true&width=600&height=600"><img src="<?php echo $template->thumbnail; ?>" /></a>
    <?php endif; ?>

    <?php if ( empty( $template->description) ): ?>
        <p><?php printf( __('Template file: <code>%s</code>', 'follow_up_emails'), fue_locate_email_template( $template->file ) ); ?></p>
    <?php else: ?>
        <p><?php echo $template->description; ?></p>
    <?php endif; ?>

    <?php

    if ( isset($template->installed) && $template->installed ):
        $template_file = $template->file;
    ?>
        <p class="installed"><span class="dashicons dashicons-yes"></span> <?php _e('Installed', 'follow_up_emails'); ?></p>
        <p class="hover">
            <a class="uninstall" href="<?php echo wp_nonce_url( 'admin.php?page=followup-emails-templates&action=uninstall_template&template='. rawurlencode( $template_file ), 'template_uninstall' ); ?>">
                <span class="dashicons dashicons-no"></span> <?php _e('Uninstall', 'follow_up_emails'); ?>
            </a>
            <a href="#" class="edit-html" data-template="<?php echo $template_file; ?>">
                <span class="dashicons dashicons-edit"></span> <?php _e('HTML', 'follow_up_emails'); ?>
            </a>
        </p>
    <?php else: ?>
        <a class="button" href="<?php echo wp_nonce_url( 'admin.php?page=followup-emails-templates&action=install_template&template='. rawurlencode($id), 'template_install' ); ?>"><?php _e('Download', 'follow_up_emails'); ?></a>
    <?php endif; ?>

    <?php if ( is_numeric( $template->downloads ) ): ?>
    <p class="downloads" title="<?php printf( __('%d downloads', 'follow_up_emails'), $template->downloads ); ?>">
        <span class="dashicons dashicons-download"></span> <?php echo number_format( $template->downloads, 0 ); ?>
    </p>
    <?php endif; ?>
</div>