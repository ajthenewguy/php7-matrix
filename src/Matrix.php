<?php
declare(strict_types=1);

namespace Matrix;

use Ds\Vector;
use Ds\Traits\GenericCollection;

/**
 * Class Matrix
 * 
 * @package Matrix
 * 
 * @phpstan-implements \IteratorAggregate<Vector>
 * 
 * @property-read int $x
 * @property-read int $y
 */
class Matrix implements \IteratorAggregate, \JsonSerializable
{
    use GenericCollection;

    public const NONE = 1;

    public const SQUARE = 2;

    public const SAME = 4;

    public const REFLECT = 8;

    public const INVERTIBLE = 16;

    /**
     * Method aliases. Format:
     * this => calls this
     *
     * @var array<string>
     */
    private static $aliases = [
        'getAdjoint' => 'getAdjugate',
        'invert'     => 'inverse',
        'inversed'   => 'getInverse',
        'inverted'   => 'getInverse',
        'transposed' => 'getTranspose',
        'pow'        => 'exponential'
    ];

    /**
     * Cache for matrix calculations
     * 
     * @var array<mixed>
     */
    private $cache;

    /**
     * Width: number of columns
     *
     * @var int
     */
    private $x;

    /**
     * Height: number of rows
     *
     * @var int
     */
    private $y;

    /**
     * Matrix inner data
     * 
     * @var Vector<Vector<mixed>>
     */
    protected $table;


    /**
     * @param iterable<iterable<mixed>> $table
     */
    public function __construct(iterable $table = [])
    {
        $this->setData($table);
    }


    /******************************************************
     * Static methods
     */


    /**
     * Create and return a filled matrix
     *
     * @param integer $width
     * @param integer|null $height
     * @param mixed $fill
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public static function create(int $width,  ?int $height = null, $fill = 0): Matrix
    {
        if (is_null($height)) {
            $height = $width;
        }
        $rows = new Vector();
        $rows->allocate($height);
        $y = 0;
        while ($y < $height) {
            $row = new Vector();
            $row->allocate($width);
            $x = 0;
            while ($x < $width) {
                $row->push($fill);
                $x++;
            }
            $rows->push($row);
            $y++;
        }
        return new self($rows);
    }

    /**
     * Create and return an identity Matrix.
     *
     * @param integer $size
     * @return Matrix<Vector<Vector<integer>>>
     */
    public static function identity(int $size): Matrix
    {
        $rows = new Vector();
        $y = 0;
        while ($y < $size) {
            $row = new Vector();
            $row->allocate($size);
            $x = 0;
            while ($x < $size) {
                $row->push(\intval($x === $y));
                $x++;
            }
            $rows->push($row);
            $y++;
        }
        return new self($rows);
    }

    public static function random(int $size): Matrix
    {
        $rows = new Vector();
        $y = 0;
        while ($y < $size) {
            $row = new Vector();
            $row->allocate($size);
            $x = 0;
            while ($x < $size) {
                $row->push(rand(0, 9) * (rand(0, 1) ? 1 : -1));
                $x++;
            }
            $rows->push($row);
            $y++;
        }
        return new self($rows);
    }


    /******************************************************
     * Instance methods
     */

    
     /**
      * Reset the cache.
      * 
      * @return Matrix
      */
    private function initCache(): Matrix
    {
        $this->cache = [];
        return $this;
    }


    /**
     * Get the determinant of the matrix
     *
     * @return float|integer
     */
    public function determinant()
    {
        $this->validateDimensions($this, self::SQUARE);

        if (!array_key_exists(__METHOD__, $this->cache)) {
            switch ($this->y) {
                case 1:
                    $this->cache[__METHOD__] = $this->get(0, 0);
                break;
                case 2:
                    $this->cache[__METHOD__] = $this->get(0, 0) * $this->get(1, 1) - $this->get(0, 1) * $this->get(1, 0);
                break;
                default:
                    $determinant = 0;
                    foreach ($this->getRow(0) as $x => $cell) {
                        $minor_determinant = $cell * $this->getMinors($x)->determinant();
                        if (($x % 2) === 0) {
                            $determinant += $minor_determinant;
                        } else {
                            $determinant -= $minor_determinant;
                        }
                    }
                    $this->cache[__METHOD__] = $determinant;
                break;
            }
        }

        return $this->cache[__METHOD__];
    }

