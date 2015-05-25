<?php
/**
 * @package		Arastta eCommerce
 * @copyright	Copyright (C) 2015 Arastta Association. All rights reserved. (arastta.org)
 * @license		GNU General Public License version 3; see LICENSE.txt
 */

class Emailtemplate {

	public function __construct($registry) {
		$this->config = $registry->get('config');
		$this->url = $registry->get('url');
		$this->language = $registry->get('language');
		$this->db = $registry->get('db');
		$this->currency = new Currency($registry);
	}

	// Mail Subject
	public function getSubject($type, $template_id, $data) {
		$template = $this->getEmailTemplate($template_id);

		$findFunctionName = 'get' . ucwords($type) . 'Find';
		$replaceFunctionName = 'get' . ucwords($type) . 'Replace';
		
		$find = $this->$findFunctionName();
		$replace = $this->$replaceFunctionName($data);

        if(!empty($template['name'])){
            $subject = trim(str_replace($find, $replace, $template['name']));
        } else {
            $subject = $this->getDefaultSubject($type, $template_id, $data);
        }

		return $subject;
	}

    // Mail Message
	public function getMessage($type, $template_id, $data) {
		$template = $this->getEmailTemplate($template_id);
		
		$findFunctionName = 'get' . ucwords($type) . 'Find';
		$replaceFunctionName = 'get' . ucwords($type) . 'Replace';
		
		$find = $this->$findFunctionName();
		$replace = $this->$replaceFunctionName($data);

        if(!empty($template['description'])){
            if(ucwords($type) == 'OrderAll') {

                preg_match('/{product:start}(.*){product:stop}/Uis', $template['description'], $template_product);
                if(!empty($template_product[1])){
                    $template['description'] = str_replace($template_product[1], '', $template['description']);
                }

                preg_match('/{voucher:start}(.*){voucher:stop}/Uis', $template['description'], $template_voucher);
                if(!empty($template_voucher[1])){
                    $template['description'] = str_replace($template_voucher[1], '', $template['description']);
                }

                preg_match('/{comment:start}(.*){comment:stop}/Uis', $template['description'], $template_comment);
                if(!empty($template_comment[1])){
                    $template['description'] = str_replace($template_comment[1], '', $template['description']);
                }

                preg_match('/{tax:start}(.*){tax:stop}/Uis', $template['description'], $template_tax);
                if(!empty($template_tax[1])){
                    $template['description'] = str_replace($template_tax[1], '', $template['description']);
                }

                preg_match('/{total:start}(.*){total:stop}/Uis', $template['description'], $template_total);
                if(!empty($template_total[1])){
                    $template['description'] = str_replace($template_total[1], '', $template['description']);
                }
            }

            $message = trim(str_replace($find, $replace, $template['description']));
        } else {
            $message = $this->getDefaultMessage($type, $template_id, $data);
        }

        return $message;
	}

    //Mail Text
    public function getText($type, $template_id, $data) {
        $findName = 'get' . ucwords($type) . 'Text';

        return $this->$findName($template_id, $data);
    }

    // Mail Template
	public function getEmailTemplate($email_template) {
        $item = explode("_", $email_template);

        $query  = $this->db->query("SELECT * FROM " . DB_PREFIX . "email AS e LEFT JOIN " . DB_PREFIX . "email_description AS ed ON ed.email_id = e.id WHERE e.type = '{$item[0]}' AND e.text_id = '{$item[1]}' AND ed.language_id = '{$this->config->get('config_language_id')}'");

        if(!$query->num_rows) {
            $query  = $this->db->query("SELECT * FROM " . DB_PREFIX . "email AS e LEFT JOIN " . DB_PREFIX . "email_description AS ed ON ed.email_id = e.id WHERE e.type = '{$item[0]}' AND e.text_id = '{$item[1]}'");
        }

        foreach ($query->rows as $result) {
            $email_template_data = array(
                'text'         => $result['text'],
                'text_id'      => $result['text_id'],
                'type'         => $result['type'],
                'context'      => $result['context'],
                'name'         => $result['name'],
                'description'  => $result['description'],
                'status'       => $result['status']
            );
        }

        return $email_template_data;
    }

	// Admin Login 
	public function getLoginFind() {
        $result = array( '{username}', '{store_name}', '{ip_address}' );
        return $result;
    }

    public function getLoginReplace($data) {
        $result = array(
            'username'   => $data['username'],
            'store_name' => $data['store_name'],
            'ip_address' => $data['ip_address']
        );

        return $result;
    }
	
	// Affilate
    public function getAffiliateFind() {
        $result = array( '{firstname}', '{lastname}', '{date}', '{store_name}', '{description}', '{order_id}', '{amount}', '{total}', '{email}','{password}', '{affiliate_code}', '{account_href}' );
        return $result;
    }
	
