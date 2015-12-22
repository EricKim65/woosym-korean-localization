<?php

namespace wskl\lib\cassandra;

if ( ! defined( 'CASSANDRA_DEFAULT_TIMEZONE' ) ) {
	define( 'CASSANDRA_DEFAULT_TIMEZONE', 'Asia/Seoul' );
}

/**
 * response 로부터 오는 텍스트나 아니면 \DateTime 객체를 안전하게 \DateTime 객체로 리턴.
 *
 * @param mixed  $datetime
 * @param string $timezone
 * @param bool   $correct_timezone
 *
 * @return \DateTime
 */
function convert_datetime( $datetime, $correct_timezone = TRUE, $timezone = CASSANDRA_DEFAULT_TIMEZONE ) {

	static $tz = NULL;

	if ( ! $tz ) {
		$tz = new \DateTimeZone( $timezone );
	}

	$obj = FALSE;

	if ( is_string( $datetime ) ) {

		if ( $correct_timezone ) {
			$obj = new \DateTime( $datetime, $tz );
		} else {
			$obj = new \DateTime( $datetime );
		}
	}

	if ( $datetime instanceof \DateTime ) {

		$obj = clone $datetime;

		if ( $correct_timezone && $tz != $obj->getTimezone() ) {
			$obj->setTimezone( $tz );
		}
	}

	assert( $obj instanceof \DateTime );

	return $obj;
}


/**
 * Interface APIResponseHandler
 *
 * @package wskl\libs\cassandra
 */
interface APIResponseHandler {

	public static function from_response( \stdclass $response );
}


/**
 * Class CreatedMixin
 */
class CreatedMixin {

	/**
	 * @var \DateTime 생성일자.
	 */
	private $created;

	/**
	 * @return \DateTime created getter
	 */
	public function get_created() {

		return $this->created;
	}

	/**
	 * @param $created mixed created setter
	 *
	 * @see   CreatedMixin::convert_datetime()
	 */
	protected function set_created( $created ) {

		$this->created = convert_datetime( $created );
	}
}


/**
 * Class CreatedUpdatedMixin
 */
class CreatedUpdatedMixin extends CreatedMixin {

	/**
	 * @var \DateTime 수정일자.
	 */
	private $updated;

	/**
	 * @return \DateTime updated getter
	 */
	public function get_updated() {

		return $this->updated;
	}

	/**
	 * @param $updated mixed 문자열이나 \DateTime 객체.
	 *
	 * @see   CreatedMixin::convert_datetime()
	 */
	protected function set_updated( $updated ) {

		$this->updated = convert_datetime( $updated );
	}
}


/**
 * Class Domain
 */
final class Domain extends CreatedUpdatedMixin implements APIResponseHandler {

	/**
	 * @var string 회사 이름. 선택 사항.
	 */
	private $company_name;

	/**
	 * @var string 회사 URL. 필수 사항.
	 */
	private $url;

	/**
	 * @var \DateTime|NULL. 도메인 비활성화된 시간. NULL 이면 활성화되어 있음.
	 */
	private $deactivated;

	/**
	 * @return string company_name getter
	 */
	public function get_company_name() {

		return $this->company_name;
	}

	/**
	 * @return string url getter
	 */
	public function get_url() {

		return $this->url;
	}

	/**
	 * @return bool
	 */
	public function is_deactivated() {

		return $this->deactivated != NULL;
	}

	/**
	 * @return \DateTime|NULL deactivated getter
	 */
	public function get_deactivated() {

		return $this->deactivated;
	}

	/**
	 * Mapping method. Rest API 호출 후 전달되는 JSON 은 parse 되어 stdClass 로 변환된다.
	 * 이 때 stdClass 는 사용하기 매우 불안정하므로 Domain 부분은 Domain class 와 매핑시킨다.
	 *
	 * @param \stdClass $response
	 *
	 * @return Domain
	 */
	public static function from_response( \stdClass $response ) {

		$obj = new static();

		$obj->company_name = esc_textarea( $response->company_name );
		$obj->url          = esc_url( $response->url );

		if ( $response->deactivated ) {
			$obj->deactivated = convert_datetime( $response->deactivated );
		} else {
			$obj->deactivated = NULL;
		}

		$obj->set_created( $response->created );
		$obj->set_updated( $response->updated );

		return $obj;
	}
}


