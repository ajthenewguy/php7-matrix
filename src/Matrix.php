<?php
declare(strict_types=1);

namespace Matrix;

use Ds\{Vector, Map};
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


    public const SQUARE = 0;

    public const SAME = 1;

    public const REFLECT = 2;

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
        $x = 0;
        $rows = new Vector();
        for ($y = 0; $y < $height; $y++) {
            $row = new Vector();
            for ($x = 0; $x < $width; $x++) {
                $row->push($fill);
            }
            $rows->push($row);
        }
        return new Matrix($rows);
    }

    /**
     * Get the cofactors of the supplied Matrix.
     * 
     * @param Matrix<Vector<Vector<mixed>>> $matrix
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public static function cofactors(Matrix $matrix): Matrix
    {
        return $matrix->getCofactors();
    }

    /**
     * Create and return an identity Matrix.
     *
     * @param integer $size
     * @return Matrix<Vector<Vector<integer>>>
     */
    public static function identity(int $size): Matrix
    {
        $x = 0;
        $rows = new Vector();
        for ($y = 0; $y < $size; $y++) {
            $row = new Vector();
            for ($x = 0; $x < $size; $x++) {
                $row->push(\intval($x === $y));
            }
            $rows->push($row);
        }
        return new Matrix($rows);
    }

    /**
     * Get a Matrix of the minors of the supplied Matrix for the given column/row.
     *
     * @param Matrix<Vector<Vector<mixed>>> $matrix
     * @param integer $column_x
     * @param integer $row_y
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public static function minors(Matrix $matrix, int $column_x, int $row_y = 0): Matrix
    {
        return $matrix->getMinors($column_x, $row_y);
    }


    /******************************************************
     * Instance methods
     */


    /**
     * Get the determinant of the matrix
     *
     * @return float|integer
     */
    public function determinant()
    {
        assert($this->isSquare(),
            new \LogicException('determinant is only defined for a square matrix')
        );

        if ($this->x === 1) {
            return $this->get(0, 0);
        } elseif ($this->x === 2) {
            return $this->get(0, 0) * $this->get(1, 1) - $this->get(0, 1) * $this->get(1, 0);
        }
        $determinant = 0;
        foreach ($this->getRow(0) as $x => $cell) {
            $minor_determinant = $cell * $this->getMinors($x)->determinant();
            if (($x % 2) === 0) {
                $determinant += $minor_determinant;
            } else {
                $determinant -= $minor_determinant;
            }
        }

        return $determinant;
    }

    /**
     * Get a Vector of the anti-diagonal components.
     * 
     * @return Vector<mixed>
     */
    public function getAntidiagonal(): Vector
    {
        $Vector = new Vector();
        for ($y = 0; $y < $this->y; $y++) {
            for ($x = 0; $x < $this->x; $x++) {
                if ($x === $y) {
                    $Vector->push($this->get($this->x - 1 - $x, $y));
                }
            }
        }
        return $Vector;
    }

    /**
     * Returns a Vector representing the column at the given offset.
     *
     * @param integer $offset
     * @return Vector<mixed>
     */
    public function getColumn(int $offset): Vector
    {
        $column = new Vector();

        foreach ($this->table as $y => $row) {
            if ($row->capacity() > $offset) {
                $column->push($row->get($offset));
            } else {
                $column->push(null);
            }
        }
        if ($column->isEmpty()) {
            throw new \OutOfBoundsException('x');
        }
        return $column;
    }

    /**
     * Returns a Vector representing the row at the given offset.
     *
     * @param integer $y
     * @return Vector<mixed>
     */
    public function getRow(int $y): Vector
    {
        return $this->table->get($y);
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
        assert($this->isSquare(),
            new \LogicException('cofactors can only be calculated for a square matrix')
        );

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
        for ($y = 0; $y < $this->y; $y++) {
            for ($x = 0; $x < $this->x; $x++) {
                if ($x === $y) {
                    $row->push($this->get($x, $y));
                }
            }
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
        $matrix = new self(clone $this);
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
        for ($y = 0; $y < $this->y; $y++) {
            if ($y === $row_y) continue;
            $row = new Vector();
            for ($x = 0; $x < $this->x; $x++) {
                if ($x === $column_x) continue;
                $row->push($this->get($x, $y));
            }
            $rows->push($row);
        }

        return new Matrix($rows);
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
        assert($this->isSquare(),
            new \LogicException('inverse is only defined for a square matrix')
        );
        assert($this->isInvertible(),
            new \LogicException('inverse is only defined for a matrix with a non-zero determinant')
        );

        if ($this->y === 1) {
            return new Matrix([[1 / $this->get(0, 0)]]);
        }

        $adjugate = $this->getAdjugate();
        $this->setData($adjugate->divide($this->determinant()));

        return $this;
    }

    /**
     * @return bool
     */
    public function isDiagonal(): bool
    {
        return $this->isLowerTriangular() && $this->isUpperTriangular();
    }

    /**
     * @return bool
     */
    public function isTriangular(): bool
    {
        return $this->isLowerTriangular() || $this->isUpperTriangular();
    }

    /**
     * @return bool
     */
    public function isLowerTriangular(): bool
    {
        if (!$this->isSquare()) {
            return false;
        }
        foreach ($this->table as $y => $row) {
            foreach ($row->slice($y + 1) as $x => $cell) {
                if ($cell != 0) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isUpperTriangular(): bool
    {
        if (!$this->isSquare()) {
            return false;
        }
        foreach ($this->table->slice(1) as $y => $row) {
            foreach ($row->slice(0, ($y + 1)) as $x => $cell) {
                if ($cell != 0) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Check if this Matrix is invertible.
     * 
     * @return bool
     */
    public function isInvertible(): bool
    {
        return $this->determinant() != 0.0;
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
        if ($this->isSquare()) {
            return $this->equals($this->getTranspose());
        }
        return false;
    }

    /**
     * Check if this Matrix is skew-symmetric.
     * 
     * @return bool
     */
    public function isSkewSymmetric(): bool
    {
        if ($this->isSquare()) {
            return $this->getTranspose()->equals($this->getNegative());
        }
        return false;
    }

    /**
     * Get the sum of the diagonal
     *
     * @return int|float
     */
    public function trace()
    {
        assert($this->isSquare(),
            new \LogicException('trace is only defined for a square matrix')
        );

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
        $equals = false;
        if ($this->x === $m->x && $this->y === $m->y) {
            $equals = true;
            foreach ($this->table as $y => $row) {
                foreach ($row as $x => $cell) {
                    $equals = $this->get($x, $y) === $m->get($x, $y);
                    if (!$equals) {
                        break 2;
                    }
                }
            }
        }
        return $equals;
    }

    /**
     * Add a Matrix or value to this instance.
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

        return $this->setData($this->added($input));
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

        return $this->map(function ($myValue, $x, $y) use ($input) {
            return $myValue + $input->get($x, $y);
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

        return $this->setData($this->subtracted($input));
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

        return $this->map(function ($myValue, $x, $y) use ($input) {
            return $myValue - $input->get($x, $y);
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

        return $this->setData($this->multiplied($input));
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

        return $this->mapMatrix($input, function ($myValue, $theirValue) {
            return $myValue * $theirValue;
        }, self::REFLECT);
    }

    /**
     * Divide this Matrix by a Matrix or value.
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
     * Return a new Matrix of this Matrix divided by a Matrix or value.
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
     * Mutative.
     * 
     * @param Matrix<Vector<Vector<mixed>>>|float|integer $input
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function exponentiated($input): Matrix
    {
        assert(is_numeric($input), new \InvalidArgumentException('power must be numeric'));

        $clone = clone $this;
        $matrix = clone $this;
        for ($i = 1; $i < $input; $i++) {
            $matrix->multiply($clone);
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
        $matrix = new self;
        $table = new Map;
        foreach ($this->table as $y => $row) {
            foreach ($row as $x => $cell) {
                if (!$table->hasKey($x)) {
                   $table->put($x, new Map);
                }
                $table->get($x)->put($y, $cell);
            }
        }

        $this->setData($table);

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
        $rows = new Vector();
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
        foreach ($this->table as $y => $row) {
            $row_Vector = new Vector();
            foreach ($row as $x => $cell) {
                $row_Vector->push($callback($cell, $x, $y));
            }
            $rows->push($row_Vector);
        }

        return new Matrix($rows);
    }

    /**
     * Apply a callback to each component of the Matrix, passing in a cooresponding
     * value from a supplied Matrix.
     * Mutative.
     * 
     * @param Matrix<Vector<Vector<mixed>>> $matrix
     * @param callable $callback
     * @param integer $validation [0: self::SQUARE, 1: self::SAME, 2: self::REFLECT]
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function applyMatrix(Matrix $matrix, callable $callback, $validation = 1): Matrix
    {
        return $this->setData($this->mapMatrix($matrix, $callback, $validation));
    }

    /**
     * Return a Matrix with a callback applied to each component of this Matrix, passing 
     * in a cooresponding value from a supplied Matrix.
     * 
     * @param Matrix<Vector<Vector<mixed>>> $matrix
     * @param callable $callback
     * @param integer $validation [0: self::SQUARE, 1: self::SAME, 2: self::REFLECT]
     * @return Matrix<Vector<Vector<mixed>>>
     */
    public function mapMatrix(Matrix $matrix, callable $callback, $validation = 1): Matrix
    {
        $this->validateDimensions($matrix, $validation);

        $newMatrix = Matrix::create($this->y, $matrix->x);
        for ($y = 0; $y < $this->y; $y++) {
            for ($x = 0; $x < $matrix->x; $x++) {
                $column = $matrix->getColumn($x);
                foreach ($this->getRow($y) as $z => $value) {
                    $newMatrix->set($x, $y, 
                        $newMatrix->get($x, $y) + $callback($value, $column->get($z), $x, $y, $z)
                    );
                }
            }
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
        $x = 0;
        $rows = new Vector();
        foreach ($table as $y => $row) {
            $row_Vector = new Vector($row);
            $c = $row_Vector->count();
            if ($c > $x) {
                $x = $c;
            }
            $rows->push($row_Vector);
        }
        $this->y = $rows->count();
        $this->x = $x;

        $this->table = new Vector($rows);

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
     * @param integer $type
     */
    public function validateDimensions(Matrix $matrix, $type = 0): void
    {
        if ($type === self::SQUARE) {
            assert($matrix->x === $matrix->y,
                new \LogicException('matrix must be square')
            );
        }
        if ($type === self::SAME) {
            assert($this->x === $matrix->x && $this->y === $matrix->y,
                new \LogicException('matrices must have the same dimensions')
            );
        }
        if ($type === self::REFLECT) {
            assert($this->x === $matrix->y && $this->y === $matrix->x,
                new \LogicException('matrix dimension mismatch: column count must match row count')
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
        foreach ($this->table as $key => $value) {
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
        for ($y = 0; $y < $this->y; $y++) {
            $out .= '[';
            for ($x = 0; $x < $this->x; $x++) {
                $out .= $this->get($x, $y).', ';
            }
            $out = rtrim($out, ' ,');
            $out .= ']'.\PHP_EOL;
        }
        $out .= \PHP_EOL;

        return $out;
    }
}