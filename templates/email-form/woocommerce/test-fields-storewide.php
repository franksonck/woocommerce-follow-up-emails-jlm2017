<p class="form-field">
    <select id="test_type">
        <option value="product" selected><?php _e('as a Product', 'follow_up_emails'); ?></option>
        <option value="order"><?php _e('as an Order', 'follow_up_emails'); ?></option>
    </select>
</p>

<p class="form-field">
    <span id="test_email_order">
        <?php _e( 'with Order Number: ', 'follow_up_emails' ); ?>
        <input type="number" id="order_id" placeholder="e.g. 105" size="5" class="test-email-field" min="1" />
    </span>
</p>

<p class="form-field">
    <span id="test_email_product">
        <input type="hidden" class="ajax_select2_products_and_variations test-email-field" data-multiple="false" data-key="product_id" data-placeholder="Select a Product" style="width: 200px;"></select>
    </span>
</p>