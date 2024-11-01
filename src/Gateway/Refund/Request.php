<?php

namespace Yoco\Gateway\Refund;

use WC_Order;
use Yoco\Gateway\Metadata;
use Yoco\Helpers\Http\Client;
use Yoco\Installation\Installation;
use Yoco\Helpers\MoneyFormatter as Money;

use function Yoco\yoco;

class Request {

	private ?WC_Order $order = null;

	private ?Installation $installation = null;

	public function __construct( WC_Order $order ) {
		$this->order        = $order;
		$this->installation = yoco( Installation::class );
	}

	public function send( ?float $amount ): array {
		try {
			$client = new Client();

			$url  = $this->getUrl();
			$args = $this->getArgs( $amount );

			return $client->post( $url, $args );
		} catch ( \Throwable $th ) {
			throw $th;
		}
	}

	public function getCheckoutId(): string {
		return yoco( Metadata::class )->getOrderCheckoutId( $this->order );
	}

	private function getUrl(): string {
		$url = $this->installation->getCheckoutApiUrl();

		return trailingslashit( $url ) . $this->getCheckoutId() . '/refund';
	}

	private function getArgs( ?float $amount = null ): array {

		$args = array(
			'headers' => $this->getHeaders(),
		);

		if ( null !== $amount && 0 < $amount ) {
			$args['body'] = wp_json_encode( array( 'amount' => yoco( Money::class )->format( $amount ) ) );
		}

		return $args;
	}

	private function getHeaders() {
		$headers = array(
			'Content-Type'  => 'application/json',
			'Authorization' => $this->installation->getApiBearer(),
			'X-Product'     => 'woocommerce',
		);

		return apply_filters( 'yoco_payment_gateway/refund/request/headers', $headers );
	}
}
