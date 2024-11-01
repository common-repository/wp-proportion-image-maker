<?php
/**
 * Container for an array of options.
 */
class WpProportionOptions {

	/**
	 * The option name.
	 * @var string
	 */
	protected $key;

	/**
	 * The default values.
	 * @var array
	 */
	protected $defaults;

	/**
	 * Used by WP hooks.
	 * @var null
	 */
	public $wp_filter_id;

	/**
	 * Create a new set of options.
	 *
	 * @param string $key Option name.
	 * @param string $file Reference to main plugin file.
	 * @param array $defaults (optional) An associative array of default values.
	 *
	 * @return void
	 */
	public function __construct( $key, $file, $defaults = array() ) {
		$this->key = $key;
		$this->defaults = $defaults;
	}

	/**
	 * Returns option name.
	 *
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Get option values for one or all fields.
	 *
	 * @param string|array $field (optional) The field to get.
	 * @param mixed $default (optional) The value returned when the key is not found.
	 *
	 * @return mixed Whatever is in those fields.
	 */
	public function get( $field = null, $default = null ) {
		$data = array_merge( $this->defaults, get_option( $this->key, array() ) );

		return self::get_value( $field, $data, $default );
	}
        
       /**
	 * Traverses the formdata and retrieves the correct value.
	 *
	 * @param string $name     The name of the value
	 * @param array  $value    The data that will be traversed
	 * @param mixed  $fallback (optional) The value returned when the key is not found
	 *
	 * @return mixed
	 */
	public static function get_value( $name, $value, $fallback = null ) {
		foreach ( (array) $name as $key ) {
			if ( ! isset( $value[ $key ] ) ) {
				return $fallback;
			}

			$value = $value[ $key ];
		}

		return $value;
	}

	/**
	 * Get default values for one or all fields.
	 *
	 * @param string|array $field (optional) The field to get.
	 *
	 * @return mixed Whatever is in those fields.
	 */
	public function get_defaults( $field = null ) {
		return self::get_amount( $field, $this->defaults );
	}
        
        	/**
	 * Traverses the formdata and retrieves the correct value.
	 *
	 * @param string $name     The name of the value
	 * @param array  $value    The data that will be traversed
	 * @param mixed  $fallback (optional) The value returned when the key is not found
	 *
	 * @return mixed
	 */
	public static function get_amount( $name, $value, $fallback = null ) {
		foreach ( (array) $name as $key ) {
			if ( ! isset( $value[ $key ] ) ) {
				return $fallback;
			}

			$value = $value[ $key ];
		}

		return $value;
	}

	/**
	 * Set all data fields, certain fields or a single field.
	 *
	 * @param string|array $field The field to update or an associative array.
	 * @param mixed $value (optional) The new value ( ignored if $field is array ).
	 *
	 * @return void
	 */
	public function set( $field, $value = '' ) {
		if ( is_array( $field ) ) {
			$newdata = $field;
		} else {
			$newdata = array( $field => $value );
		}

		$this->update( array_merge( $this->get(), $newdata ) );
	}

	/**
	 * Reset option to defaults.
	 *
	 * @return void
	 */
	public function reset() {
		$this->update( $this->defaults, false );
	}

	/**
	 * Remove any keys that are not in the defaults array.
	 *
	 * @return void
	 */
	public function cleanup() {
		$this->update( $this->get(), true );
	}

	/**
	 * Update raw data.
	 *
	 * @param mixed $newdata
	 * @param bool $clean (optional) Whether to remove unrecognized keys or not.
	 *
	 * @return void
	 */
	public function update( $newdata, $clean = true ) {
		if ( $clean ) {
			$newdata = $this->_clean( $newdata );
		}

		update_option( $this->key, array_merge( $this->get(), $newdata ) );
	}

	/**
	 * Delete the option.
	 *
	 * @return void
	 */
	public function delete() {
		delete_option( $this->key );
	}


//_____INTERNAL METHODS_____


	/**
	 * Saves an extra query.
	 *
	 * @return void
	 */
	public function activation() {
		add_option( $this->key, $this->defaults );
	}

	/**
	 * Keep only the keys defined in $this->defaults
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function _clean( $data ) {
		return wp_array_slice_assoc( $data, array_keys( $this->defaults ) );
	}

	private function &_get( $field, $data ) {
	}

	/**
	 * Magic method: $options->field
	 *
	 * @param string|array $field The field to get.
	 *
	 * @return mixed
	 */
	public function __get( $field ) {
		return $this->get( $field );
	}

	/**
	 * Magic method: $options->field = $value
	 *
	 * @return void
	 */
	public function __set( $field, $value ) {
		$this->set( $field, $value );
	}

	/**
	 * Magic method: isset( $options->field )
	 *
	 * @return bool
	 */
	public function __isset( $field ) {
		$data = $this->get();
		return isset( $data[ $field ] );
	}
}

