<?php
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

class i8Html {
	/**
	 * @param mixed[] $args
	 */
	public function render(...$args): string {
		return implode("", array_map('strval', \i8Helpers::array_flatten($args)));
	}

	/**
	 * @param string $tag
	 * @param array<string, mixed> $attributes
	 * @return string
	 */
	public function tag(string $tag, array $attributes): string {
		$escapedAttributes = "";
		foreach ($attributes as $name => $value) {
			$value = htmlspecialchars(strval($value), ENT_QUOTES);
			$escapedAttributes .= " {$name}=\"{$value}\"";
		}

		return "<{$tag}{$escapedAttributes}>";
	}

	/**
	 * @param string $tag
	 * @param array<string, mixed> $attributes
	 * @param mixed[] $children
	 * @return string
	 */
	public function tagHasChildren(string $tag, array $attributes, ...$children): string {
		$escapedAttributes = "";
		foreach ($attributes as $name => $value) {
			$value = htmlspecialchars(strval($value), ENT_QUOTES);
			$escapedAttributes .= " {$name}=\"{$value}\"";
		}

		$children = $this->render(...$children);
		return "<{$tag}{$escapedAttributes}>{$children}</{$tag}>";
	}
	
	/**
	 * @param mixed[] $head
	 * @param mixed[] $body
	 * @param array<string, mixed> $htmlAttributes
	 * @param array<string, mixed> $bodyAttributes
	 */
	public function renderDocument(
		array $head,
		array $body,
		array $htmlAttributes = [],
		array $bodyAttributes = []
	): string {
		return $this->render(
			"<!DOCTYPE html>",
			$this->tagHasChildren('html', $htmlAttributes,
				$this->tagHasChildren('head', [], $head),
				$this->tagHasChildren('body', $bodyAttributes, $body),
			),
		);
	}

	/**
	 * @param mixed[] $head
	 * @param mixed[] $body
	 */
	public function renderDefault(array $head, array $body): string {
		$head = array_merge([
			$this->tag('meta', ['charset' => 'utf-8']),
			$this->tag('meta', ['name' => 'viewport', 'content' => 'initial-scale=1, width=device-width']),
			$this->tag('link', ['rel' => 'stylesheet', 'href' => '/styles.css']),
		], $head);

		return $this->renderDocument($head, $body);
	}
}
