<?php declare(strict_types = 1);

namespace ProLib;

class QuestionMarkReplacer {

	/** @var string */
	private $string;

	/** @var int */
	private $length;

	/** @var int */
	private $pos = 0;

	/** @var bool */
	private $backSlashed = false;

	/** @var string */
	private $lastChar;

	/** @var int */
	private $paramPos = 0;

	/** @var string[] */
	private $params = [];

	/** @var int */
	private $paramsCount;

	/** @var callable */
	private $caster;

	public function __construct(string $string) {
		$this->string = $string;
		$this->length = strlen($string);
		$this->caster = [$this, 'defaultCaster'];
	}

	private function defaultCaster($param): string {
		return (string) $param;
	}

	public function setCaster(callable $caster): void {
		$this->caster = $caster;
	}

	public function process(array $params): string {
		if (!$params && strpos($this->string, '?') === false) {
			return $this->string;
		}

		$this->params = $params;
		$this->paramsCount = count($params);
		$missingParams = 0;

		while ($this->length > $this->pos) {
			$char = $this->string[$this->pos];

			if ($char === '?') {
				if (!$this->backSlashed) {
					if ($this->lastChar === '\\') {
						$this->string = substr($this->string, $this->pos - 1, 1);
					}

					$state = $this->replaceQuestionMark();
					if (!$state) {
						$missingParams++;
					}

					$char = null;
				} else if ($this->lastChar === '\\') {
					$this->substrReplace('?', 2, $this->pos - 1);
				}
			} else if ($char === '\\') {
				$this->backSlashed = !$this->backSlashed;
			} else {
				$this->backSlashed = false;
			}

			$this->lastChar = $char;
			$this->pos++;
		}

		if ($missingParams) {
			throw new InvalidArgumentsException(sprintf(
				'Found %d missing occurrences.', $missingParams
			));
		}

		return $this->string;
	}

	private function substrReplace(string $with, int $length, ?int $pos = null): void {
		$this->string = substr_replace($this->string, $with, $pos !== null ? $pos : $this->pos, $length);

		$plus = strlen($with) - $length;
		$this->pos += $plus;
		$this->length += $plus;
	}

	private function replaceQuestionMark(): bool {
		if ($this->paramsCount <= $this->paramPos) {
			return false;
		}

		$param = call_user_func($this->caster, $this->params[$this->paramPos++]);

		$this->substrReplace($param, 1);

		return true;
	}

	public static function replace(string $string, array $params, ?callable $caster = null): string {
		$class = new static($string);
		if ($caster) {
			$class->setCaster($caster);
		}

		return $class->process($params);
	}

}
