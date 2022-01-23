<?php
namespace Arad\BotBlocker;

use OutOfBoundsException;
use Countable;
use Iterator;
use ArrayAccess;
use Exception;

/**
 * @template T
 * @implements ArrayAccess<int,T>
 * @implements Iterator<int,T>
 */
class SortedList implements Countable, Iterator, ArrayAccess {
	/**
	 * @var T[]
	 */
	protected array $data = [];
	private int $position = 0;

	/**
	 * @param T $value
	 */
	public function add($value): void {
		if ($this->count() == 0) {
			$index = 0;
		} else {
			$index = $this->binarySearch($value);
			if ($index >= 0) {
				return;
			}
			$index = - ($index + 1);
		}
		array_splice($this->data, $index, 0, [$value]);
	}

	/**
	 * @return T
	 */
	public function delete(int $index) {
		$this->insureValidOffset($index);
		$data = $this->data[$index];
		array_splice($this->data, $index, 1);
		return $data;
	}

	/**
	 * @param T $value
	 */
	public function search($value): ?int {
		$index = $this->binarySearch($value);
		if ($index < 0) {
			return null;
		}
		return $index;
	}

	/**
	 * @param T $value
	 */
	public function has($value): bool {
		return $this->search($value) !== null;
	}


	public function offsetExists($offset): bool {
		return isset($this->data[$offset]);
	}

	/**
	 * @return T
	 */
	public function offsetGet($offset) {
		$this->insureValidOffset($offset);
		return $this->data[$offset];
	}
	public function offsetSet($offset, $value): void {
		throw new Exception("It's not gonna happen");
	}
	public function offsetUnset($offset): void {
		$this->insureValidOffset($offset);
		if (!is_int($offset)) {
			return;
		}
		$this->delete($offset);
	}

	public function count(): int {
		return count($this->data);
	}

	/**
	 * @return T
	 */
	public function current() {
		return $this->data[$this->position];
	}
	public function key(): int {
		return $this->position;
	}
	public function next(): void {
		++$this->position;
	}
	public function rewind(): void {
		$this->position = 0;
	}
	public function valid(): bool {
		return isset($this->data[$this->position]);
	}

	/**
	 * @param T $a
	 * @param T $b
	 */
	protected function compare($a, $b): int {
		if ($a == $b) {
			return 0;
		}
		if ($a > $b) {
			return 1;
		}
		return -1;
	}

	/**
	 * @param T $element
	 */
	protected function binarySearch($element): int {
		$count = $this->count();
		if ($count === 0) {
			return -1;
		}
		$low = 0;
		$high = $count - 1;
		
		while ($low <= $high) {

			$mid = intval(($low + $high) / 2);
			$compare = $this->compare($this->data[$mid], $element);
			if($compare === 0) {
				return $mid;
			}
	
			if ($compare > 0) {
				$high = $mid -1;
			}
			else {
				$low = $mid + 1;
			}
		}
		return -($low + 1);
	}

	/**
	 * @param mixed $offset
	 */
	protected function insureValidOffset($offset): void {
		if (!is_int($offset)) {
			throw new OutOfBoundsException();
		}
		if ($offset < 0 or $offset >= $this->count()) {
			throw new OutOfBoundsException();
		}
	}

	
}
