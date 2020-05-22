<?php
declare(strict_types=1);

namespace Matrix;

use Ds\{Map, Vector};
use Ds\Traits\GenericCollection;

/**
 * Class Matrix
 * 
 * @package Matrix
 */
class Matrix implements \IteratorAggregate, \JsonSerializable
{
    use GenericCollection;

    /**
     * Method aliases. Format:
     * this => calls this
     *
     * @var array
     */
    private static $aliases = [
        'getAdjoint' => 'getAdjugate',
        'invert'     => 'inverse',
        'inversed'   => 'getInverse',
        'inverted'   => 'getInverse',
        'transposed' => 'getTranspose'
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
     * @var Vector
     */
    protected $table;
    /*
        Vector([                         |
            Vector([ Point, Point ]),    |
            Vector([ Point, Point ]),    Y
            Vector([ Point, Point ])     |
        ])                               |
        ---------------- X --------------|
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
     * @return Matrix
     */
    public static function create(int $width,  ?int $height = null, $fill = 0): Matrix
    {
        if (is_null($height)) {
            $height = $width;
        }
        $x = 0;
        $rows = new Vector();
        for ($y = 0; $y < $height; $y++) {
            $row_vector = new Vector();
            for ($x = 0; $x < $width; $x++) {
                $row_vector->push($fill);
            }
            $rows->push($row_vector);
        }
        return new static($rows);
    }

    /**
     * Get the cofactors of the supplied Matrix.
     * 
     * @param Matrix $matrix
     * @return Matrix
     */
    public static function cofactors(Matrix $matrix): Matrix
    {
        return $matrix->getCofactors();
    }

    /**
     * Create and return an identity Matrix.
     *
     * @param integer $size
     * @return Matrix
     */
    public static function identity(int $size): Matrix
    {
        $x = 0;
        $rows = new Vector();
        for ($y = 0; $y < $size; $y++) {
            $row_vector = new Vector();
            for ($x = 0; $x < $size; $x++) {
                $row_vector->push(\intval($x === $y));
            }
            $rows->push($row_vector);
        }
        return new static($rows);
    }

    /**
     * Get a Matrix of the minors of the supplied Matrix for the given column/row.
     *
     * @param Matrix $matrix
     * @param integer $column_x
     * @param integer $row_y
     * @return Matrix
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
     * @return Vector
     */
    public function getAntidiagonal(): Vector
    {
        $vector = new Vector();
        for ($y = 0; $y < $this->y; $y++) {
            for ($x = 0; $x < $this->x; $x++) {
                if ($x === $y) {
                    $vector->push($this->get($this->x - 1 - $x, $y));
                }
            }
        }
        return $vector;
    }

    /**
     * Returns a Vector representing the column at the given offset.
     *
     * @param integer $offset
     * @return Vector
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
     * @return Vector
     */
    public function getRow(int $y): Vector
    {
        return $this->table->get($y);
    }

    /**
     * Get the inner Vector that holds the matrix data.
     * 
     * @return Vector
     */
    public function getData(): Vector
    {
        return $this->table;
    }

    /**
     * Get the ajugate/adjoint of the matrix
     * @aliases['getAdjoint']
     *
     * @return Matrix
     */
    public function getAdjugate(): Matrix
    {
        return $this->getCofactors()->transpose();
    }

    /**
     * Return new Matrix of the cofactors of all components.
     *
     * @return Matrix
     */
    public function getCofactors(): Matrix
    {
        assert($this->isSquare(),
            new \LogicException('cofactors can only be calculated for a square matrix')
        );

        $minor_factor = 1;
        return $this->map(function ($cell, $x, $y) use (&$minor_factor) {
            $determinant = $this->getMinors($x, $y)->determinant() * $minor_factor;
            $minor_factor = -$minor_factor;
            return $determinant;
        });
    }

    /**
     * Get a Vector of the diagonal components.
     * 
     * @return Vector
     */
    public function getDiagonal(): Vector
    {
        $vector = new Vector();
        for ($y = 0; $y < $this->y; $y++) {
            for ($x = 0; $x < $this->x; $x++) {
                if ($x === $y) {
                    $vector->push($this->get($x, $y));
                }
            }
        }
        return $vector;
    }

    /**
     * Return new Matrix of this Matrix's inverse
     *
     * @return Matrix
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
     * @return Matrix
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

        return new static($rows);
    }

    /**
     * Get a Matrix of the negative of this Matrix.
     * 
     * @return Matrix
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
     * @return Matrix
     */
    public function getTranspose(): Matrix
    {
        $matrix = new self(clone $this);
        $matrix->transpose();
        return $matrix;
    }

    /**
     * Update this Matrix to its inverse.
     * 
     * @return Matrix
     */
    public function inverse(): Matrix
    {
        assert($this->isSquare(),
            new \LogicException('inverse is only defined for a square matrix')
        );
        assert($this->isInvertable(),
            new \LogicException('inverse is only defined for a matrix with a non-zero determinant')
        );

        if ($this->y === 1) {
            return new static([[1 / $this->get(0, 0)]]);
        }

        $adjugate = $this->getAdjugate();
        $this->setData($adjugate)->multiply(1 / $this->determinant());

        return $this;
    }

    /**
     * Check if this Matrix is invertable.
     * 
     * @return bool
     */
    public function isInvertable(): bool
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
            return $this->equals($this->transposed());
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
            return $this->transposed()->equals($this->getNegative());
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
     * 
     * @param Matrix|float|integer $input
     * @return Matrix
     */
    public function add($input): Matrix
    {
        if (is_numeric($input)) {
            return $this->apply(function ($cell) use ($input) {
                return $cell + $input;
            });
        }
        return $this->applyMatrix($input, function ($myValue, $theirValue) {
            return $myValue + $theirValue;
        });
    }

    /**
     * Return a new Matrix with a Matrix or value added to this instance.
     * 
     * @param Matrix|float|integer $input
     * @return Matrix
     */
    public function added($input): Matrix
    {
        if (is_numeric($input)) {
            return $this->map(function ($cell) use ($input) {
                return $cell + $input;
            });
        }
        return $this->mapMatrix($input, function ($myValue, $theirValue) {
            return $myValue + $theirValue;
        });
    }

    /**
     * Subtract a Matrix or value to this instance.
     * 
     * @param Matrix|float|integer $input
     * @return Matrix
     */
    public function subtract($input): Matrix
    {
        if (is_numeric($input)) {
            return $this->apply(function ($cell) use ($input) {
                return $cell - $input;
            });
        }
        return $this->applyMatrix($input, function ($myValue, $theirValue) {
            return $myValue - $theirValue;
        });
    }

    /**
     * Return a new Matrix with a Matrix or value subtracted from this instance.
     * 
     * @param Matrix|float|integer $input
     * @return Matrix
     */
    public function subtracted($input): Matrix
    {
        if (is_numeric($input)) {
            return $this->map(function ($cell) use ($input) {
                return $cell - $input;
            });
        }
        return $this->mapMatrix($input, function ($myValue, $theirValue) {
            return $myValue - $theirValue;
        });
    }

    /**
     * Multiply a Matrix or value to this instance.
     * 
     * @param Matrix|float|integer $input
     * @return Matrix
     */
    public function multiply($input): Matrix
    {
        if (is_numeric($input)) {
            return $this->apply(function ($cell) use ($input) {
                return $cell * $input;
            });
        }
        return $this->applyMatrix($input, function ($myValue, $theirValue) {
            return $myValue * $theirValue;
        });
    }

    /**
     * Return a new Matrix with a Matrix or value multiplied from this instance.
     * 
     * @param Matrix|float|integer $input
     * @return Matrix
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
        });
    }

    /**
     * Multiply a Matrix or value to this instance.
     * 
     * @param Matrix|float|integer $input
     * @return Matrix
     */
    public function divide($input): Matrix
    {
        if (is_numeric($input)) {
            assert($input != 0.0, new \DivisionByZeroError());
            
            return $this->apply(function ($cell) use ($input) {
                return $cell / $input;
            });
        }
        return $this->applyMatrix($input, function ($myValue, $theirValue) {
            assert($theirValue != 0.0, new \DivisionByZeroError());

            return $myValue / $theirValue;
        });
    }

    /**
     * Return a new Matrix with a Matrix or value multiplied from this instance.
     * 
     * @param Matrix|float|integer $input
     * @return Matrix
     */
    public function divided($input): Matrix
    {
        if (is_numeric($input)) {
            assert($input != 0.0, new \DivisionByZeroError());
            
            return $this->map(function ($cell) use ($input) {
                return $cell / $input;
            });
        }
        return $this->mapMatrix($input, function ($myValue, $theirValue) {
            assert($theirValue != 0.0, new \DivisionByZeroError());

            return $myValue / $theirValue;
        });
    }

    /**
     * Flip the Matrix along the diagonal.
     * 
     * @return Matrix
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
                $new_row = $table->get($x);
                $new_row->put($y, $cell);
            }
        }

        $this->setData($table);

        return $this;
    }

    /**
     * Apply a callback to each component of the Matrix.
     * 
     * @param callable $callback
     * @return Matrix
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
     * @return Matrix
     */
    public function map(callable $callback): Matrix
    {
        $rows = new Vector();
        foreach ($this->table as $y => $row) {
            $row_vector = new Vector();
            foreach ($row as $x => $cell) {
                $row_vector->push($callback($cell, $x, $y));
            }
            $rows->push($row_vector);
        }

        return new static($rows);
    }

    /**
     * Apply a callback to each component of the Matrix, passing in a cooresponding
     * value from a supplied Matrix.
     * 
     * @param Matrix $matrix
     * @param callable $callback
     * @return Matrix
     */
    public function applyMatrix(Matrix $m, callable $callback): Matrix
    {
        assert($this->x === $m->x && $this->y === $m->y,
            new \LogicException('matrices must have the same dimensions to apply a callback to another matrix')
        );

        $rows = new Vector();
        foreach ($this->table as $y => $row) {
            foreach ($row as $x => $cell) {
                $this->set($x, $y, $callback($cell, $m->get($x, $y), $x, $y));
            }
        }

        return $this;
    }

    /**
     * Return a Matrix with a callback applied to each component of this Matrix, passing 
     * in a cooresponding value from a supplied Matrix.
     * 
     * @param Matrix $matrix
     * @param callable $callback
     * @return Matrix
     */
    public function mapMatrix(Matrix $m, callable $callback): Matrix
    {
        assert($this->x === $m->x && $this->y === $m->y,
            new \LogicException('matrices must have the same dimensions to apply a callback to another matrix')
        );

        $rows = new Vector();
        foreach ($this->table as $y => $row) {
            $row_vector = new Vector();
            foreach ($row as $x => $cell) {
                $row_vector->push($callback($cell, $m->get($x, $y), $x, $y));
            }
            $rows->push($row_vector);
        }

        return new static($rows);
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
     *
     * @param integer $x
     * @param integer $y
     * @param mixed $value
     * @return void
     */
    public function set(int $x, int $y, $value)
    {
        return $this->table->get($y)->set($x, $value);
    }

    /**
     * Set the inner data Vector.
     * 
     * @param iterable $table
     * @return Matrix
     */
    public function setData(iterable $table = []): Matrix
    {
        $x = 0;
        $rows = new Vector();
        foreach ($table as $y => $row) {
            $row_vector = new Vector($row);
            $c = $row_vector->count();
            if ($c > $x) {
                $x = $c;
            }
            $rows->push($row_vector);
        }
        $this->y = $rows->count();
        $this->x = $x;

        $this->table = new Vector($rows);

        return $this;
    }

    /**
     * Return an array representation of the Matrix data.
     * 
     * @return array
     */
    public function toArray(): array
    {
        $data = [];
        foreach ($this->table as $y => $vector) {
           $data[$y] = $vector->toArray();
        }
        return $data;
    }

    /**
     * Get iterator.
     */
    public function getIterator()
    {
        foreach ($this->table as $key => $value) {
            yield $value;
        }
    }

    public function rewind()
    {
        reset($this->table);
    }

    public function current()
    {
        return current($this->table);
    }

    public function key()
    {
        return key($this->table);
    }

    public function next()
    {
        return next($this->table);
    }

    public function valid()
    {
        return false !== current($this->table);
    }

    /**
     * Return a JSON representation of the Matrix data.
     * 
     * @return Vector
     */
    public function jsonSerialize(): Vector
    {
        return $this->table;
    }


    public function __call($name, $arguments)
    {
        if (array_key_exists($name, static::$aliases)) {
            return call_user_func_array([$this, static::$aliases[$name]], $arguments);
        }
    }

    /**
     * Ensures that the internal table will be cloned too.
     */
    public function __clone()
    {
        $this->table = clone $this->table;
    }

    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
    }
}