    /**
     * Get a Vector of the anti-diagonal components.
     * 
     * @return Vector<mixed>
     */
    public function getAntidiagonal(): Vector
    {
        $row = new Vector();
        $row->allocate((int) ceil(($this->x + $this->y) / 2));
        $size = max($this->x, $this->y);
        $o = 0;
        while ($o < $size) {
            $row->push($this->get($this->x - 1 - $o, $o));
            $o++;
        }
        return $row;
    }

    /**
     * Returns a Vector representing the column at the given offset.
     *
     * @param integer $offset
     * @return Vector<mixed>
     */
    public function getColumn(int $offset): Vector
    {
        assert($offset < $this->x, new \OutOfBoundsException());

        $column = new Vector();
        $column->allocate($this->y);

        foreach ($this->table as $row) {
            $column->push($row->get($offset));
        }

        return $column;
    }

    /**
     * Returns a Vector representing the row at the given offset.
     *
     * @param integer $offset
     * @return Vector<mixed>
     */
    public function getRow(int $offset): Vector
    {
        assert($offset < $this->y, new \OutOfBoundsException());

        return $this->table->get($offset);
    }

    /**
     * Get the inner Vector that holds the matrix data.
     * 
     * @return Vector<Vector<mixed>>
     */
    public function getData(): Vector
    {
        return $this->table;
    }

    /**
     * Get the ajugate/adjoint of the matrix
     * @aliases['getAdjoint']
     *
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function getAdjugate(): Matrix
    {
        return $this->getCofactors()->transpose();
    }

    /**
     * Return new Matrix of the cofactors of all components.
     *
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function getCofactors(): Matrix
    {
        $this->validateDimensions($this, self::SQUARE);

        return $this->map(function ($cell, $x, $y) {
            return pow(-1, $x + $y) * $this->getMinors($x, $y)->determinant();
        });
    }

    /**
     * Get a Vector of the diagonal components.
     * 
     * @return Vector<mixed>
     */
    public function getDiagonal(): Vector
    {
        $row = new Vector();
        $row->allocate((int) ceil(($this->x + $this->y) / 2));
        $size = max($this->x, $this->y);
        $o = 0;
        while ($o < $size) {
            $row->push($this->get($o, $o));
            $o++;
        }
        return $row;
    }

    /**
     * Return new Matrix of this Matrix's inverse
     *
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function getInverse(): Matrix
    {
        $matrix = new self(clone $this->table);
        $matrix->inverse();
        return $matrix;
    }

    /**
     * Get a Matrix of the minors of this Matrix for the given column/row.
     *
     * @param integer $column_x
     * @param integer $row_y
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function getMinors(int $column_x, int $row_y = 0): Matrix
    {
        $rows = new Vector();
        $rows->allocate($this->y);
        foreach ($this->table as $y => $colVector) {
            if ($y === $row_y) continue;
            $row = new Vector();
            $row->allocate($this->x);
            foreach ($colVector as $x => $value) {
                if ($x === $column_x) continue;
                $row->push($value);
            }
            $rows->push($row);
        }

        return new self($rows);
    }

    /**
     * Get a Matrix of the negative of this Matrix.
     * 
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function getNegative(): Matrix
    {
        return $this->map(function ($cell) {
            return $cell * -1;
        });
    }

    /**
     * Get a Matrix with the components of this Matrix flipped along the diagonal.
     * 
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function getTranspose(): Matrix
    {
        $matrix = new self(clone $this);
        $matrix->transpose();
        return $matrix;
    }

    /**
     * Update this Matrix to its inverse.
     * Mutative.
     * 
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function inverse(): Matrix
    {
        $this->validateDimensions($this, self::SQUARE | self::INVERTIBLE);

        if ($this->y === 1) {
            return new self([[1 / $this->get(0, 0)]]);
        }

        $adjugate = $this->getAdjugate();
        $this->setData($adjugate->divide($this->determinant()));

        return $this;
    }

    /**
     * Check if the matrix is a diagonal matrix, a matrix in which the entries outside the main diagonal are all zero.
     * 
     * @return bool
     */
    public function isDiagonal(): bool
    {
        return $this->isLowerTriangular() && $this->isUpperTriangular();
    }

