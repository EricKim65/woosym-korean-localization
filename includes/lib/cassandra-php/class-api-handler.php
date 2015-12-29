<?php

namespace wskl\lib\cassandra;

require_once( 'class-models.php' );

define( 'WSKL_HOST_API_URL', 'http://localhost:8000/api/v1' );  // do not add slashes


/**
 * Class BadResponseException
 *
 * 의도하지 않은 response 를 접수한 경우 발생하는 예외.
 *
 * @package wskl\libs\cassandra
 */
class BadResponseException extends \Exception {

}


function handle_bad_response( $method, BadResponseException &$e ) {

	$message = sprintf( '%s(): Bad response occurred. "%s"', $method, $e->getMessage() );
	error_log( $message );
	wp_die( $message );
}


/**
 * Class Rest_Api_Helper
 *
 * 아주 간단한 REST API 호출 조력자.
 *
 * @package wskl\libs\cassandra
 */
class Rest_Api_Helper {

	/**
	 * 원격지에 API 호출을 보냄.
	 *
	 * @param string $url     원격지 주소
	 * @param string $method  method. GET, POST, PUT, PATCH, DELETE 등.
	 * @param mixed  $body    전송하는 데이터. 기본은 빈 배열
	 * @param array  $accepts 성공으로 간주할 원격지 응답. 기본은 array(200, )
	 * @param array  $headers 추가 헤더.
	 * @param bool   $throws  연결, 혹은 응답 코드에 문제가 있을 경우 예외처리를 하는가? FALSE 면 함수의 리턴은 FALSE
	 *
	 * @return array|bool 두 개의 키로 구성됨. 키는 'code', 'body' 이며 각각 원격지의 응답 코드와 응답 본문이 담경 씨다.
	 *                    헤더 중 content-type 필드의 값이 application/json 인 경우 body 값은 미리 파싱하여 \stdClass 로 만든다.
	 *                    만약 throws 파라미터가 FALSE 일 때 기대하지 않은 응답이 올 경우 FALSE 를 리턴한다.
	 *
	 *
	 * @throws BadResponseException 의도한 응답 코드가 아닐 경우 던지는 예외. $throws 가 TRUE 일 때 동작 (기본)
	 */
	public static function request( $url, $method, $body = NULL, array $accepts = array( 200, ), array $headers = array(), $throws = TRUE ) {

		$args = array(
			'headers' => &$headers,
			'method'  => strtoupper( $method ),
			'body'    => &$body,
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {

			if ( $throws ) {
				throw new BadResponseException(
					sprintf( 'Response is WP_Error object: %s', $response->get_error_message() )
				);
			} else {
				return FALSE;
			}
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( array_search( $response_code, $accepts ) === FALSE ) {

			if ( $throws ) {
				throw new BadResponseException(
					sprintf( "Invalid response code '%s', message: %s", $response_code, $response_body )
				);
			} else {
				return FALSE;
			}
		}

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( $content_type == 'application/json' ) {
			$response_body = json_decode( $response_body, FALSE );
		}

		return array(
			'code' => $response_code,
			'body' => $response_body,
		);
	}
}


class ClientAPI {

	public static function activate( $key_type, $key_value, $site_url, $company_name = '', $activate = FALSE ) {

		assert( $key_type && $key_value && $site_url );

		$obj = NULL;

		try {

			$url  = WSKL_HOST_API_URL . '/auth/activate/';
			$body = array(
				'key_type'     => $key_type,
				'key_value'    => $key_value,
				'site_url'     => $site_url,
				'company_name' => $company_name,
				'activate'     => $activate,
			);

			$response = Rest_Api_Helper::request( $url, 'POST', $body, array( 200, 403, 404 ) );

			if ( $response['code'] == 200 ) {
				$obj = OrderItemRelation::from_response( $response['body'] );
			}

		} catch( BadResponseException $e ) {
			handle_bad_response( __METHOD__, $e );
		}

		return $obj;
	}

	public static function verify( $key_type, $key_value, $site_url ) {

		if ( empty( $key_type ) || empty( $key_value ) ) {
			return NULL;
		};

		$obj = NULL;

		$url  = WSKL_HOST_API_URL . '/auth/verify/';
		$body = array(
			'key_type'  => &$key_type,
			'key_value' => &$key_value,
			'site_url'  => &$site_url,
		);

		$response = Rest_Api_Helper::request( $url, 'POST', $body, array( 200, 403, 404, ), array(), FALSE );

		if ( is_array( $response ) ) {

			assert( isset( $response['code'] ) && isset( $response['body'] ) );

			if ( $response['code'] == 200 ) {
				$obj = OrderItemRelation::from_response( $response['body'] );
			}

			// $obj 은 계속 NULL 값으로 진행.

		} else if ( $response === FALSE ) {

			// 이 경우 연결 자체가 성립하지 않음을 뜻함.
			// FALSE: 서버 연결이 되지 않았음.
			// NULL: 서버 연결은 되었으나 기대한 응답이 오지 않음 (인증실패)
			return FALSE;
		}

		return $obj;
	}
}


class SalesAPI {

	public static function send_data( $key_type, $key_value, $site_url, $user_id, $order ) {

		$obj = NULL;

		try {

			$url     = WSKL_HOST_API_URL . '/logs/sales/';
			$body    = json_encode( static::create_body( $key_type, $key_value, $site_url, $user_id, $order ) );
			$headers = array( 'content-type' => 'application/json', );

			$response = Rest_Api_Helper::request( $url, 'POST', $body, array( 201, ), $headers );
			$obj      = Sales::from_response( $response['body'] );

		} catch( BadResponseException $e ) {
			handle_bad_response( __METHOD__, $e );
		}

		return $obj;

	}

	/**
	 * @param string $key_type
	 * @param string $key_value
	 * @param string $site_url
	 * @param string $user_id
	 * @param mixed  $order
	 *
	 * @return array
	 */
	private static function create_body( $key_type, $key_value, $site_url, $user_id, $order ) {

		$order = wc_get_order( $order );

		assert( $order instanceof \WC_Order, 'Sale object creation failed: $order is not a \WC_Order object.' );

		/** @noinspection PhpUndefinedFieldInspection */
		$body = array(
			'key_type'            => $key_type,
			'key_value'           => $key_value,
			'site_url'            => $site_url,
			'user_id'             => $user_id,                           // Casper's User ID
			'order_id'            => $order->id,
			'order_date'          => $order->order_date,
			'post_status'         => $order->post_status,
			'order_currency'      => $order->order_currency,
			'customer_user_agent' => $order->customer_user_agent,
			'customer_user'       => $order->customer_user,
			'created_via'         => $order->created_via,
			'order_version'       => $order->order_version,
			'billing_country'     => $order->billing_country,
			'billing_city'        => $order->billing_city,
			'billing_state'       => $order->billing_state,
			'shipping_country'    => $order->shipping_country,
			'shipping_city'       => $order->shipping_city,
			'shipping_state'      => $order->shipping_state,
			'payment_method'      => $order->payment_method,
			'order_total'         => $order->order_total,
			'completed_date'      => $order->completed_date,
			'sales_sub'           => array(),
		);

		$sales_sub = &$body['sales_sub'];
		$items     = $order->get_items();
		foreach ( $items as $order_item_id => &$item ) {

			$sales_sub[] = array(
				'order_item_id'   => $order_item_id,
				'order_item_name' => $item['name'],
				'order_item_type' => $item['type'],
				'order_id'        => $order->id,
				'qty'             => $item['qty'],
				'product_id'      => $item['product_id'],
				'variation_id'    => $item['variation_id'],
				'line_subtotal'   => $item['line_subtotal'],
				'line_total'      => $item['line_total'],
			);
		}

		return $body;
	}
}


abstract class ProductLogAPI {

	public static function send_data( $url, $key_type, $key_value, $site_url, $user_id, $product_id, $quantity, $variation_id = 0 ) {

		$obj = NULL;

		try {

			$body    = json_encode( static::create_body( $key_type, $key_value, $site_url, $user_id, $product_id, $quantity, $variation_id ) );
			$headers = array( 'content-type' => 'application/json', );

			$response = Rest_Api_Helper::request( $url, 'POST', $body, array( 201, ), $headers );
			$obj      = ProductLogs::from_response( $response['body'] );

		} catch( BadResponseException $e ) {
			handle_bad_response( __METHOD__, $e );
		}

		return $obj;
	}

	private static function create_body( $key_type, $key_value, $site_url, $user_id, $product_id, $quantity, $variation_id ) {

		/** @var \WC_Product $product */
		$product = wc_get_product( $product_id );
		assert( $product instanceof \WC_Product, 'Product object retrieval failed: $product is not a \WC_Product.' );

		$terms = wp_get_post_terms( $product_id, 'product_cat' );
		if ( is_array( $terms ) ) {
			$term_names = array_map( function ( $t ) { return $t->name; }, $terms );
			sort( $term_names );
			$term_name = join( '|', $term_names );
		} else {
			$term_name = '';
		}

		/** @noinspection PhpUndefinedFieldInspection */
		$body = array(
			'key_type'        => $key_type,
			'key_value'       => $key_value,
			'site_url'        => $site_url,
			'user_id'         => (int) $user_id,    // Casper's User ID
			'customer_id'     => get_current_user_id(),
			'product_id'      => (int) $product_id,
			'variation_id'    => (int) $variation_id,
			'quantity'        => (int) $quantity,
			'product_name'    => $product->get_title(),
			'price'           => $product->get_price(),
			'product_version' => $product->product_version,
			'term_name'       => $term_name,
		);

		return $body;
	}
}


class AddToCartAPI extends ProductLogAPI {

	public static function send_data( $key_type, $key_value, $site_url, $user_id, $product_id, $quantity, $variation_id = 0 ) {

		return parent::send_data( WSKL_HOST_API_URL . '/logs/add-to-carts/', $key_type, $key_value, $site_url, $user_id, $product_id, $quantity, $variation_id );
	}
}


class TodaySeenAPI extends ProductLogAPI {

	public static function send_data( $key_type, $key_value, $site_url, $user_id, $product_id, $quantity, $variation_id = 0 ) {

		return parent::send_data( WSKL_HOST_API_URL . '/logs/today-seen/', $key_type, $key_value, $site_url, $user_id, $product_id, $quantity, $variation_id );
	}
}


class WishListAPI extends ProductLogAPI {

	public static function send_data( $key_type, $key_value, $site_url, $user_id, $product_id, $quantity, $variation_id = 0 ) {

		return parent::send_data( WSKL_HOST_API_URL . '/logs/wish-lists/', $key_type, $key_value, $site_url, $user_id, $product_id, $quantity, $variation_id );
	}
}


class PostAPI {

	public static function send_post( $key_type, $key_value, $site_url, $user_id, $post_id ) {

		assert( $key_type && $key_value && $site_url );

		$obj = NULL;

		try {

			$url        = WSKL_HOST_API_URL . '/logs/posts/';
			$post_array = static::create_post_field( $post_id );
			$body       = array_merge(
				array( 'key_type' => $key_type, 'key_value' => $key_value, 'site_url' => $site_url, ),
				$post_array
			);

		} catch( BadResponseException $e ) {
			handle_bad_response( __METHOD__, $e );
		}
	}
}