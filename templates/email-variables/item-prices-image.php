<?php
/**
 * Template file for the email variable "{item_prices_image}".
 *
 * To edit this template, copy this file over to your wp-content/[current_theme]/follow-up-emails/email-variables
 * then edit the new file. A single variable named $lists is passed along to this template.
 *
 * $lists = array('items' => array(
 *      array(
 *          id:     Product ID
 *          sku:    Product's SKU
 *          link:   Absolute URL to the product
 *          name:   Product's name
 *          price:  Price of the product - unformatted
 *          qty:    Quantity bought
 *          categories: Array of product categories
 *      )
 * ))
 */
?>
<ul>
    <?php
    foreach ( $lists['items'] as $item ) {
        $_product = WC_FUE_Compatibility::wc_get_product( $item['id'] );

        $thumbnail = $_product->get_image( 'shop_thumbnail', array( 'title' => '' ) );

        $thumbnail_html = sprintf( '<a href="%s">%s</a>', esc_url( $item['link'] ), $thumbnail );

        printf(
            '<li>%s <a href="%s">%s X %d &ndash; %s</a></li>',
            $thumbnail_html,
            $item['link'],
            $item['name'],
            $item['qty'],
            woocommerce_price( $item['price'] )
        );
    } ?>
</ul>