/**
 * Class Key
 */
final class Key extends CreatedUpdatedMixin implements APIResponseHandler {

	/**
	 * @var string 키 정보
	 */
	private $key;

	/**
	 * @var string. Because it is too typical, that it is substituted to a string. Originally, it is a foreign key.
	 */
	private $type;

	/**
	 * @var boolean 활성화 여부
	 */
	private $_is_active;

	/**
	 * @var \DateTime It is originally a 'date' class. Its time is always 00:00:00 in KST.
	 */
	private $issue_date;

	/**
	 * @var \DateTime It is originally a 'date' class. Its time is always 23:59:59 in KST.
	 */
	private $expire_date;

	/**
	 * @return string key getter
	 */
	public function get_key() {

		return $this->key;
	}

	/**
	 * @return string type getter
	 */
	public function get_type() {

		return $this->type;
	}

	/**
	 * @return bool is_active
	 */
	public function is_active() {

		return $this->_is_active;
	}

	/**
	 * @return \DateTime issue_date getter
	 */
	public function get_issue_date() {

		return $this->issue_date;
	}

	/**
	 * @return \DateTime expire_date getter
	 */
	public function get_expire_date() {

		return $this->expire_date;
	}

	public function is_expired() {

		$today = new \DateTime();

		return $today < $this->issue_date || $today > $this->expire_date;
	}

	public function get_days_left() {

		$today = new \DateTime( 'now', new \DateTimeZone( 'Asia/Seoul' ) );
		$interval = $today->diff( $this->expire_date );

		return $interval->invert == 0 ? $interval->days : FALSE;
	}

	/**
	 * Mapping method. Rest API 호출 후 전달되는 JSON 은 parse 되어 stdClass 로 변환된다.
	 * 이 때 stdClass 는 사용하기 매우 불안정하므로 Key class 와 매핑시킨다.
	 *
	 * @param \stdClass $response
	 *
	 * @return Key
	 */
	public static function from_response( \stdClass $response ) {

		$obj = new static();

		$obj->key        = esc_textarea( $response->key );
		$obj->type       = esc_textarea( $response->type->type );
		$obj->_is_active = filter_var( $response->is_active, FILTER_VALIDATE_BOOLEAN );
		$obj->issue_date = convert_datetime( $response->issue_date );

		$obj->expire_date = convert_datetime( $response->expire_date );
		$obj->expire_date->setTime( 23, 59, 59 );

		$obj->set_created( $response->created );
		$obj->set_updated( $response->updated );

		return $obj;
	}
}


/**
 * Class OrderItemRelation
 */
final class OrderItemRelation implements APIResponseHandler {

	/**
	 * @var integer
	 */
	private $order_item_id;

	/**
	 * @var Key|integer
	 */
	private $key;

	/**
	 * @var Domain|integer
	 */
	private $domain;

	/**
	 * @var integer
	 */
	private $user_id;

	public function get_order_item_id() {

		return $this->order_item_id;
	}

	public function get_key() {

		return $this->key;
	}

	public function get_domain() {

		return $this->domain;
	}

	public function get_user_id() {

		return $this->user_id;
	}

