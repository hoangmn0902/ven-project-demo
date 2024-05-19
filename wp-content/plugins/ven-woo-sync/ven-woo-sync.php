<?php
/*
Plugin Name: Hoang Sync Woocommerce Products
Description: Sync Woocommerce Products between two sites
Version: 1.0
Author: Hoang Nguyen
*/

define( 'PLUGIN_SYNC_WOO_URI', plugin_dir_url( __FILE__ ));
define( 'PLUGIN_SYNC_WOO_PATH', plugin_dir_path( __FILE__ ));

require_once (PLUGIN_SYNC_WOO_PATH . 'vendor/autoload.php');
require_once (PLUGIN_SYNC_WOO_PATH . 'inc/class-ven-woo-api.php');
require_once (PLUGIN_SYNC_WOO_PATH . 'inc/admin-menu.php');

class VEN_Woocommerce_Sync {

    private $ven_woo_api = null;

	public function __construct(){
	    $this->ven_woo_api = new VEN_Woo_API();
		add_action( 'bulk_actions-edit-product', array($this,'sync_woo_product_actions') );
		add_filter( 'handle_bulk_actions-edit-product', array($this,'bulk_action_handler'), 10, 3 );
		add_action( 'admin_notices', array($this,'bulk_action_notices') );
	}


	public function sync_woo_product_actions( $bulk_array ) {
		$bulk_array[ 'sync_to_external' ] = 'Sync to external site';
		$bulk_array[ 'sync_from_external' ] = 'Sync from external site';
		return $bulk_array;
	}

	/**
	 * bulk action handler
	 */
	public function bulk_action_handler( $redirect, $doaction, $object_ids ) {
		set_time_limit(300);
		$redirect = remove_query_arg(array('success_items','skip_items'),$redirect);

		//sync_to_external action
		if ( 'sync_to_external' === $doaction ) {
			$products_data = array(
				'create' => array(),
				'update' => array(),
			);

			foreach ( $object_ids as $post_id ) {
				// get product object
				$product = wc_get_product( $post_id );

				// prepare product data before the loop
				$product_data = $product->get_data();

				unset( $product_data[ 'id' ] );
                unset($product_data['attributes']);

				// Fix: "Error 400 Bad Request low_stock_amount is not of type integer,null." error
				$product_data[ 'low_stock_amount' ] = (int) $product_data[ 'low_stock_amount' ];

				// In order to sync a product image we have to do some additional stuff
				if( $product_data[ 'image_id' ] ) {
					$product_data[ 'images' ] = array(
						array(
							'src' => wp_get_attachment_url( $product_data[ 'image_id' ] )
						)
					);
					unset( $product_data[ 'image_id' ] );
				}

				if( $id = $this->ven_woo_api->is_product_exists($product) ) {
					$product_data[ 'id' ] = $id;
					$products_data[ 'update' ][] = $product_data;
				} else {
					$products_data[ 'create' ][] = $product_data;
				}

			}

			$this->ven_woo_api->sync_to_external($products_data);
			$result = array(
				'success_items' =>  count( $object_ids ),
			);

		}

		//sync_from_external action
		if ( 'sync_from_external' === $doaction ) {
			$result = $this->ven_woo_api->sync_from_external($object_ids);
		}

		//add query args to URL to in show notices
		$redirect = add_query_arg(
			$result,
			$redirect
		);

		return $redirect;

	}

	/**
	 * show admin notices
	 */
	public function bulk_action_notices() {
	    if (!empty($_REQUEST[ 'success_items' ]) ):
        ?>
            <div class="updated notice is-dismissible">
                <p>Products updated</p>
                <p>Success items: <?php echo $_REQUEST[ 'success_items' ] ?></p>
                <?php if(!empty($_REQUEST[ 'skip_items' ])): ?>
                    <p>Skip items ID: <?php echo $_REQUEST[ 'skip_items' ] ?></p>
                <?php endif;?>
            </div>
        <?php
        endif;
	}
}

$ven_woo_sync = new VEN_Woocommerce_Sync();