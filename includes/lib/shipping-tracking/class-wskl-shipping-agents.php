<?php

if ( ! class_exists( 'WSKL_Shipping_Agents' ) ) :

	class WSKL_Shipping_Agents {

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

endif;