	/**
	 * @param \stdClass $response
	 *
	 * @return OrderItemRelation
	 */
	public static function from_response( \stdClass $response ) {

		$obj = new static();

		$obj->order_item_id = absint( $response->order_item_id );

		if ( is_numeric( $response->key ) ) {

			$obj->key = absint( $response->key );
			assert( $obj->key !== FALSE, '$response->key assertion failed.' );

		} else if ( $response->key instanceof \stdClass ) {

			$obj->key = Key::from_response( $response->key );

		} else {

			assert( FALSE, 'unknown $response->key structure.' );
		}

		if ( is_numeric( $response->domain ) ) {

			$obj->domain = absint( $response->domain );
			assert( $obj->domain !== FALSE, '$response->domain assertion failed.' );

		} else if ( $response->domain instanceof \stdClass ) {

			$obj->domain = Domain::from_response( $response->domain );

		} else if ( $response->domain === NULL ) {

			// It can be null. Ok, do nothing.
			$obj->domain = NULL;

		} else {

			assert( FALSE, 'unknown $response->domain structure.' );
		}

		$obj->user_id = absint( $response->user_id );
		assert( $obj->user_id !== FALSE );

		return $obj;
	}

	public static function from_response_list( array &$response ) {

		$output = array();

		foreach ( $response as &$elem ) {
			$output[] = static::from_response( $elem );
		}

		return $output;
	}
}


class Sales implements APIResponseHandler {

	/** @var Domain $domain */
	private $domain;

	/** @var  \DateTime $post_date */
	private $post_date;

	/** @var \DateTime $post_date_gmt */
	private $post_date_gmt;

	private $post_status;

	/** @var string $order_currency */
	private $order_currency;

	/** @var string $customer_user_agent */
	private $customer_user_agent;

	/** @var int $customer_user */
	private $customer_user;

	/** @var string $created_via */
	private $created_via;

	/** @var string $order_version */
	private $order_version;

	/** @var string $billing_country */
	private $billing_country;

	/** @var string $billing_city */
	private $billing_city;

	/** @var string $billing_state */
	private $billing_state;

	/** @var string $shipping_country */
	private $shipping_country;

	/** @var string $shipping_city */
	private $shipping_city;

	/** @var string $shipping_state */
	private $shipping_state;

	/** @var string $payment_method */
	private $payment_method;

	/** @var string $order_total 소수점 문제가 있을 수 있으므로 문자열 그대로 처리 */
	private $order_total;

	/** @var \DateTime $completed_date */
	private $completed_date;

	/**
	 * @return Domain
	 */
	public function get_domain() {

		return $this->domain;
	}

	/**
	 * @return \DateTime
	 */
	public function get_post_date() {

		return $this->post_date;
	}

	/**
	 * @return \DateTime
	 */
	public function get_post_date_gmt() {

		return $this->post_date_gmt;
	}

	/**
	 * @return mixed
	 */
	public function get_post_status() {

		return $this->post_status;
	}

	/**
	 * @return string
	 */
	public function get_order_currency() {

		return $this->order_currency;
	}

	/**
	 * @return string
	 */
	public function get_customer_user_agent() {

		return $this->customer_user_agent;
	}

	/**
	 * @return int
	 */
	public function get_customer_user() {

		return $this->customer_user;
	}

	/**
	 * @return string
	 */
	public function get_created_via() {

		return $this->created_via;
	}

	/**
	 * @return string
	 */
	public function get_order_version() {

		return $this->order_version;
	}

	/**
	 * @return string
	 */
	public function get_billing_country() {

		return $this->billing_country;
	}

	/**
	 * @return string
	 */
	public function get_billing_city() {

		return $this->billing_city;
	}

	/**
	 * @return string
	 */
	public function get_billing_state() {

		return $this->billing_state;
	}

	/**
	 * @return string
	 */
	public function get_shipping_country() {

		return $this->shipping_country;
	}

	/**
	 * @return string
	 */
	public function get_shipping_city() {

		return $this->shipping_city;
	}

	/**
	 * @return string
	 */
	public function get_shipping_state() {

		return $this->shipping_state;
	}

	/**
	 * @return string
	 */
	public function get_payment_method() {

		return $this->payment_method;
	}

	/**
	 * @return string
	 */
	public function get_order_total() {

		return $this->order_total;
	}

	/**
	 * @return \DateTime
	 */
	public function get_completed_date() {

		return $this->completed_date;
	}

	/**
	 * @return array
	 */
	public function get_sales_sub() {

		return $this->sales_sub;
	}

	/** @var array $sales_sub */
	private $sales_sub = array();