    /**
     * Check if the matrix is triangular, a matrix that is either upper or lower triangular.
     * 
     * @return bool
     */
    public function isTriangular(): bool
    {
        return $this->isLowerTriangular() || $this->isUpperTriangular();
    }

    /**
     * Check if all the entries above the main diagonal are zero.
     * 
     * @return bool
     */
    public function isLowerTriangular(): bool
    {
        if (!$this->isSquare()) {
            return false;
        }
        if (!array_key_exists(__METHOD__, $this->cache)) {
            $this->cache[__METHOD__] = true;
            foreach ($this->table as $y => $row) {
                foreach ($row->slice($y + 1) as $cell) {
                    if ($cell != 0) {
                        $this->cache[__METHOD__] = false;
                        break 2;
                    }
                }
            }
        }
        return $this->cache[__METHOD__];
    }

    /**
     * Check if all the entries below the main diagonal are zero.
     * 
     * @return bool
     */
    public function isUpperTriangular(): bool
    {
        if (!$this->isSquare()) {
            return false;
        }
        if (!array_key_exists(__METHOD__, $this->cache)) {
            $this->cache[__METHOD__] = true;
            foreach ($this->table->slice(1) as $y => $row) {
                foreach ($row->slice(0, ($y + 1)) as $cell) {
                    if ($cell != 0) {
                        $this->cache[__METHOD__] = false;
                        break 2;
                    }
                }
            }
        }
        return $this->cache[__METHOD__];
    }

    /**
     * Check if this Matrix is invertible.
     * 
     * @return bool
     */
    public function isInvertible(): bool
    {
        if (!array_key_exists(__METHOD__, $this->cache)) {
            $this->cache[__METHOD__] = $this->determinant() != 0.0;
        }
        return $this->cache[__METHOD__];
    }

    /**
     * Check if this Matrix is square.
     * 
     * @return bool
     */
    public function isSquare(): bool
    {
        return $this->x === $this->y;
    }

    /**
     * Check if this Matrix is symmetric.
     * 
     * @return bool
     */
    public function isSymmetric(): bool
    {
        if (!array_key_exists(__METHOD__, $this->cache)) {
            $this->cache[__METHOD__] = false;
            if ($this->isSquare()) {
                $this->cache[__METHOD__] = $this->equals($this->getTranspose());
            }
        }
        return $this->cache[__METHOD__];
    }

    /**
     * Check if this Matrix is skew-symmetric.
     * 
     * @return bool
     */
    public function isSkewSymmetric(): bool
    {
        if (!array_key_exists(__METHOD__, $this->cache)) {
            $this->cache[__METHOD__] = false;
            if ($this->isSquare()) {
                $this->cache[__METHOD__] = $this->getNegative()->equals($this->getTranspose());
            }
        }
        return $this->cache[__METHOD__];
    }

    /**
     * Get the sum of the diagonal
     *
     * @return int|float
     */
    public function trace()
    {
        $this->validateDimensions($this, self::SQUARE);

        return $this->getDiagonal()->sum();
    }

