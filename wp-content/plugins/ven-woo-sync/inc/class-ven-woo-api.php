<?php

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

class VEN_Woo_API {

	const DOMAIN_OPT = 'ven_woo_sync_target';
	const CONSUMER_KEY_OPT = 'ven_woo_sync_consumer_key';
	const SECRET_KEY_OPT = 'ven_woo_sync_secret_key';

	/**
	 * Client instance.
	 * @var Client
	 */
	public $woo_client;

	public function __construct(){
		$domain       = get_option( self::DOMAIN_OPT );
		$consumer_key = get_option( self::CONSUMER_KEY_OPT );
		$secret_key   = get_option( self::SECRET_KEY_OPT );

		$this->woo_client = new Client(
			$domain,
			$consumer_key,
			$secret_key,
			array(
				'version' => 'wc/v3',
			)
		);
	}

	/**
	 * function sync_to_external
	 */
	public function sync_to_external( $products_data ): void {
		$endpoint = 'products/batch';
		try {
			$this->woo_client->post($endpoint, $products_data);
		}catch (HttpClientException $exception ){
			wp_die('Error happen when calling API: '.$exception->getMessage());
		}

	}

	/**
	 * function sync_from_external
	 */
	public function sync_from_external(array $object_ids ) {
		if( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		$list_of_sku = array();
		$products_id_without_sku = array();
		$products_data = array();
		$result = array(
			'success_items' => 0,
			'skip_items' => null,
		);

		foreach ( $object_ids as $post_id ) {
			$product = wc_get_product( $post_id );
			$sku = $product->get_sku();

			if (!empty($sku)){
				$list_of_sku[$post_id] = $sku;
			}else{
				$products_id_without_sku[] = $post_id;
			}
		}

		//if $list_of_sku is empty => return. because we find products by SKU from external site.
		if (empty($list_of_sku)){
			$result['success_items'] = 0;
			$result['skip_items'] = implode(", ", $products_id_without_sku);
			return $result;
		}

		//call api to get list of products by list of SKU
		$sku_string = implode(",", array_values($list_of_sku));
		try {
			$response = $this->woo_client->get('products', array('sku' => $sku_string));
		}catch (HttpClientException $exception ){
			wp_die('Error happen when calling API: '.$exception->getMessage());
		}

		//if $response is empty => return. there is not product to update
		if (empty($response)){
			$result['skip_items'] = implode(", ",$object_ids);
			return $result;
		}

		//convert $response to array and save to $product_data
		if (is_array($response)){
			$products_data = json_decode( json_encode($response),true);
		}

		// find and update products by SKU
		foreach ($products_data as $product_data){
			if ($product_id = array_search($product_data['sku'], $list_of_sku)){
				unset($product_data['sku']);
				$product = wc_get_product( $product_id );
				$this->update_product($product,$product_data);
				$result['success_items'] += 1;
			}else{
				$result['skip_items'] = $result['skip_items'].', '.$product;
			}
		}
		$result['skip_items'] = $result['skip_items'].implode(", ",$products_id_without_sku);


		return $result;
	}

	public function update_product(WC_Product $product, array $product_data ):void {
		$product->set_name($product_data["name"]);
		$product->set_description($product_data["description"]);
		$product->set_regular_price($product_data["price"]);
		$product->set_sale_price($product_data["sale_price"]);
		$product->set_category_ids($product_data["category_ids"]);
		$product->set_status($product_data["status"]);
		$product->set_stock_status($product_data["stock_status"]);
		$product->save();
	}

	/**
	 * function check product is exiting
	 * @param  WC_Product
	 */
	public function is_product_exists(WC_Product $product ) {
		$sku = $product->get_sku();
		if (empty($sku)){
			return false;
		}
		$response = $this->woo_client->get('products', array('sku' => $sku));

		if (!empty($response[0]->id)){
			return $response[0]->id;
		}

		return false;
	}

}
