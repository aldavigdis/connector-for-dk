<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\DimensionalWeight;

/**
 * The Dimensional Weight Courier Dividers enum
 *
 * Includes the numbers the square centimeter volume of a product should be
 * divided with to get the volumetric weight in kilos.
 *
 * @see AldaVigdis\ConnectorForDK\Calculator
 */
enum CourierDividers: string {
	case IcelandPost  = 'iceland_post';
	case Dropp        = 'dropp';
	case Icetransport = 'icetransport';
	case UPS          = 'ups';

	/**
	 * Get the divider for a courier
	 */
	public function divider(): int {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext
		return match ( $this ) {
			CourierDividers::IcelandPost, CourierDividers::Dropp => 3_000,
			CourierDividers::Icetransport => 5_000,
			CourierDividers::UPS => 6_000,
			default => self::IcelandPost
		};
	}

	/**
	 * Get the full name of a courier
	 */
	public function full_name(): string {
		// phpcs:ignore PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext
		return match ( $this ) {
			CourierDividers::IcelandPost => 'Iceland Post',
			CourierDividers::Dropp => 'Dropp',
			CourierDividers::Icetransport => 'Icetransport (FedEx)',
			CourierDividers::UPS => 'Airport Associates (UPS)'
		};
	}
}
