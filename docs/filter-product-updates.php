<?php

add_filter(
	'connector_for_dk_update_current_quantity',
	function (): int {
		return 16;
	},
	10,
	0
);
