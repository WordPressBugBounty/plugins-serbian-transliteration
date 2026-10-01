<?php

declare(strict_types=1);

define('WPINC', 'wp-includes');

function apply_filters(string $hook, $value)
{
	return $value;
}

function sanitize_key(string $key): string
{
	return strtolower(preg_replace('/[^a-z0-9_\-]/', '', $key));
}

function esc_attr(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function wp_rand(int $min = 0, int $max = 0): int
{
	return 123456;
}

trait Transliteration__Cache
{
	protected static array $test_cache = [];

	protected static function cached_static(string $key, callable $callback, $index = null)
	{
		$cache_key = $key . ':' . serialize($index);

		if (!array_key_exists($cache_key, self::$test_cache)) {
			self::$test_cache[$cache_key] = $callback();
		}

		return self::$test_cache[$cache_key];
	}
}

class Transliteration
{
}

class Transliteration_Utilities
{
	public static function can_transliterate($content): bool
	{
		return false;
	}

	public static function is_editor(): bool
	{
		return false;
	}

	public static function is_admin(): bool
	{
		return false;
	}
}

class Controller_Cyr_To_Lat_Test_Map
{
	public static function transliterate(string $content, string $direction): string
	{
		if ($direction !== 'cyr_to_lat') {
			return $content;
		}

		return strtr($content, [
			'Љ' => 'Lj', 'Њ' => 'Nj', 'Џ' => 'Dž', 'љ' => 'lj', 'њ' => 'nj', 'џ' => 'dž',
			'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Ђ' => 'Đ',
			'Е' => 'E', 'Ж' => 'Ž', 'З' => 'Z', 'И' => 'I', 'Ј' => 'J', 'К' => 'K',
			'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R',
			'С' => 'S', 'Т' => 'T', 'Ћ' => 'Ć', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H',
			'Ц' => 'C', 'Ч' => 'Č', 'Ш' => 'Š', 'а' => 'a', 'б' => 'b', 'в' => 'v',
			'г' => 'g', 'д' => 'd', 'ђ' => 'đ', 'е' => 'e', 'ж' => 'ž', 'з' => 'z',
			'и' => 'i', 'ј' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
			'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'ћ' => 'ć',
			'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'č', 'ш' => 'š',
		]);
	}
}

class Transliteration_Map
{
	public static function get(): self
	{
		return new self();
	}

	public function map(): string
	{
		return Controller_Cyr_To_Lat_Test_Map::class;
	}
}

class Transliteration_Sanitization
{
	public static function get(): self
	{
		return new self();
	}

	public function lat(string $content, bool $sanitize_html): string
	{
		return $content;
	}
}

require_once dirname(__DIR__) . '/classes/controller.php';

function assert_same(string $expected, string $actual, string $message): void
{
	if ($expected !== $actual) {
		throw new RuntimeException($message . "\nExpected: " . $expected . "\nActual: " . $actual);
	}
}

function assert_contains(string $needle, string $haystack, string $message): void
{
	if (strpos($haystack, $needle) === false) {
		throw new RuntimeException($message . "\nMissing: " . $needle . "\nOutput: " . $haystack);
	}
}

function assert_not_matches(string $pattern, string $actual, string $message): void
{
	if (preg_match($pattern, $actual) === 1) {
		throw new RuntimeException($message . "\nOutput: " . $actual);
	}
}

$input = <<<'HTML'
<!doctype html><html><head><link href="https://example.test/head.css"></head><body><script>var j = {}; j.src='https://cdn.example.test/app.js';</script><a href="https://example.test/путања?q=ћирилица">Видљив ћирилични текст</a><img src='https://example.test/слика.jpg' srcset="https://example.test/мала.jpg 1x, https://example.test/велика.jpg 2x"></body></html>
HTML;

$output = (new Transliteration_Controller(false))->cyr_to_lat($input, true, true);

assert_contains("j.src='https://cdn.example.test/app.js'", $output, 'Inline JavaScript URL assignment changed.');
assert_contains('href="https://example.test/путања?q=ћирилица"', $output, 'Body href changed.');
assert_contains("src='https://example.test/слика.jpg'", $output, 'Body src changed.');
assert_contains('srcset="https://example.test/мала.jpg 1x, https://example.test/велика.jpg 2x"', $output, 'Body srcset changed.');
assert_contains('Vidljiv ćirilični tekst', $output, 'Visible Cyrillic body text was not transliterated.');
assert_not_matches('/%%::\d+::\d+::\d+::%%/', $output, 'Output contains an unresolved placeholder.');
assert_not_matches('/\b(?:href|src|srcset)\s*=\s*(["\'])\1/i', $output, 'Output contains a newly emptied URL attribute.');

assert_same($input, str_replace('Vidljiv ćirilični tekst', 'Видљив ћирилични текст', $output), 'Unexpected output changes detected.');

echo "controller cyr_to_lat URL placeholder regression: PASS\n";
