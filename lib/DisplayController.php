<?php
declare(strict_types=1);
namespace Namegen;

use Namegen\CurlHelpers;
use ix\Controller\Controller;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpInternalServerErrorException;
use ix\Helpers\ArrayHelpers;

class DisplayController extends Controller {
	/**
	 * @param Request $request The Request object
	 * @param Response $response The Response object
	 * @param mixed[] $args Arguments passed from the router (if any)
	 * @return Response The resulting Response object
	 */
	public function requestGET(Request $request, Response $response, ?array $args = []): Response {
		$html = $this->container->get('html');
		$query_values = (array) $request->getQueryParams();

		$name_query = 'enby';
		if (array_key_exists('q', $query_values))
			$name_query = $query_values['q'];

		$name_params = http_build_query(['q' => $name_query ?? 'enby']);
		$name_url = "https://nominare.cogitare.nz/search?{$name_params}";
		$name_data = CurlHelpers::fetchUrl($name_url, [], true);

		$names = [];
		foreach ($name_data as $name) {
			$name_first = implode(" ", ArrayHelpers::array_flatten([$name['first']]));
			$name_last = implode("-", ArrayHelpers::array_flatten([$name['last']]));

			$names[] = [$name_first, $name_last];
		}

		$names_html = [];
		foreach ($names as $name) {
			list($name_first, $name_last) = $name;
			$names_html[] = $html->tagHasChildren('div', ['class' => 'name'], ...[
				$html->tagHasChildren('span', ['class' => 'name-first'], $name_first),
				$html->tagHasChildren('span', [], '  &middot  '),
				$html->tagHasChildren('span', ['class' => 'name-last'], $name_last),
			]);
		}

		$response->getBody()->write($html->renderDocument(
			[
				$html->tag('meta', ['charset' => 'utf-8']),
				$html->tag('meta', ['name' => 'viewport', 'content' => 'initial-scale=1, width=device-width']),
				$html->tag('link', ['rel' => 'stylesheet', 'href' => '/styles.css']),
				$html->tagHasChildren('title', [], 'namegen'),
			],
			[
				$html->tagHasChildren('main', [], ...[
					/* Main text */
					$html->tagHasChildren('h1', [], 'namegen'),
					$html->tagHasChildren('p', [], ...[
						"Name data sourced from Nominare, ",
						$html->tagHasChildren('a', ['href' => 'https://nominare.cogitare.nz/stats'], ...[
							"see here for stats on the available search terms",
						]),
					]),

					/* Form */
					$html->tagHasChildren('form', ['class' => 'form', 'method' => 'GET'], ...[
						$html->tag('input', ['name' => 'q', 'placeholder' => 'Query', 'value' => $name_query]),
						$html->tagHasChildren('button', ['type' => 'submit'], 'Get names'),
					]),

					
					$html->tagHasChildren('section', ['class' => 'names'], $names_html),
				]),
			],
		));

		return $response;
	}
}