    public function getAffiliateReplace($data) {
        $result = array(
            'firstname'      => (!empty($data['firstname'])) ? $data['firstname'] : '',
            'lastname'       => (!empty($data['lastname'])) ? $data['lastname'] : '',
            'date'           => date($this->language->get('date_format_short'), strtotime(date("Y-m-d H:i:s"))),
            'store_name'     => $this->config->get('config_name'),
            'description'    => (!empty($data['description'])) ? nl2br($data['description']) : '',
            'order_id'       => (!empty($data['order_id'])) ? $data['order_id'] : '',
            'amount'         => (!empty($data['amount'])) ? $data['amount'] : '',
            'total'          => (!empty($data['total'])) ? $data['total'] : '',
            'email'          => (!empty($data['email'])) ? $data['email'] : '',
            'password'       => (!empty($data['password'])) ? $data['password'] : '',
            'affiliate_code' => (!empty($data['code'])) ? $data['code'] : '',
            'account_href'   => $this->url->link('affiliate/login', '', 'SSL')
        );

        return $result;
    }	
	
	// Customer
	public function getCustomerFind() {
        $result = array( '{firstname}', '{lastname}', '{date}', '{store_name}', '{email}', '{password}', '{account_href}', '{activate_href}' );
        return $result;
    }

    public function getCustomerReplace($data) {
        $result = array(
            'firstname'      => $data['firstname'],
            'lastname'       => $data['lastname'],
            'date'           => date($this->language->get('date_format_short'), strtotime(date("Y-m-d H:i:s"))),
            'store_name'     => $this->config->get('config_name'),
            'email'          => $data['email'],
            'password'       => $data['password'],
            'account_href'   => $this->url->link('account/login', '', 'SSL'),
            'activate_href'  => (!empty($data['confirm_code'])) ? $this->url->link('account/activate', 'passkey=' . $data['confirm_code'], 'SSL') : ''
        );

        return $result;
    }
	
	// Contact ( Information )
	public function getContactFind() {
        $result = array( '{name}', '{email}', '{store_name}', '{enquiry}' );
        return $result;
    }

    public function getContactReplace($data) {
        $result = array(
            'name'       => (!empty($data['name'])) ? $data['name'] : '',
            'email'      => (!empty($data['email'])) ? $data['email'] : '',
            'store_name' => $this->config->get('config_name'),
            'enquiry'    => (!empty($data['enquiry'])) ? $data['enquiry'] : ''
        );

        return $result;
    }

    // Order
    public function getOrderAllFind() {

        $result = array (
            '{firstname}', '{lastname}', '{delivery_address}', '{shipping_address}', '{payment_address}', '{order_date}', '{product:start}', '{product:stop}',
            '{total:start}', '{total:stop}', '{voucher:start}', '{voucher:stop}', '{special}', '{date}', '{payment}', '{shipment}', '{order_id}', '{total}', '{invoice_number}',
            '{order_href}', '{store_url}', '{status_name}', '{store_name}', '{ip}', '{comment:start}', '{comment:stop}', '{comment}', '{sub_total}', '{shipping_cost}',
            '{client_comment}', '{tax:start}', '{tax:stop}', '{tax_amount}', '{email}', '{telephone}'
        );

        return $result;
    }

    public function getOrderAllReplace($data) {
        $emailTemplate = $this->getEmailTemplate($data['template_id']);

        foreach($data as $dataKey => $dataValue) {
            $$dataKey = $dataValue;
        }

        // Special
       /* $special = array();

        if (sizeof($email_template['special']) <> 0) {
         //   $special = $this->_prepareProductSpecial((int)$order_info['customer_group_id'], $email_template['special']);
        }
        */

        // Products
        preg_match('/{product:start}(.*){product:stop}/Uis', $emailTemplate['description'], $template_product);

        if (sizeof($template_product) > 0) {
            $getProducts = $this->getOrderProducts($order_info['order_id']);

            $products = $this->getProductsTemplate($order_info, $getProducts, $template_product);
            $emailTemplate['description'] = str_replace($template_product[1], '', $emailTemplate['description']);
        } else {
            $products = array();
        }

        // Vouchers
        preg_match('/{voucher:start}(.*){voucher:stop}/Uis', $emailTemplate['description'], $template_voucher);

        if (sizeof($template_voucher) > 0) {
            $getVouchers = $this->getOrderVouchers($order_info['order_id']);

            $vouchers = $this->getVoucherTemplate($order_info, $getVouchers, $template_voucher);
            $emailTemplate['description'] = str_replace($template_voucher[1], '', $emailTemplate['description']);
        } else {
            $vouchers = array();
        }

        // Comment
        preg_match('/{comment:start}(.*){comment:stop}/Uis', $emailTemplate['description'], $template_comment);

        if (sizeof($template_comment) > 0) {
            if(empty($comment)){
                $comment[0] = '';
            } else {
                $comment = $this->getCommentTemplate($comment, $template_comment);
            }
            $emailTemplate['description'] = str_replace($template_comment[1], '', $emailTemplate['description']);
        } else {
            $comment[0] = '';
        }

        // Tax
        preg_match('/{tax:start}(.*){tax:stop}/Uis', $emailTemplate['description'], $template_tax);

        if (sizeof($template_tax) > 0) {
            $taxes = $this->getTaxTemplate($totals, $template_tax);
            $emailTemplate['description'] = str_replace($template_tax[1], '', $emailTemplate['description']);
        } else {
            $taxes = array();
        }

        // Total
        preg_match('/{total:start}(.*){total:stop}/Uis', $emailTemplate['description'], $template_total);

        $order_total = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_info['order_id'] . "'");
        $getTotal = $order_total->rows;

