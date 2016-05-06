<?php


class WSKL_Submodules {

	private $modules = array();

	public function add_submodule( $name, $instance ) {

		if ( ! isset( $this->modules[ $name ] ) ) {
			$this->modules[ $name ] = $instance;
		} else {
			throw new \LogicException( "module $name is already set." );
		}

		return $instance;
	}

	public function has_module( $name ) {

		return isset( $this->modules[ $name ] );
	}

	public function get_submodule( $name ) {

		if ( ! isset( $this->modules[ $name ] ) ) {
			return NULL;
		}

		return $this->modules[ $name ];
	}
}