    /**
     * Check if this Matrix equals the supplied Matrix.
     * 
     * @param Matrix<Vector<Vector<mixed>>> $m
     * @return bool
     */
    public function equals(Matrix $m): bool
    {
        if ($this->x === $m->x && $this->y === $m->y) {
            foreach ($this->table as $y => $row) {
                foreach ($row as $x => $cell) {
                    if ($cell !== $m->get($x, $y)) {
                        return false;
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Add a Matrix or numeric value to this instance.
     * Mutative.
     * 
     * @param Matrix<Vector<Vector<mixed>>>|float|integer $input
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function add($input): Matrix
    {
        if (is_numeric($input)) {
            return $this->apply(function ($cell) use ($input) {
                return $cell + $input;
            });
        }

        return $this->apply(function ($currentValue, $x, $y) use ($input) {
            return $currentValue + $input->get($x, $y);
        });
    }

    /**
     * Return a new Matrix with a Matrix or value added to this instance.
     * 
     * @param Matrix<Vector<Vector<mixed>>>|float|integer $input
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function added($input): Matrix
    {
        if (is_numeric($input)) {
            return $this->map(function ($cell) use ($input) {
                return $cell + $input;
            });
        }

        return $this->map(function ($currentValue, $x, $y) use ($input) {
            return $currentValue + $input->get($x, $y);
        });
    }

    /**
     * Subtract a Matrix or value to this instance.
     * Mutative.
     * 
     * @param Matrix<Vector<Vector<mixed>>>|float|integer $input
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function subtract($input): Matrix
    {
        if (is_numeric($input)) {
            return $this->apply(function ($cell) use ($input) {
                return $cell - $input;
            });
        }

        return $this->apply(function ($currentValue, $x, $y) use ($input) {
            return $currentValue - $input->get($x, $y);
        });
    }

    /**
     * Return a new Matrix with a Matrix or value subtracted from this instance.
     * 
     * @param Matrix<Vector<Vector<mixed>>>|float|integer $input
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function subtracted($input): Matrix
    {
        if (is_numeric($input)) {
            return $this->map(function ($cell) use ($input) {
                return $cell - $input;
            });
        }

        return $this->map(function ($currentValue, $x, $y) use ($input) {
            return $currentValue - $input->get($x, $y);
        });
    }

    /**
     * Multiply a Matrix or value to this instance.
     * Mutative.
     * 
     * @param Matrix<Vector<Vector<mixed>>>|float|integer $input
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function multiply($input): Matrix
    {
        if (is_numeric($input)) {
            return $this->apply(function ($cell) use ($input) {
                return $cell * $input;
            });
        }

        return $this->applyMatrix($input, function ($currentValue, $theirValue) {
            return $currentValue * $theirValue;
        }, self::REFLECT);
    }

    /**
     * Return a new Matrix with a Matrix or value multiplied from this instance.
     * 
     * @param Matrix<Vector<Vector<mixed>>>|float|integer $input
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function multiplied($input): Matrix
    {
        if (is_numeric($input)) {
            return $this->map(function ($cell) use ($input) {
                return $cell * $input;
            });
        }

        return $this->mapMatrix($input, function ($currentValue, $theirValue) {
            return $currentValue * $theirValue;
        }, self::REFLECT);
    }

    /**
     * Divide this Matrix by a Matrix or numeric value.
     * Mutative.
     * 
     * @param Matrix<Vector<Vector<mixed>>>|float|integer $input
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function divide($input): Matrix
    {
        if (is_numeric($input)) {
            assert($input != 0.0, new \DivisionByZeroError());
            
            return $this->apply(function ($cell) use ($input) {
                return $cell / $input;
            });
        }

        return $this->setData($this->divided($input));
    }

    /**
     * Return a new Matrix of this Matrix divided by a Matrix or numeric value.
     * 
     * @param Matrix<Vector<Vector<mixed>>>|float|integer $input
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function divided($input): Matrix
    {
        if (is_numeric($input)) {
            assert($input != 0.0, new \DivisionByZeroError());
            
            return $this->map(function ($cell) use ($input) {
                return $cell / $input;
            });
        }

        assert($this->isInvertible(),
            new \LogicException('matrix must not be singular')
        );

        return $this->multiplied($input->inverse());
    }

    /**
     * Exponentiate this Matrix by a numeric value.
     * Mutative.
     * 
     * @param Matrix<Vector<Vector<mixed>>>|float|integer $input
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function exponential($input): Matrix
    {
        return $this->setData($this->exponentiated($input));
    }

    /**
     * Returns a Matrix exponentiated by a numeric value.
     * 
     * @param Matrix<Vector<Vector<mixed>>>|float|integer $input
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function exponentiated($input): Matrix
    {
        assert(is_numeric($input), new \InvalidArgumentException('power must be numeric'));

        $clone = clone $this;
        $matrix = clone $this;
        $i = 1;
        while ($i < $input) {
            $matrix->multiply($clone);
            $i++;
        }

        return $matrix;
    }

    /**
     * Flip the Matrix along the diagonal.
     * Mutative.
     * 
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function transpose(): Matrix
    {
        $matrix = static::create($this->y, $this->x);
        foreach ($this->table as $y => $row) {
            foreach ($row as $x => $cell) {
                $matrix->set($y, $x, $cell);
            }
        }

        $this->setData($matrix);

        return $this;
    }

    /**
     * Apply a callback to each component of the Matrix.
     * Mutative.
     * 
     * @param callable $callback
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function apply(callable $callback): Matrix
    {
        foreach ($this->table as $y => $row) {
            foreach ($row as $x => $cell) {
                $this->set($x, $y, $callback($cell, $x, $y));
            }
        }

        return $this;
    }

    /**
     * Return a Matrix with a callback applied to each component of this Matrix.
     * 
     * @param callable $callback
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function map(callable $callback): Matrix
    {
        $rows = new Vector();
        $rows->allocate($this->y);
        foreach ($this->table as $y => $row) {
            $row_Vector = new Vector();
            $row_Vector->allocate($this->x);
            foreach ($row as $x => $cell) {
                $row_Vector->push($callback($cell, $x, $y));
            }
            $rows->push($row_Vector);
        }

        return new self($rows);
    }

    /**
     * Apply a callback to each component of the Matrix, passing in a cooresponding
     * value from a supplied Matrix.
     * Mutative.
     * 
     * @param Matrix<Vector<Vector<mixed>>> $matrix
     * @param callable $callback
     * @param integer $validation
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function applyMatrix(Matrix $matrix, callable $callback, int $validation = self::SAME): Matrix
    {
        return $this->setData($this->mapMatrix($matrix, $callback, $validation));
    }

    /**
     * Return a Matrix with a callback applied to each component of this Matrix, passing 
     * in a cooresponding value from a supplied Matrix.
     * 
     * @param Matrix<Vector<Vector<mixed>>> $matrix
     * @param callable $callback
     * @param integer $validation
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function mapMatrix(Matrix $matrix, callable $callback, int $validation = self::SAME): Matrix
    {
        $this->validateDimensions($matrix, $validation);

        assert($this->x === $matrix->y,
            new \LogicException('column count must match row count in mapped matrix')
        );

        $newMatrix = Matrix::create($matrix->x, $this->y);
        $z = 0;
        while ($z < $matrix->x) {
            foreach ($this->table as $y => $this_row) {
                foreach ($this_row as $x => $value1) {
                    $newMatrix->set($z, $y,
                        $newMatrix->get($z, $y) + $callback($value1, $matrix->get($z, $x), $x, $y, $z)
                    );
                }
            }
            $z++;
        }

        return $newMatrix;
    }

    /**
     * Get the value at the given coordinates/offsets.
     *
     * @param integer $x
     * @param integer $y
     * @return mixed
     */
    public function get(int $x, int $y)
    {
        assert($x < $this->x,
            new \OutOfRangeException(sprintf('attempted to access x index: %d on matrix with %d columns', $x, $this->x))
        );
        assert($y < $this->y,
            new \OutOfRangeException(sprintf('attempted to access y index: %d on matrix with %d rows', $y, $this->y))
        );

        return $this->table->get($y)->get($x);
    }

    /**
     * Set the value at the given coordinates/offsets.
     * Mutative.
     *
     * @param integer $x
     * @param integer $y
     * @param mixed $value
     * @return void
     */
    public function set(int $x, int $y, $value): void
    {
        $this->initCache();
        $this->table->get($y)->set($x, $value);
    }

    /**
     * Set the inner data Vector.
     * Mutative.
     * 
     * @param iterable<iterable<mixed>> $table
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function setData(iterable $table = []): Matrix
    {
        $this->initCache();

        if ($table instanceof Matrix) {
            $this->x = $table->x;
            $this->y = $table->y;
            $this->table = $table->getData();

            return $this;
        }
        
        $this->x = $x = 0;
        $this->y = $y = 0;
        $this->table = new Vector();

        foreach ($table as $row_y => $col) {
            if (!($col instanceof Vector)) {
                $col = new Vector($col);
            }
            if ($x === 0) {
                $x = $col->count();
            } elseif ($col->count() !== $x) {
                throw new \InvalidArgumentException(sprintf('row %d has %d columns but %d was expected', $row_y, $col->count(), $x));
            }
            $this->table->push($col);
            $y++;
        }

        $this->x = $x;
        $this->y = $y;

        return $this;
    }

    /**
     * Return an array representation of the Matrix data.
     * 
     * @return array<array<mixed>>
     */
    public function toArray(): array
    {
        $data = [];
        foreach ($this->table as $y => $Vector) {
           $data[$y] = $Vector->toArray();
        }
        return $data;
    }

    /**
     * @param Matrix<Vector<Vector<mixed>>> $matrix
     * @param integer $flags
     */
    private function validateDimensions(Matrix $matrix, int $flags = 0): void
    {
        if ($flags & self::SQUARE) {
            assert($matrix->isSquare(),
                new \LogicException('matrix must be square')
            );
        }
        if ($flags & self::SAME) {
            assert($this->x === $matrix->x && $this->y === $matrix->y,
                new \LogicException('matrices must have the same dimensions')
            );
        }
        if ($flags & self::REFLECT) {
            assert($this->x === $matrix->y && $this->y === $matrix->x,
                new \LogicException('matrix dimension mismatch: column count must match row count')
            );
        }
        if ($flags & self::INVERTIBLE) {
            assert($matrix->isInvertible(),
                new \LogicException('matrix must have a non-zero determinant (invertible)')
            );
        }
    }

    /**
     * Get iterator.
     * 
     * @return \Generator<Vector<mixed>>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->table as $value) {
            yield $value;
        }
    }

    /**
     * Return a JSON representation of the Matrix data.
     * 
     * @return Vector<Vector<mixed>>
     */
    public function jsonSerialize(): Vector
    {
        return $this->table;
    }

    /**
     * @param string $name
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (array_key_exists($name, static::$aliases)) {
            $callable = [$this, static::$aliases[$name]];
            if (is_callable($callable)) {
                return call_user_func_array($callable, $arguments);
            }
        }
        throw new \InvalidArgumentException('method not found');
    }

    /**
     * Ensures that the internal table will be cloned too.
     * Mutative.
     */
    public function __clone()
    {
        $this->table = clone $this->table;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $out = '';
        $column_widths = [];
        foreach ($this->table as $cols) {
            foreach ($cols as $x => $value) {
                if (!is_scalar($value)) {
                    $value = var_export($value, true);
                    $value = str_replace("\n", '', $value);
                }
                $width = strlen(''.$value);
                if (!isset($column_widths[$x]) || $column_widths[$x] < $width) {
                    $column_widths[$x] = $width;
                }
            }
        }

        foreach ($this->table as $cols) {
            $out .= '[';
            foreach ($cols as $x => $value) {
                if (!is_scalar($value)) {
                    $value = var_export($value, true);
                    $value = str_replace("\n", '', $value);
                }
                $out .= str_pad(''.$value, $column_widths[$x], ' ', STR_PAD_LEFT);
                $out .= ', ';
            }
            $out = rtrim($out, ' ,');
            $out .= ']'.\PHP_EOL;
        }

        return $out;
    }
}