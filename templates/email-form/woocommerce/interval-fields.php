<span class="show-if-order_total_above hide-if-date order_total_above_span hideable">
    <?php echo get_woocommerce_currency_symbol(); ?>
    <input type="text" name="meta[order_total_above]" id="order_total_above" value="<?php if (isset($email->meta['order_total_above'])) echo $email->meta['order_total_above']; ?>" />
</span>

<span class="show-if-order_total_below hide-if-date order_total_below_span hideable">
    <?php echo get_woocommerce_currency_symbol(); ?>
    <input type="text" name="meta[order_total_below]" id="order_total_below" value="<?php if (isset($email->meta['order_total_below'])) echo $email->meta['order_total_below']; ?>" />
</span>

<span class="show-if-total_purchases hide-if-date total_purchases_span hideable">
    <span class="description"><?php _e('is', 'follow_up_emails'); ?></span>
    <select name="meta[total_purchases_mode]">
        <option value="equal to" <?php if (isset($email->meta['total_purchases_mode']) && $email->meta['total_purchases_mode'] == 'equal to') echo  'selected'; ?>><?php _e('equal to', 'follow_up_emails'); ?></option>
        <option value="greater than" <?php if (isset($email->meta['total_purchases_mode']) && $email->meta['total_purchases_mode'] == 'greater than') echo  'selected'; ?>><?php _e('greater than', 'follow_up_emails'); ?></option>
    </select>

    <?php echo get_woocommerce_currency_symbol(); ?>
    <input type="text" name="meta[total_purchases]" value="<?php if (isset($email->meta['total_purchases'])) echo $email->meta['total_purchases']; ?>" />
</span>

<span class="show-if-total_orders hide-if-date total_orders_span hideable">
    <span class="description"><?php _e('is', 'follow_up_emails'); ?></span>
    <select name="meta[total_orders_mode]">
        <option value="equal to" <?php if (isset($email->meta['total_orders_mode']) && $email->meta['total_orders_mode'] == 'equal to') echo  'selected'; ?>><?php _e('equal to', 'follow_up_emails'); ?></option>
        <option value="greater than" <?php if (isset($email->meta['total_orders_mode']) && $email->meta['total_purchases_mode'] == 'greater than') echo  'selected'; ?>><?php _e('greater than', 'follow_up_emails'); ?></option>
    </select>

    <input type="text" name="meta[total_orders]" value="<?php if (isset($email->meta['total_orders'])) echo $email->meta['total_orders']; ?>" />
</span>