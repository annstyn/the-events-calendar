<?php
if ( ! class_exists( 'TribeEventsTicketObject' ) ) {
	/**
	 *    Generic object to hold information about a single ticket
	 */
	class TribeEventsTicketObject {

		const GLOBAL_STOCKS_META  = 'tribe_events_global_stocks';
		const GLOBAL_STOCK_ENABLE = 'tribe_events_global_stocks_enable';

		/**
		 * This value - an empty string - should be used to populate the stock
		 * property in situations where no limit has been placed on stock
		 * levels.
		 */
		const UNLIMITED_STOCK = '';

		/**
		 * Unique identifier
		 *
		 * @var
		 */
		public $ID;
		/**
		 * Name of the ticket
		 *
		 * @var string
		 */
		public $name;

		/**
		 * Free text with a description of the ticket
		 *
		 * @var string
		 */
		public $description;

		/**
		 * Price, without any sign. Just a float.
		 *
		 * @var float
		 */
		public $price;

		/**
		 * Link to the admin edit screen for this ticket in the provider system,
		 * or null if the provider doesn't have any way to edit the ticket.
		 *
		 * @var string
		 */
		public $admin_link;

		/**
		 * Link to the front end of this ticket, if the providers has single view
		 * for this ticket.
		 *
		 * @var string
		 */
		public $frontend_link;

		/**
		 * Class name of the provider handling this ticket
		 *
		 * @var
		 */
		public $provider_class;

		/**
		 * Amount of tickets of this kind in stock
		 *
		 * This property is used if the ticket is a ticket not affecting
		 * a global stock.
		 *
		 * @var mixed
		 */
		protected $stock;

		/**
		 * Global stock affected by the ticket sales.
		 *
		 * Will be `false` if the ticket does not affect any global stock,
		 * will be a string defining the ID of the global stock affected
		 * otherwise.
		 *
		 * @var bool/string
		 */
		public $global_stock_id = false;

		/**
		 * Amount of tickets of this kind sold
		 *
		 * @var int
		 */
		public $qty_sold;

		/**
		 * Number of tickets for which an order has been placed but not confirmed or "completed".
		 *
		 * @var int
		 */
		public $qty_pending = 0;

		/**
		 * When the ticket should be put on sale
		 *
		 * @var
		 */
		public $start_date;

		/**
		 * When the ticket should be stop being sold
		 *
		 * @var
		 */
		public $end_date;

		/**
		 * @param $property
		 * @param $value
		 *
		 * @throws Exception if accessing the `stock` property on a global
		 *                   ticket and the event associated with the ticket
		 *                   cannot be found.
		 */
		public function __set( $property, $value ) {
			$could_use_global_stock  = $property == 'stock' && is_string( $this->global_stock_id );
			$should_use_global_stock = $this->is_global_stock_enabled() && $could_use_global_stock;
			if ( $should_use_global_stock ) {
				$event                                   = TribeEventsTickets::find_matching_event( $this->ID );
				$global_stocks                           = $this->get_global_stocks( $event );
				$global_stocks[ $this->global_stock_id ] = $value;
				$event->{self::GLOBAL_STOCKS_META}       = $global_stocks;
			}
			if ( $property == 'global_stock_id' ) {
				if ( ! is_string( $value ) ) {
					throw new Exception( 'Global stock ID must be a string' );
				}
			}
			$this->$property = $value;
		}

		/**
		 * @param string $property
		 *
		 * @return mixed
		 * @throws Exception if accessing the `stock` property on a global
		 *                   ticket and the event associated with the ticket
		 *                   cannot be found.
		 */
		public function __get( $property ) {
			$could_use_global_stock  = $property == 'stock' && is_string( $this->global_stock_id );
			$should_use_global_stock = $this->is_global_stock_enabled() && $could_use_global_stock;
			if ( $should_use_global_stock ) {
				$event         = TribeEventsTickets::find_matching_event( $this->ID );
				$global_stocks = $this->get_global_stocks( $event );

				return $global_stocks[ $this->global_stock_id ];
			}

			return $this->$property;
		}

		/**
		 * Returns the global stocks array stored as an event meta.
		 *
		 * @param bool/WP_Post $event Either a `WP_Post` instance or `false`
		 *
		 * @return array The global stocks stored in the event meta.
		 *               The array will have the format `global_stock_id/value`:
		 *
		 *                  ['global_stock_1': 20, 'global_stock_2': 10]
		 *
		 * @throws Exception if the event object is false
		 */
		protected function get_global_stocks( $event ) {
			if ( ! $event ) {
				throw new Exception( 'There was a problem retrieving the event for the ticket' );
			}
			$stocks = $event->{self::GLOBAL_STOCKS_META};

			return $stocks;
		}

		/**
		 * Checks if the use of global stock has been enabled on a filter
		 * level.
		 *
		 * @return bool `true` if the filter return value evaluates to true, `false` otherwise.
		 */
		public function is_global_stock_enabled() {

			/**
			 * Enables or disables the use of global stock.
			 *
			 * Filter will return `null` disabling the function by
			 * default. To activate the function hook into the filter
			 * and return a value evaluating to boolean `true`.
			 *
			 * @since 1.5
			 *
			 * @param TribeEventsTicketObject $this The current ticket instance.
			 */
			$global_stock_enabled = apply_filters( self::GLOBAL_STOCK_ENABLE, $this );

			return $global_stock_enabled ? true : false;
		}

	}
}