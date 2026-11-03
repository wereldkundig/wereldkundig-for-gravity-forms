<?php

namespace Wereldkundig\GravityForms;

class Component {
	public function register_hooks(): void {
		add_action( 'gform_loaded', array( $this, 'load' ), 5 );
	}

	public function load(): void {
		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) { // @phpstan-ignore function.alreadyNarrowedType (Guards against a Gravity Forms without the feed framework.)
			return;
		}

		\GFForms::include_feed_addon_framework();
		\GFAddOn::register( Addon::class );

		if ( class_exists( 'GF_Fields' ) ) {
			\GF_Fields::register( new SubscribeField() );
			SubscribeField::register_editor();
		}
	}
}
