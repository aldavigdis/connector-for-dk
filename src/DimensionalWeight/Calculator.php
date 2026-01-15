<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\DimensionalWeight;

use AldaVigdis\ConnectorForDK\Brick\Math\BigDecimal;
use AldaVigdis\ConnectorForDK\Brick\Math\RoundingMode;

class Calculator {
	const DEFAULT_COURIER    = 'iceland_post';
	const DIVIDER_MULTIPLIER = 1_000_000;

	/**
	 * Calculate volumetric weight drom dimension and a courier
	 *
	 * Please note that couriers calculate volumetric weight based on cubic cm,
	 * while DK uses cubic meters, so we use cubic meters as well.
	 *
	 * @param float|int|string $volume The colume in cubic meters.
	 * @param string           $courier_name The courier name in snake case, referencing the CourierDividers enum.
	 * @param float|int|string $actual_weight The actual weight in kg.
	 */
	public static function calculate(
		float|int|string $volume = 0.0,
		string $courier_name = self::DEFAULT_COURIER,
		float|int|string $actual_weight = 0.0
	): float {
		$courier = CourierDividers::from( $courier_name );

		$decimal_divider = BigDecimal::of(
			$courier->divider()
		)->dividedBy(
			self::DIVIDER_MULTIPLIER,
			12,
			RoundingMode::HALF_CEILING
		);

		$decimal_volume = BigDecimal::of( $volume );

		$dimensional_weight = $decimal_volume->dividedBy(
			$decimal_divider,
			3,
			RoundingMode::HALF_CEILING
		);

		if ( $dimensional_weight->toFloat() > (float) $actual_weight ) {
			return $dimensional_weight->toFloat();
		}

		return (float) $actual_weight;
	}
}
