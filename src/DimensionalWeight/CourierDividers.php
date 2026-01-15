<?php

declare(strict_types = 1);

namespace AldaVigdis\ConnectorForDK\DimensionalWeight;

enum CourierDividers: string {
	case IcelandPost  = 'iceland_post';
	case Dropp        = 'dropp';
	case Icetransport = 'icetransport';
	case UPS          = 'ups';

	public function divider(): int {
		return match ( $this ) {
			CourierDividers::IcelandPost, CourierDividers::Dropp => 3_000,
			CourierDividers::Icetransport => 5_000,
			CourierDividers::UPS => 6_000,
			default => self::IcelandPost
		};
	}

	public function full_name(): string {
		return match ( $this ) {
			CourierDividers::IcelandPost => 'Iceland Post',
			CourierDividers::Dropp => 'Dropp',
			CourierDividers::Icetransport => 'Icetransport (FedEx)',
			CourierDividers::UPS => 'Airport Associates (UPS)'
		};
	}
}
