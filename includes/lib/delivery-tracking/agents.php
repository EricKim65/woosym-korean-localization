<?php

namespace wskl\delivery_tracking\agents;

/**
 * 송장 번호로 추적을 할 수 있는 배송 업체 객체
 *
 * Class Tracking_Number_Agent
 *
 * @package wskl\delivery_tracking\agents
 */
class Tracking_Number_Agent {

	/**
	 * @var string 슬러그.
	 */
	private $slug;

	/**
	 * @var string 배송 업체의 이름.
	 */
	private $name;

	/**
	 * @var string URL 주소 스트링. printf() 서식 문자열이 사용될 수 있다.
	 */
	private $query_url_template;

	public function __construct( $slug, $name, $query_url_template ) {

		$this->set_slug( $slug );
		$this->set_name( $name );
		$this->set_query_url_template( $query_url_template );
	}

	/**
	 * 배송 추적 쿼리 주소
	 *
	 * @param $tracking_number string. 송장 번호
	 *
	 * @return string
	 */
	public function get_url_by_tracking_number( $tracking_number ) {

		return sprintf( $this->get_query_url_template(), $tracking_number );
	}

	/**
	 * @return string
	 */
	public function get_slug() {

		return $this->slug;
	}

	/**
	 * @param $slug
	 */
	protected function set_slug( $slug ) {

		$this->slug = $slug;
	}

	/**
	 * @return string
	 */
	public function get_name() {

		return $this->name;
	}

	/**
	 * @param $name
	 */
	protected function set_name( $name ) {

		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function get_query_url_template() {

		return urldecode( $this->query_url_template );
	}

	/**
	 * @param $query_url_template
	 */
	protected function set_query_url_template( $query_url_template ) {

		$this->query_url_template = $query_url_template;
	}
}


/**
 * Class Agent_Helper
 *
 * 택배 회사관련 작업을 위한 헬퍼 클래스.
 *
 * options 테이블에 'delivery-agents' 키로 배송 업체 목록을 저장한다. 이 값은 PHP serialize 되어 있다.
 * 풀어 보면, 1st depth 는 array로 되어 있다. 키는 배송 업체의 slug 이다. 값은 stdObject 이다.
 * stdObject (2nd depth)는 다음과 같은 속성을 가진다.
 *   - slug: string. 배송 업체의 슬러그. select - option tag의 값으로 활용하라.
 *   - name: string. 배송 업체의 이름. select - option tag에서 유저에게 보여 주는 텍스트로 활용하라.
 *   - query_url_template: string. 쿼리 주소의 템플릿. printf() 에서 쓰이는 서식문자열을 가지고 있다.
 *     송장 번호 같은 것이 입력이 될 수 있다.
 *
 * @package wskl\delivery_tracking\agents
 */
class Agent_Helper {

	private static $agents = NULL;

	/**
	 * static initializer
	 */
	public static function init() {

		// just to reset the data.
		// delete_option( 'delivery-agents' );
		static::$agents = get_option( 'delivery-agents' );

		if ( ! static::$agents ) {
			static::$agents = static::default_delivery_agents();
			update_option( 'delivery-agents', static::$agents );
		}
	}

	/**
	 * 기본 배송 업체를 하드코딩하였다. 기본 자료를 제공.
	 * @return array Tracking_Number_Agent 생성자의 인자를 위한 파라미터 정보.
	 */
	public static function default_delivery_agents() {

		return array(
			// 건영택배
			'geonyoung'  => (object) array(
				'slug'               => 'geonyoung',
				'name'               => '건영택배',
				'query_url_template' => 'http://www.kunyoung.com/goods/goods_01.php?mulno=%s',
			),

			// 경동택배
			'gyeongdoung'  => (object) array(
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
			'daeshin'  => (object) array(
				'slug'               => 'daeshin',
				'name'               => '대신택배',
				'query_url_template' => 'http://apps.ds3211.co.kr/freight/internalFreightSearch.ht?billno=%s',
			),

			// 로젠택배
			'logen'     => (object) array(
				'slug'               => 'logen',
				'name'               => '로젠택배',
				'query_url_template' => 'https://www.ilogen.com/iLOGEN.Web.New/TRACE/TraceDetail.aspx?gubun=fromview&slipno=%s',
			),


			// 우체국택배
			'post-office'  => (object) array(
				'slug'               => 'post-office',
				'name'               => '우체국택배',
				'query_url_template' => 'http://trace.epost.go.kr/xtts/servlet/kpl.tts.common.svl.SttSVL?ems_gubun=E&POST_CODE=&mgbn=trace&traceselect=1&target_command=kpl.tts.tt.epost.cmd.RetrieveOrderConvEpostPoCMD&JspURI=%2Fxtts%2Ftt%2Fepost%2Ftrace%2FTrace_list.jsp&postNum=6899063435971&x=22&y=2&sid1=%s',
			),

			// 일양로지스
			'ilyang'  => (object) array(
				'slug'               => 'ilyang',
				'name'               => '일양로지스',
				'query_url_template' => 'https://www.ilyanglogis.com/product/visa_01_result1.asp?hawb_no=%s',
			),

			// 천일택배
			'chunil'  => (object) array(
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
			'hanjin'  => (object) array(
				'slug'               => 'hanjin',
				'name'               => '한진택배',
				'query_url_template' => 'https://www.hanjin.co.kr/Delivery_html/inquiry/result_waybill.jsp?wbl_num=%s',
			),

			// 합동택배
			'hapdong'  => (object) array(
				'slug'               => 'hapdong',
				'name'               => '합동택배',
				'query_url_template' => 'http://www.hdexp.co.kr/parcel/order_result_t.asp?p_item=%s',
			),

//			// 현대택배
//			'hyeondae'  => (object) array(
//				'slug'               => 'hyeondae',
//				'name'               => '현대택배',
//				'query_url_template' => '',
//			),

			// CJ대한통운
			'cj-daehan' => (object) array(
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
//			// KG로지스
//			'kg-logis'  => (object) array(
//				'slug'               => 'kg-logis',
//				'name'               => 'KG로지스',
//				'query_url_template' => '',
//			),

			// KGB택배
			'kgb'  => (object) array(
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
	 * 입력한 슬러그에 해당하는 Tracking_Number_Agent 객체를 리턴.
	 *
	 * @param $slug
	 * @see \wksl\delivery_tracking\agents\Agent_Helper::default_delivery_agents()
	 *
	 * @return \wskl\delivery_tracking\agents\Tracking_Number_Agent|false
	 */
	public static function get_tracking_number_agent_by_slug( $slug ) {

		if( empty( $current_agent_slug ) || $current_agent_slug == 'not-available' ) {
			return FALSE;
		}

		if ( ! static::$agents or ! isset( static::$agents[ $slug ] ) ) {
			return FALSE;
		}

		$agent = static::$agents[ $slug ];
		if ( is_array( $agent ) ) {
			$agent = (object) $agent;
		}

		$instance = new Tracking_Number_Agent(
			$agent->slug,
			$agent->name,
			$agent->query_url_template
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


Agent_Helper::init();