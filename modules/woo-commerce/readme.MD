# Module: WooCommerce

## Description

This module was converted from the legacy plugin [WooCommerce & qTranslate-X](https://github.com/qTranslate-Team/woocommerce-qtranslate-x).

## Changelog ##

### 1.4 ###
* Improvement: Mini-Cart cached in browser now changes the display language, when user changes the active language. Other browser's tabs will now also show mini-cart in new language regardless to what language they were originally displayed. Woocommerce shares single mini-cart object between all the tabs of browser in the same session. That is why mini-cart will be shown the same on all tabs in the last language chosen by user.
* Improvement: Filters are disabled if DOING_CRON is defined. This enables Woocommerce API to send information in raw ML format. 
* Improvement: PayPal site to depend on active language locale if no billing country field is available.
* Improvement: ML fields on e-mail admin pages are now configured with "jquery" single entry.
* Improvement: Field "Purchase Note" on "Advanced" tab of Product editting page is now multilingual.
* Improvement: Translation of column "Category" in product list on page '/wp-admin/edit.php?post_type=product'.
* Fix: Variable product variations at front-end [Issue #277](https://github.com/qTranslate-Team/qtranslate-x/issues/277).

### 1.3 ###
* Improvement: A copule of new multilingual fields.
* Fix: compatibility with the latest qTranslate-X 3.4.6.1 or later.

### 1.2 ###
* Improvement: Gateway names are translated on page `/wp-admin/admin.php?page=wc-settings&tab=checkout`.
* Improvement: page /wp-admin/admin.php?page=wc-settings&tab=email&section=wc_email_customer_refunded_order [Pull Request #17](https://github.com/qTranslate-Team/woocommerce-qtranslate-x/issues/17).

### 1.1 ###
* Improvement: during an order, the language currently used by the customer is stored along with the order meta data
* Improvement: complete order e-mails on admin side are now sent with the order's original language (only for orders made after this update). [[Issue #3]( https://github.com/qTranslate-Team/woocommerce-qtranslate-x/issues/3)]

### 1.0.1 ###
* Improvement: display of fields of class 'attribute_name' in `post.php` page.
* Improvement: added filter 'woocommerce_format_content', subject to approval from Woocommerce - was already approved, wait for next release after 2.3.5 - done by now.
* Fix: problem with custom attributes: [Issue #2](https://github.com/qTranslate-Team/woocommerce-qtranslate-x/issues/2).

### 1.0 ###
* Initial release


## Upgrade Notice ##

### 1.3 ###
This version recovers compatibility with the latest qTranslate-X 3.4.6.2.