	/**
	 * @param \stdClass $response
	 *
	 * @return Sales
	 */
	public static function from_response( \stdClass $response ) {

		$obj = new static();

		if ( property_exists( $response, 'domain' ) && $response->domain instanceof \stdClass ) {
			$obj->domain = Domain::from_response( $response->domain );
		}

		if ( property_exists( $response, 'post_date' ) ) {
			$obj->post_date = convert_datetime( $response->post_date, FALSE );
		}

		if ( property_exists( $response, 'post_date_gmt' ) ) {
			$obj->post_date_gmt = convert_datetime( $response->post_date_gmt, FALSE );
		}

		if ( property_exists( $response, 'post_status' ) ) {
			$obj->post_status = sanitize_text_field( $response->post_status );
		}

		if ( property_exists( $response, 'order_currency' ) ) {
			$obj->order_currency = sanitize_text_field( $response->order_currency );
		}

		if ( property_exists( $response, 'customer_user_agent' ) ) {
			$obj->customer_user_agent = sanitize_text_field( $response->customer_user_agent );
		}

		if ( property_exists( $response, 'customer_user' ) ) {
			$obj->customer_user = absint( $response->customer_user );
		}

		if ( property_exists( $response, 'created_via' ) ) {
			$obj->created_via = sanitize_text_field( $response->created_via );
		}

		if ( property_exists( $response, 'order_version' ) ) {
			$obj->order_version = sanitize_text_field( $response->order_version );
		}

		if ( property_exists( $response, 'billing_country' ) ) {
			$obj->billing_country = sanitize_text_field( $response->billing_country );
		}

		if ( property_exists( $response, 'billing_city' ) ) {
			$obj->billing_city = sanitize_text_field( $response->billing_city );
		}

		if ( property_exists( $response, 'billing_state' ) ) {
			$obj->billing_state = sanitize_text_field( $response->billing_state );
		}

		if ( property_exists( $response, 'shipping_country' ) ) {
			$obj->shipping_country = sanitize_text_field( $response->shipping_country );
		}

		if ( property_exists( $response, 'shipping_city' ) ) {
			$obj->shipping_city = sanitize_text_field( $response->shipping_city );
		}

		if ( property_exists( $response, 'shipping_state' ) ) {
			$obj->shipping_state = sanitize_text_field( $response->shipping_state );
		}

		if ( property_exists( $response, 'payment_method' ) ) {
			$obj->payment_method = sanitize_text_field( $response->payment_method );
		}

		if ( property_exists( $response, 'order_total' ) ) {
			$obj->order_total = sanitize_text_field( $response->order_total );
		}

		if ( property_exists( $response, 'completed_date' ) ) {
			$obj->completed_date = convert_datetime( $response->completed_date, FALSE );
		}

		if ( property_exists( $response, 'sales_sub' ) && is_array( $response->sales_sub ) ) {

			foreach ( $response->sales_sub as &$sub ) {

				if ( $sub instanceof \stdClass ) {
					$obj->sales_sub[] = SalesSub::from_response( $sub );
				}
			}
		}

		return $obj;
	}
}


class SalesSub implements APIResponseHandler {

	/** @var int $order_item_id */
	private $order_item_id;

	/** @var string $order_item_name */
	private $order_item_name;

	/** @var string $order_item_type */
	private $order_item_type;

	/** @var int $order_id */
	private $order_id;

	/** @var int $qty */
	private $qty;

	/** @var int $product_id */
	private $product_id;

	/** @var int $variation_id */
	private $variation_id;

	/** @var string $line_subtotal 소수점 문제가 있을 수 있으므로 문자열 그대로 처리 */
	private $line_subtotal;

	/** @var string $line_total 소수점 문제가 있을 수 있으므로 문자열 그대로 처리 */
	private $line_total;

	/**
	 * @return int
	 */
	public function get_order_item_id() {

		return $this->order_item_id;
	}

	/**
	 * @return string
	 */
	public function get_order_item_name() {

		return $this->order_item_name;
	}

