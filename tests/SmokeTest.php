<?php
fbm_test( 'smoke_plugin_loaded', function () {
	fbm_assert_true( defined( 'FBM_VERSION' ), 'FBM_VERSION defined' );
} );