        if (sizeof($template_total) > 0) {
            $tempTotals = $this->getTotalTemplate($getTotal, $template_total, $order_info);
            $emailTemplate['description'] = str_replace($template_total[1], '', $emailTemplate['description']);
        } else {
            $tempTotals = array();
        }

        $result = array(
            'firstname'       => $order_info['firstname'],
            'lastname'        => $order_info['lastname'],
            'delivery_address'=> $address,
            'shipping_address'=> $address,
            'payment_address' => $payment_address,
            'order_date'      => date($this->language->get('date_format_short'), strtotime($order_info['date_added'])),
            'product:start'   => implode("", $products),
            'product:stop'    => '',
            'total:start'     => implode("", $tempTotals),
            'total:stop'      => '',
            'voucher:start'   => implode("", $vouchers),
            'voucher:stop'    => '',
            'special'         => (sizeof($special) <> 0) ? implode("<br />", $special) : '',
            'date'            => date($this->language->get('date_format_short'), strtotime(date("Y-m-d H:i:s"))),
            'payment'         => $order_info['payment_method'],
            'shipment'        => $order_info['shipping_method'],
            'order_id'        => $order_info['order_id'],
            'total'           => $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']),
            'invoice_number'  => $order_info['invoice_prefix'] . $invoice_no,
            'order_href'      => $order_href,
            'store_url'       => $order_info['store_url'],
            'status_name'     => $order_status,
            'store_name'      => $order_info['store_name'],
            'ip'              => $order_info['ip'],
            'comment:start'   => implode("", $comment),
            'comment:stop'    => '',
            'sub_total'       => $totals['sub_total'][0]['text'],
            'shipping_cost'   => (isset($totals['shipping'][0]['text'])) ? $totals['shipping'][0]['text'] : '',
            'client_comment'  => $order_info['comment'],
            'tax:start'       => implode("", $taxes),
            'tax:stop'        => '',
            'tax_amount'      => $this->currency->format($tax_amount, $order_info['currency_code'], $order_info['currency_value']),
            'email'           => $order_info['email'],
            'telephone'       => $order_info['telephone']
        );