	/**
	 * @return string
	 */
	public function get_order_item_type() {

		return $this->order_item_type;
	}

	/**
	 * @return int
	 */
	public function get_order_id() {

		return $this->order_id;
	}

	/**
	 * @return int
	 */
	public function get_qty() {

		return $this->qty;
	}

	/**
	 * @return int
	 */
	public function get_product_id() {

		return $this->product_id;
	}

	/**
	 * @return int
	 */
	public function get_variation_id() {

		return $this->variation_id;
	}

	/**
	 * @return string
	 */
	public function get_line_subtotal() {

		return $this->line_subtotal;
	}

	/**
	 * @return string
	 */
	public function get_line_total() {

		return $this->line_total;
	}

	/**
	 * @param \stdClass $response
	 *
	 * @return SalesSub
	 */
	public static function from_response( \stdClass $response ) {

		$obj = new static();

		if ( property_exists( $response, 'order_item_id' ) ) {
			$obj->order_item_id = absint( $response->order_item_id );
		}

		if ( property_exists( $response, 'order_item_name' ) ) {
			$obj->order_item_name = sanitize_text_field( $response->order_item_name );
		}

		if ( property_exists( $response, 'order_item_type' ) ) {
			$obj->order_item_type = sanitize_text_field( $response->order_item_type );
		}

		if ( property_exists( $response, 'order_id' ) ) {
			$obj->order_id = absint( $response->order_id );
		}

		if ( property_exists( $response, 'qty' ) ) {
			$obj->qty = absint( $response->qty );
		}

		if ( property_exists( $response, 'product_id' ) ) {
			$obj->product_id = absint( $response->product_id );
		}

		if ( property_exists( $response, 'variation_id' ) ) {
			$obj->variation_id = absint( $response->variation_id );
		}

		if ( property_exists( $response, 'line_subtotal' ) ) {
			$obj->line_subtotal = sanitize_text_field( $response->line_subtotal );
		}

		if ( property_exists( $response, 'line_total' ) ) {
			$obj->line_total = sanitize_text_field( $response->line_total );
		}

		return $obj;
	}
}

class ProductLogs implements APIResponseHandler {

	public $user_id;

	public $domain;

	public $created;

	public $customer_id;

	public $product_id;

	public $variation_id;

	public $quantity;

	public $price;

	public $product_name;

	public $product_version;

	public $term_name;

	public $log_type;

	public static function from_response( \stdClass $response ) {

		$obj = new static();

		if( property_exists( $response, 'user_id' ) ) {
			$obj->user_id = absint( $response->user_id );
		}

		if( property_exists( $response, 'domain' ) && $response->domain instanceof \stdClass ) {
			$obj->domain = Domain::from_response( $response->domain );
		}

		if( property_exists( $response, 'created' ) ) {
			$obj->created = convert_datetime( $response->created, FALSE );
		}

		if( property_exists( $response, 'customer_id' ) ) {
			$obj->customer_id = absint( $response->customer_id );
		}

		if( property_exists( $response, 'product_id' ) ) {
			$obj->product_id = absint( $response->product_id );
		}

		if( property_exists( $response, 'variation_id' ) ) {
			$obj->variation_id = absint( $response->variation_id );
		}

		if( property_exists( $response, 'quantity' ) ) {
			$obj->quantity = absint( $response->quantity );
		}

		if( property_exists( $response, 'price' ) ) {
			$obj->price = sanitize_text_field( $response->price );
		}

		if( property_exists( $response, 'product_name' ) ) {
			$obj->product_name = sanitize_text_field( $response->product_name );
		}

		if( property_exists( $response, 'product_version' ) ) {
			$obj->product_version = sanitize_text_field( $response->product_version );
		}

		if( property_exists( $response, 'term_name' ) ) {
			$obj->term_name = sanitize_text_field( $response->term_name );
		}

		if( property_exists( $response, 'log_type' ) ) {
			$obj->log_type = sanitize_text_field( $response->log_type );
		}

		return $obj;
	}
}
