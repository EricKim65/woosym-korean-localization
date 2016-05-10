<?php

require_once( WSKL_PATH . '/includes/lib/shipping-tracking/class-wskl-shipping-agents.php' );

if ( ! class_exists( 'WSKL_Agent_Helper' ) ) :

	class WSKL_Agent_Helper {

		private static $agents = null;

		/**
		 * static initializer
		 */
		public static function init() {

			static::$agents = static::default_delivery_agents();

			add_action( 'wc_ajax_' . 'wskl-ship-track', array( __CLASS__, 'wskl_indirect_ship_track' ) );
		}

		public function wskl_indirect_ship_track() {

			$slug   = ( isset( $_GET['agent'] ) ) ? esc_textarea( $_GET['agent'] ) : '';
			$number = ( isset( $_GET['number'] ) ) ? esc_textarea( $_GET['number'] ) : '';

			$agent = WSKL_Agent_Helper::get_tracking_number_agent_by_slug( $slug );
			if ( $agent && ! empty( $number ) ) {
				/** @noinspection PhpIncludeInspection */
				include "track-template-{$slug}.php";
			}

			die();
		}

		/**
		 * 기본 배송 업체를 하드코딩하였다. 기본 자료를 제공.
		 *
		 * @return array WSKL_Shipping_Agents 생성자의 인자를 위한 파라미터 정보.
		 */
		public static function default_delivery_agents() {

			return array(
				// 건영택배
				'geonyoung'   => (object) array(
					'slug'               => 'geonyoung',
					'name'               => '건영택배',
					'query_url_template' => 'http://www.kunyoung.com/goods/goods_01.php?mulno=%s',
				),

				// 경동택배
				'gyeongdoung' => (object) array(
					'slug'               => 'gyeongdoung',
					'name'               => '경동택배',
					'query_url_template' => 'http://www.kdexp.com/sub3_shipping.asp?p_item=%s',
				),

				// 굿투럭
//			'goodtoluck'  => (object) array(
//				'slug'               => 'goodtoluck',
//				'name'               => '굿투럭',
//				'query_url_template' => '', // POST 조회. API
//			),

				// 대신택배
				'daeshin'     => (object) array(
					'slug'               => 'daeshin',
					'name'               => '대신택배',
					'query_url_template' => 'http://apps.ds3211.co.kr/freight/internalFreightSearch.ht?billno=%s',
				),

				// 로젠택배
				'logen'       => (object) array(
					'slug'               => 'logen',
					'name'               => '로젠택배',
					'query_url_template' => 'https://www.ilogen.com/iLOGEN.Web.New/TRACE/TraceDetail.aspx?gubun=fromview&slipno=%s',
				),


				// 우체국택배
				'post-office' => (object) array(
					'slug'               => 'post-office',
					'name'               => '우체국택배',
					'query_url_template' => 'http://trace.epost.go.kr/xtts/servlet/kpl.tts.common.svl.SttSVL?ems_gubun=E&POST_CODE=&mgbn=trace&traceselect=1&target_command=kpl.tts.tt.epost.cmd.RetrieveOrderConvEpostPoCMD&JspURI=%2Fxtts%2Ftt%2Fepost%2Ftrace%2FTrace_list.jsp&postNum=6899063435971&x=22&y=2&sid1=%s',
				),

				// 일양로지스
				'ilyang'      => (object) array(
					'slug'               => 'ilyang',
					'name'               => '일양로지스',
					'query_url_template' => 'https://www.ilyanglogis.com/product/visa_01_result1.asp?hawb_no=%s',
				),

				// 천일택배
				'chunil'      => (object) array(
					'slug'               => 'chunil',
					'name'               => '천일택배',
					'query_url_template' => 'http://www.chunil.co.kr/kor/taekbae/HTrace.jsp?transNo=%s',
				),

				// 한덱스
//			'handex'  => (object) array(
//				'slug'               => 'handex',
//				'name'               => '한덱스',
//				'query_url_template' => '',
//			),
//
//			// 한의사랑택배
//			'hani-ps'  => (object) array(
//				'slug'               => 'hani-ps',
//				'name'               => '한의사랑택배',
//				'query_url_template' => '',
//			),

				// 한진택배
				'hanjin'      => (object) array(
					'slug'               => 'hanjin',
					'name'               => '한진택배',
					'query_url_template' => 'https://www.hanjin.co.kr/Delivery_html/inquiry/result_waybill.jsp?wbl_num=%s',
				),

				// 합동택배
				'hapdong'     => (object) array(
					'slug'               => 'hapdong',
					'name'               => '합동택배',
					'query_url_template' => 'http://www.hdexp.co.kr/parcel/order_result_t.asp?p_item=%s',
				),

				// 현대택배
				'hyeondae'    => (object) array(
					'slug'               => 'hyeondae',
					'name'               => '현대택배',
					'query_url_template' => add_query_arg( array( 'wc-ajax' => 'wskl-ship-track', 'agent' => 'hyeondae', ), site_url() ) . '&number=%s', // using indirect,
				),

				// CJ대한통운 (CJ GLS)
				'cj-daehan'   => (object) array(
					'slug'               => 'cj-daehan',
					'name'               => 'CJ대한통운',
					'query_url_template' => 'https://www.doortodoor.co.kr/parcel/doortodoor.do?fsp_action=PARC_ACT_002&fsp_cmd=retrieveInvNoACT&invc_no=%s',
				),

//			// CVSnet 편의점택배
//			'cvsnet'  => (object) array(
//				'slug'               => 'cvsnet',
//				'name'               => 'CVSnet 편의점택배',
//				'query_url_template' => '',
//			),
//
//			// GSMNtoN(인로스)
//			'gsmnton'  => (object) array(
//				'slug'               => 'gsmnton',
//				'name'               => 'GSMNtoN(인로스)',
//				'query_url_template' => '',
//			),
//
//			// GTX로지스
//			'gtx-logis'  => (object) array(
//				'slug'               => 'gtx-logis',
//				'name'               => 'GTX로지스',
//				'query_url_template' => '',
//			),
//
//			// i-Parcel
//			'i-parcel'  => (object) array(
//				'slug'               => 'i-parcel',
//				'name'               => 'i-Parcel',
//				'query_url_template' => '',
//			),
//
				// KG로지스
				'kg-logis'    => (object) array(
					'slug'               => 'kg-logis',
					'name'               => 'KG로지스',
					'query_url_template' => 'http://www.kglogis.co.kr/delivery/delivery_result.jsp?item_no=%s',
				),

				// KGB택배
				'kgb'         => (object) array(
					'slug'               => 'kgb',
					'name'               => 'KGB택배',
					'query_url_template' => 'https://www.kgbls.co.kr/sub5/trace.asp?f_slipno=%s',
				),
//
//			// KGL네트웍스
//			'kgl'  => (object) array(
//				'slug'               => 'kgl',
//				'name'               => 'KGL네트웍스',
//				'query_url_template' => '',
//			),
			);
		}

		/**
		 * 입력한 슬러그에 해당하는 WSKL_Shipping_Agents 객체를 리턴.
		 *
		 * @param $slug
		 *
		 * @return bool|WSKL_Shipping_Agents
		 */
		public static function get_tracking_number_agent_by_slug( $slug ) {

			if ( ! static::$agents || ! isset( static::$agents[ $slug ] ) ) {
				return false;
			}

			$agent = (array) static::$agents[ $slug ];

			$instance = new WSKL_Shipping_Agents(
				$agent['slug'],
				$agent['name'],
				$agent['query_url_template']
			);

			return $instance;
		}

		/**
		 * @return array 배송 업체의 슬러그를 키로, 각 업체 이름을 값으로 가진 배열 반환
		 */
		public static function get_agent_list() {

			$agents = array();
			foreach ( array_keys( static::$agents ) as $k ) {
				$agents[ $k ] = static::$agents[ $k ]->name;
			}

			return $agents;
		}
	}

endif;

WSKL_Agent_Helper::init();