        return $result;
    }

	// Review
	public function getReviewFind() {
        $result = array( '{author}', '{review}', '{date}', '{rating}', '{product}' );
        return $result;
    }

    public function getReviewReplace($data) {
        $result = array(
            'author'   => $data['name'],
            'review'   => $data['text'],
            'date'     => date($this->language->get('date_format_short'), time()),
            'rating'   => $data['rating'],
            'product'  => $data['product']
        );

        return $result;
    }
	
	// Voucher
    public function getVoucherFind() {
        $result = array( '{recip_name}', '{recip_email}', '{date}', '{store_name}', '{name}', '{amount}', '{message}', '{store_href}', '{image}', '{code}' );
        return $result;
    }

    public function getVoucherReplace($data) {
        $result = array( 
			'recip_name'  => $data['recip_name'],
			'recip_email' => $data['recip_email'], 
			'date'        => date($this->language->get('date_format_short'), strtotime(date("Y-m-d H:i:s"))),
            'store_name'  => $data['store_name'],
			'name'        => $data['name'],
			'amount'      => $data['amount'],
			'message'     => $data['message'],
			'store_href'  => $data['store_href'],
            'image'       => (file_exists(DIR_IMAGE . $data['image'])) ? 'cid:' . md5(basename($data['image'])) : '', 'code' => $data['code']
        );
        return $result;
    }

    // Order Text
    public function getOrderText($template_id, $data){

        foreach($data as $dataKey => $dataValue) {
            $$dataKey = $dataValue;
        }

         // Load the language for any mails that might be required to be sent out
        $language = new Language($order_info['language_directory']);
        $language->load('english');
        $language->load('mail/order');

        $text  = sprintf($language->get('text_new_greeting'), html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8')) . "\n\n";
        $text .= $language->get('text_new_order_id') . ' ' . $order_id . "\n";
        $text .= $language->get('text_new_date_added') . ' ' . date($language->get('date_format_short'), strtotime($order_info['date_added'])) . "\n";
        $text .= $language->get('text_new_order_status') . ' ' . $order_status . "\n\n";

        if ($comment && $notify) {
            $text .= $language->get('text_new_instruction') . "\n\n";
            $text .= $comment . "\n\n";
        }

        // Products
        $text .= $language->get('text_new_products') . "\n";

        foreach ($getProdcuts as $product) {
            $text .= $product['quantity'] . 'x ' . $product['name'] . ' (' . $product['model'] . ') ' . html_entity_decode($this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8') . "\n";

            $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . $product['order_product_id'] . "'");

            foreach ($order_option_query->rows as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->getUploadByCode($option['value']);

                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }

                $text .= chr(9) . '-' . $option['name'] . ' ' . (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value) . "\n";
            }
        }

        foreach ($getVouchers as $voucher) {
            $text .= '1x ' . $voucher['description'] . ' ' . $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']);
        }

        $text .= "\n";

        $text .= $language->get('text_new_order_total') . "\n";

        foreach ($getTotal as $total) {
            $text .= $total['title'] . ': ' . html_entity_decode($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8') . "\n";
        }

        $text .= "\n";

        if ($order_info['customer_id']) {
            $text .= $language->get('text_new_link') . "\n";
            $text .= $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_id . "\n\n";
        }

        /*
         if ($download_status) {
             $text .= $language->get('text_new_download') . "\n";
             $text .= $order_info['store_url'] . 'index.php?route=account/download' . "\n\n";
         }
        */
                // Comment
        if ($order_info['comment']) {
            $text .= $language->get('text_new_comment') . "\n\n";
            $text .= $order_info['comment'] . "\n\n";
        }

        $text .= $language->get('text_new_footer') . "\n\n";

        return $text;
    }

    // Language
    public function getLanguage(){
        $sql = "SELECT * FROM " . DB_PREFIX . "language WHERE language_id = '" . $this->config->get('config_language_id') . "'";
        $query = $this->db->query($sql);

        return $query->row;
    }

    // Order Special

    // Order Product
    public function getOrderProducts($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }

    public function getProductsTemplate($order_info, $getProducts, $template_product) {
        $result = array();

        foreach ($getProducts as $product) {
            $option = array();
            $attribute = array();

             // Product Option Order
            if (stripos($template_product[1], '{product_option}') !== false) {
                $product_option = $this->getOrderOptions($order_info['order_id'], $product['product_id']);

                foreach ($product_option as $option) {
                    if ($option['type'] != 'file') {
                        $option[] = '<i>' . $option['name'] . '</i>: ' . $option['value'];
                    } else {
                        $filename = utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.'));
                        $option[] = '<i>' . $option['name'] . '</i>: ' . (utf8_strlen($filename) > 20 ? utf8_substr($filename, 0, 20) . '..' : $filename);
                    }
                }
            }

            // Product Attribute Order
            if (stripos($template_product[1], '{product_attribute}') !== false) {
                $product_attributes = $this->getProductAttributes($product['product_id'], $order_info['language_id']);

                foreach ($product_attributes as $attribute_group) {
                    $attribute_sub_data = '';

                    foreach ($attribute_group['attribute'] as $attribute) {
                        $attribute_sub_data .= '<br />' . $attribute['name'] . ': ' . $attribute['text'];
                    }

                    $attribute[] = '<u>' . $attribute_group['name'] . '</u>' . $attribute_sub_data;
                }
            }

            $getProduct = $this->getProduct($product['product_id']);

            // Product Image Order
            if ($getProduct['image']) {
                if ($this->config->get('template_email_product_thumbnail_width') && $this->config->get('template_email_product_thumbnail_height')) {
                    $image = $this->imageResize($getProduct['image'], $this->config->get('template_email_product_thumbnail_width'), $this->config->get('template_email_product_thumbnail_height'));
                } else {
                    $image = $this->imageResize($getProduct['image'], 80, 80);
                }
            } else {
                $image = '';
            }

            #Replace Product Short Code to Values
            $product_replace = $this->getProductReplace($image, $product, $order_info, $attribute, $option);

            $product_find = $this->getProductFind();

            $result[] = trim(str_replace($product_find, $product_replace, $template_product[1]));
        }

        return $result;
    }

    public function getProductFind() {
        $result = array(
            '{product_image}', '{product_name}', '{product_model}', '{product_quantity}', '{product_price}', '{product_price_gross}', '{product_attribute}',
            '{product_option}', '{product_sku}', '{product_upc}', '{product_tax}', '{product_total}', '{product_total_gross}'
        );

        return $result;
    }

    public function getProductReplace($image, $product, $order_info, $attribute, $option) {

        $getProduct = $this->getProduct($product['product_id']);

        $result = array(
            'product_image'       => $image,
            'product_name'        => $product['name'],
            'product_model'       => $product['model'],
            'product_quantity'    => $product['quantity'],
            'product_price'       => $this->currency->format($product['price'], $order_info['currency_code'], $order_info['currency_value']),
            'product_price_gross' => $this->currency->format(($product['price'] + $product['tax']), $order_info['currency_code'], $order_info['currency_value']),
            'product_attribute'   => implode('<br />', $attribute),
            'product_option'      => implode('<br />', $option),
            'product_sku'         => $getProduct['sku'],
            'product_upc'         => $getProduct['upc'],
            'product_tax'         => $this->currency->format($product['tax'], $order_info['currency_code'], $order_info['currency_value']),
            'product_total'       => $this->currency->format($product['total'], $order_info['currency_code'], $order_info['currency_value']),
            'product_total_gross' => $this->currency->format($product['total'] + ($product['tax'] * $product['quantity']), $order_info['currency_code'], $order_info['currency_value'])
        );

        return $result;
    }

    public function getOrderOptions($order_id, $order_product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product_id . "'");

        return $query->rows;
    }

    public function getProductAttributes($product_id, $language_id) {
        $product_attribute_group_data = array();

        $product_attribute_group_query = $this->db->query("SELECT ag.attribute_group_id, agd.name FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_group ag ON (a.attribute_group_id = ag.attribute_group_id) LEFT JOIN " . DB_PREFIX . "attribute_group_description agd ON (ag.attribute_group_id = agd.attribute_group_id) WHERE pa.product_id = '" . (int)$product_id . "' AND agd.language_id = '" . (int)$language_id . "' GROUP BY ag.attribute_group_id ORDER BY ag.sort_order, agd.name");

        foreach ($product_attribute_group_query->rows as $product_attribute_group) {
            $product_attribute_data = array();

            $product_attribute_query = $this->db->query("SELECT a.attribute_id, ad.name, pa.text FROM " . DB_PREFIX . "product_attribute pa LEFT JOIN " . DB_PREFIX . "attribute a ON (pa.attribute_id = a.attribute_id) LEFT JOIN " . DB_PREFIX . "attribute_description ad ON (a.attribute_id = ad.attribute_id) WHERE pa.product_id = '" . (int)$product_id . "' AND a.attribute_group_id = '" . (int)$product_attribute_group['attribute_group_id'] . "' AND ad.language_id = '" . (int)$language_id . "' AND pa.language_id = '" . (int)$language_id . "' ORDER BY a.sort_order, ad.name");

            foreach ($product_attribute_query->rows as $product_attribute) {
                $product_attribute_data[] = array(
                    'attribute_id' => $product_attribute['attribute_id'],
                    'name'         => $product_attribute['name'],
                    'text'         => $product_attribute['text']
                );
            }

            $product_attribute_group_data[] = array(
                'attribute_group_id' => $product_attribute_group['attribute_group_id'],
                'name'               => $product_attribute_group['name'],
                'attribute'          => $product_attribute_data
            );
        }

        return $product_attribute_group_data;
    }

    // Order Voucher
    public function getOrderVouchers($order_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }

    public function getVoucherTemplate($order_info, $getVouchers, $template_voucher) {

        $result = array();

        foreach ($getVouchers as $voucher) {
            // Replace Product Short Code to Values
            $voucher_find = $this->getOrderVoucherFind();
            $voucher_replace = $this->getOrderVoucherReplace($voucher, $order_info);

            $result[] = trim(str_replace($voucher_find, $voucher_replace, $template_voucher[1]));
        }

        return $result;
    }

    public function getOrderVoucherFind() {
        $result = array( '{voucher_description}', '{voucher_amount}' );

        return $result;
    }

    public function getOrderVoucherReplace($voucher, $order_info) {
        $result = array(
            'voucher_description'  => $voucher['description'],
            'voucher_amount'       => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value'])
        );

        return $result;
    }

    // Order Comment
    public function getCommentTemplate($comment, $template_comment) {

        $result = array();

        // Replace Product Short Code to Values
        $comment_find = $this->getCommentFind();
        $comment_replace = $this->getCommentReplace($comment);

        $result[] = trim(str_replace($comment_find, $comment_replace, $template_comment[1]));

        return $result;
    }

    public function getCommentFind() {
        $result = array( '{comment}' );

        return $result;
    }

    public function getCommentReplace($comment) {
        $result = array(
            'comment'  => $comment,
        );

        return $result;
    }

    // Order Tax
    public function getTaxTemplate($totals, $template_tax) {

        $result = array();

        if (isset($totals['tax'])) {
            foreach ($totals['tax'] as $tax) {
                // Replace Product Short Code to Values
                $tax_find = $this->getTaxFind();
                $tax_replace = $this->getTaxReplace($tax);

                $result[] = trim(str_replace($tax_find, $tax_replace, $template_tax[1]));
            }
        }

        return $result;
    }

    public function getTaxFind() {
        $result = array( '{tax_title}', '{tax_value}' );

        return $result;
    }

    public function getTaxReplace($tax) {
        $result = array(
            'tax_title'     => $tax['title'],
            'tax_value'     => $tax['text']
        );

        return $result;
    }

    // Order Total
    public function getTotalTemplate($getTotal, $template_total, $order_info) {

        $result = array();

        foreach ($getTotal as $total) {
            // Replace Product Short Code to Values
            $total_find = $this->getTotalFind();
            $total_replace = $this->getTotalReplace($total, $order_info);

            $result[] = trim(str_replace($total_find, $total_replace, $template_total[1]));
        }

        return $result;
    }

    public function getTotalFind() {
        $result = array( '{total_title}', '{total_value}' );

        return $result;
    }

    public function getTotalReplace($total, $order_info) {
        $result = array(
            'total_title'     => $total['title'],
            'total_value'     => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'])
        );

        return $result;
    }

    public function getDefaultSubject($type, $template_id, $data){
        switch (ucwords($type)) {
            case 'Login':
                $subject = $this->getDefautLoginSubject($template_id, $data);
                break;
            case 'Affilate':
                $subject = $this->getDefautAffilateSubject($template_id, $data);
                break;
            case 'Customer':
                $subject = $this->getDefautCustomerSubject($template_id, $data);
                break;
            case 'Contact':
                $subject = $this->getDefautContactSubject($template_id, $data);
                break;
            case 'Order':
                $subject = $this->getDefautOrderSubject($template_id, $data);
                break;
            case 'Review':
                $subject = $this->getDefautReviewSubject($template_id, $data);
                break;
            case 'Voucher':
                $subject = $this->getDefautVoucherSubject($template_id, $data);
                break;
            case 'Order':
                $subject = $this->getDefautOrderSubject($template_id, $data);
                break;
        }

        return $subject;
    }

    public function getDefaultMessage($type, $template_id, $data){
        switch (ucwords($type)) {
            case 'Login':
                $subject = $this->getDefautLoginMessage($template_id, $data);
                break;
            case 'Affilate':
                $subject = $this->getDefautAffilateMessage($template_id, $data);
                break;
            case 'Customer':
                $subject = $this->getDefautCustomerMessage($template_id, $data);
                break;
            case 'Contact':
                $subject = $this->getDefautContactMessage($template_id, $data);
                break;
            case 'Order':
                $subject = $this->getDefautOrderMessage($template_id, $data);
                break;
            case 'Review':
                $subject = $this->getDefautReviewMessage($template_id, $data);
                break;
            case 'Voucher':
                $subject = $this->getDefautVoucherMessage($template_id, $data);
                break;
            case 'Order':
                $subject = $this->getDefautOrderMessage($template_id, $data);
                break;
        }

        return $subject;
    }

    public function getDefautLoginSubject($type_id, $data){
        $username = $data['username'];

        $subject = 'User '.$username.' logged in on '.$this->config->get('config_name').' admin panel';

        return $subject;
    }

    public function getDefautLoginMessage($type_id, $data){
        $message = 'Hello,<br/><br/>';
        $message .= 'We would like to notify you that user ' . $data['username'] . ' has just logged in to the admin panel of your store, ' . $data['store_name'] . ', using IP address ' . $data['ip_address'].'.<br/><br/>';
        $message .= 'If this is expected you need to do nothing about it. If you suspect a hacking attempt, please log in to your store\'s admin panel immediately and change your password at once.<br/><br/>';
        $message .= 'Best Regards,<br/><br/>';
        $message .= 'The ' . $data['store_name'] . ' team<br/><br/>';

        return $message;
    }

    public function getDefautAffilateSubject($type_id, $data){
        $this->load->language('mail/affiliate');

        if($type_id == 'affiliate_4'){
            $subject = sprintf($this->language->get('text_approve_subject'), $this->config->get('config_name'));
        }else if($type_id == 'affiliate_5'){
            $subject = sprintf($this->language->get('text_commission_subject'), $this->config->get('config_name'));
        } else if($type_id == 'affiliate_1') {
            $subject = sprintf($this->language->get('text_register_subject'), $this->config->get('config_name'));
        } else if($type_id == 'affiliate_3') {
            $subject = sprintf($this->language->get('text_register_approve_subject'), $this->config->get('config_name'));
        } else if($type_id == 'affiliate_2'){
            // Reset Password Null
            $subject = '';
        } else {
            $subject = $this->config->get('config_name') . ' - Affilate Mail';
        }

        $this->load->language('mail/affiliate');

        return $subject;
    }

    public function getDefautAffilateMessage($type_id, $data){
        $this->load->language('mail/affiliate');

        if($type_id == 'affiliate_4'){
            $message  = sprintf($this->language->get('text_approve_welcome'), $this->config->get('config_name')) . "\n\n";
            $message .= $this->language->get('text_approve_login') . "\n";
            $message .= HTTP_CATALOG . 'index.php?route=affiliate/login' . "\n\n";
            $message .= $this->language->get('text_approve_services') . "\n\n";
            $message .= $this->language->get('text_approve_thanks') . "\n";
            $message .= $this->config->get('config_name');
        } else if($type_id == 'affiliate_5') {
            $message  = sprintf($this->language->get('text_commission_received'), $this->currency->format($data['amount'], $this->config->get('config_currency'))) . "\n\n";
            $message .= sprintf($this->language->get('text_commission_total'), $this->currency->format($this->getCommissionTotal($data['affiliate_id']), $this->config->get('config_currency')));
        } else if($type_id == 'affiliate_1') {
            $message = sprintf($this->language->get('text_register_message'), $data['firstname'] . ' ' . $data['lastname'], $this->config->get('config_name'));
        } else if($type_id == 'affiliate_3') {
            $message = sprintf($this->language->get('text_register_approve_message'), $data['firstname'] . ' ' . $data['lastname'], $this->config->get('config_name'));
        } else if($type_id == 'affiliate_2') {
            // Reset Password Null
            $message = sprintf($this->language->get('text_register_approve_subject'), $this->config->get('config_name'), $data['firstname'] . ' ' . $data['lastname']);
        } else {
            $message = 'Hi!' . "\n" . 'Welcome ' . $data['firstname'] . ' ' . $data['lastname'];
        }

        return $message;
    }

    /* Affilate getComissionTotal Frontend & Backend */
    public function getCommissionTotal($affiliate_id) {
        $query = $this->db->query("SELECT SUM(amount) AS total FROM " . DB_PREFIX . "affiliate_commission WHERE affiliate_id = '" . (int)$affiliate_id . "'");

        return $query->row['total'];
    }
    /* Affilate getComissionTotal Frontend & Backend */

    public function getDefaultCustomerSubject($type_id, $data) {
        $this->load->language('mail/customer');

        if($type_id == 'customer_4') {
            $subject = sprintf($this->language->get('text_approve_subject'), $this->config->get('config_name'));
        } else if($type_id == 'customer_1') {
            // Register
            $subject = sprintf($this->language->get('text_register_subject'), $this->config->get('config_name'));
        } else if($type_id == 'customer_2') {
            // Aprove
            $subject = sprintf($this->language->get('text_approve_wait_subject'), $this->config->get('config_name'));
        } else if($type_id == 'customer_3') {
            // Reset
            $subject = sprintf($this->language->get('text_approve_subject'), $this->config->get('config_name'));
        }

        return $subject;
    }

    public function getDefaultCustomerMessage($type_id, $data) {
        $this->load->language('mail/customer');

        if($type_id == 'customer_4') {
            $store_name = $this->config->get('config_name');
            $store_url = HTTP_CATALOG . 'index.php?route=account/login';

            $message = sprintf($this->language->get('text_approve_welcome'), $store_name) . "\n\n";
            $message .= $this->language->get('text_approve_login') . "\n";
            $message .= $store_url . "\n\n";
            $message .= $this->language->get('text_approve_services') . "\n\n";
            $message .= $this->language->get('text_approve_thanks') . "\n";
            $message .= $store_name;
        }else if($type_id == 'customer_1') {
            // Register
            $message = sprintf($this->language->get('text_register_message'), $this->config->get('config_name'));
        } else if($type_id == 'customer_2') {
            // Aprove
            $message = sprintf($this->language->get('text_register_message'), $this->config->get('config_name'));
        } else if($type_id == 'customer_3') {
            $message = ' --- ';
        }

        return $message;
    }

     public function imageResize($filename, $width, $height) {
         if (!is_file(DIR_IMAGE . $filename)) {
             return;
         }

         $extension = pathinfo($filename, PATHINFO_EXTENSION);

         $old_image = $filename;
         $new_image = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . $width . 'x' . $height . '.' . $extension;

         if (!is_file(DIR_IMAGE . $new_image) || (filectime(DIR_IMAGE . $old_image) > filectime(DIR_IMAGE . $new_image))) {
             $path = '';

             $directories = explode('/', dirname(str_replace('../', '', $new_image)));

             foreach ($directories as $directory) {
                 $path = $path . '/' . $directory;

                 if (!is_dir(DIR_IMAGE . $path)) {
                     $this->filesystem->mkdir(DIR_IMAGE . $path);
                 }
             }

             list($width_orig, $height_orig) = getimagesize(DIR_IMAGE . $old_image);

             if ($width_orig != $width || $height_orig != $height) {
                 $image = new Image(DIR_IMAGE . $old_image);
                 $image->resize($width, $height);
                 $image->save(DIR_IMAGE . $new_image);
             } else {
                 copy(DIR_IMAGE . $old_image, DIR_IMAGE . $new_image);
             }
         }

         if ($_SERVER['HTTPS']) {
             return $this->config->get('config_ssl') . 'image/' . $new_image;
         } else {
             return $this->config->get('config_url') . 'image/' . $new_image;
         }
     }

     public function getUploadByCode($code) {
         $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "upload` WHERE code = '" . $this->db->escape($code) . "'");

         return $query->row;
     }

     public function getProduct($product_id) {
         $query = $this->db->query("SELECT DISTINCT p.*, pd.*, md.name AS manufacturer, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special, (SELECT points FROM " . DB_PREFIX . "product_reward pr WHERE pr.product_id = p.product_id AND customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "') AS reward, (SELECT ss.name FROM " . DB_PREFIX . "stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = '" . (int)$this->config->get('config_language_id') . "') AS stock_status, (SELECT wcd.unit FROM " . DB_PREFIX . "weight_class_description wcd WHERE p.weight_class_id = wcd.weight_class_id AND wcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS weight_class, (SELECT lcd.unit FROM " . DB_PREFIX . "length_class_description lcd WHERE p.length_class_id = lcd.length_class_id AND lcd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS length_class, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r2 WHERE r2.product_id = p.product_id AND r2.status = '1' GROUP BY r2.product_id) AS reviews, p.sort_order FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) LEFT JOIN " . DB_PREFIX . "manufacturer_description md ON (p.manufacturer_id = md.manufacturer_id AND md.language_id = '" . (int)$this->config->get('config_language_id') . "') WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

         if ($query->num_rows) {
             return array(
                 'product_id'       => $query->row['product_id'],
                 'name'             => $query->row['name'],
                 'description'      => $query->row['description'],
                 'meta_title'       => $query->row['meta_title'],
                 'meta_description' => $query->row['meta_description'],
                 'meta_keyword'     => $query->row['meta_keyword'],
                 'tag'              => $query->row['tag'],
                 'model'            => $query->row['model'],
                 'sku'              => $query->row['sku'],
                 'upc'              => $query->row['upc'],
                 'ean'              => $query->row['ean'],
                 'jan'              => $query->row['jan'],
                 'isbn'             => $query->row['isbn'],
                 'mpn'              => $query->row['mpn'],
                 'location'         => $query->row['location'],
                 'quantity'         => $query->row['quantity'],
                 'stock_status'     => $query->row['stock_status'],
                 'image'            => $query->row['image'],
                 'manufacturer_id'  => $query->row['manufacturer_id'],
                 'manufacturer'     => $query->row['manufacturer'],
                 'price'            => ($query->row['discount'] ? $query->row['discount'] : $query->row['price']),
                 'special'          => $query->row['special'],
                 'reward'           => $query->row['reward'],
                 'points'           => $query->row['points'],
                 'tax_class_id'     => $query->row['tax_class_id'],
                 'date_available'   => $query->row['date_available'],
                 'weight'           => $query->row['weight'],
                 'weight_class_id'  => $query->row['weight_class_id'],
                 'length'           => $query->row['length'],
                 'width'            => $query->row['width'],
                 'height'           => $query->row['height'],
                 'length_class_id'  => $query->row['length_class_id'],
                 'subtract'         => $query->row['subtract'],
                 'rating'           => round($query->row['rating']),
                 'reviews'          => $query->row['reviews'] ? $query->row['reviews'] : 0,
                 'minimum'          => $query->row['minimum'],
                 'sort_order'       => $query->row['sort_order'],
                 'status'           => $query->row['status'],
                 'date_added'       => $query->row['date_added'],
                 'date_modified'    => $query->row['date_modified'],
                 'viewed'           => $query->row['viewed']
             );
         } else {
             return false;
         }
     }

 }