<?php
/**
 * Template file for the email variable "{item_prices_categories}".
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
        $sku = (!empty( $item['sku'] )) ? '('. $item['sku'] .')' : '';
        $categories = implode( ', ', $item['categories'] );

        if ( !empty( $categories ) ) {
            $categories = 'in '. $categories;
        }

        printf(
            '<li><a href="%s">%s %s X %d &ndash; %s %s</a></li>',
            $item['link'],
            $item['name'],
            $sku,
            $item['qty'],
            woocommerce_price( $item['price'] ),
            $categories
        );
    } ?>
</ul>