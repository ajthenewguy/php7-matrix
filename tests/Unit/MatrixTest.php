<?php

namespace Tests\Unit;

use Ds\Map;
use Ds\Vector;
use Matrix\Matrix;
use PHPUnit\Framework\TestCase;

class MatrixTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCreate()
    {
        $matrix = Matrix::create(5);
        $table = $matrix->table;

        $this->assertCount(5, $table);
        $this->assertCount(5, $table->get(0));
        $this->assertEquals(0, $matrix->get(0, 0));
        $this->assertEquals(0, $matrix->get(4, 4));
        
        $matrix = Matrix::create(4, 3, 2);
        $table = $matrix->table;

        $this->assertCount(3, $table);
        $this->assertCount(4, $table->get(0));
        $this->assertEquals(2, $matrix->get(0, 0));
        $this->assertEquals(2, $matrix->get(3, 2));
    }

    public function testIdentity()
    {
        $matrix = Matrix::identity(3);

        $this->assertEquals(1, $matrix->get(0, 0));
        $this->assertEquals(0, $matrix->get(1, 0));
        $this->assertEquals(0, $matrix->get(2, 0));
        $this->assertEquals(0, $matrix->get(0, 1));
        $this->assertEquals(1, $matrix->get(1, 1));
        $this->assertEquals(0, $matrix->get(2, 1));
        $this->assertEquals(0, $matrix->get(0, 2));
        $this->assertEquals(0, $matrix->get(1, 2));
        $this->assertEquals(1, $matrix->get(2, 2));
    }

    public function testGetAntidiagonal()
    {
        $matrix = new Matrix([
            [1, 2, 3, 4],
            [0, 4, 5, 6],
            [1, 0, 6, 7],
            [2, 6, 2, 7]
        ]);
        $expected = new Vector([4, 5, 0, 2]);
        $adia = $matrix->getAntiDiagonal();

        $this->assertEquals($expected, $adia);
    }

    public function testDeterminantSquareException()
    {
        $this->expectError(\LogicException::class);
        $this->expectErrorMessage('determinant is only defined for a square matrix');

        $matrix = new Matrix([
            [1, 3, 2],
            [4, 1, 3]
        ]);

        $matrix->determinant();
    }

    public function testDeterminant()
    {
        $matrix = new Matrix([
            [1, 2],
            [3, 4]
        ]);
        $det = $matrix->determinant();

        $this->assertEquals(-2, $det);

        $matrix = new Matrix([
            [1, 3, 2],
            [4, 1, 3],
            [2, 5, 2]
        ]);
        $det = $matrix->determinant();

        $this->assertEquals(17, $det);

        $matrix = new Matrix([
            [4,  3,  2,  2],
            [0,  1, -3,  3],
            [0, -1,  3,  3],
            [0,  3,  1,  1]
        ]);
        $det = $matrix->determinant();

        $this->assertEquals(-240, $det);
    }

    public function testGetColumn()
    {
        $matrix = new Matrix([
            [4, -2,  8],
            [1,  9, -2],
            [2,  5,  7],
        ]);
        $expected = new Vector([-2, 9, 5]);

        $this->assertEquals($expected, $matrix->getColumn(1));
    }

    public function testGetRow()
    {
        $matrix = new Matrix([
            [4, -2,  8],
            [1,  9, -2],
            [2,  5,  7],
        ]);
        $expected = new Vector([1, 9, -2]);

        $this->assertEquals($expected, $matrix->getRow(1));
    }

    public function testGetData()
    {
        $expected = new Vector([
            new Vector([4, -2,  8]),
            new Vector([1,  9, -2]),
            new Vector([2,  5,  7])
        ]);
        $matrix = new Matrix($expected);

        $this->assertEquals($expected, $matrix->getData());
    }

    public function testGetAdjugate()
    {
        $matrix = new Matrix([
            [1, 2, 3],
            [0, 4, 5],
            [1, 0, 6]
        ]);
        $expected = new Matrix([
            [ 24, -12,  -2],
            [  5,   3,  -5],
            [ -4,   2,   4]
        ]);
        $adj = $matrix->getAdjugate();

        $this->assertEquals($expected, $adj);
    }

    public function testGetCofactors()
    {
        $matrix = new Matrix([
            [1, 2, 3],
            [0, 4, 5],
            [1, 0, 6]
        ]);
        $expected = new Matrix([
            [  24,   5,  -4],
            [ -12,   3,   2],
            [  -2,  -5,   4]
        ]);
        $cof = $matrix->getCofactors();

        $this->assertEquals($expected, $cof);
    }

    public function testGetDiagonal()
    {
        $matrix = new Matrix([
            [4,  3,  2,  2],
            [0,  1, -3,  3],
            [0, -1,  3,  3],
            [0,  3,  1,  1]
        ]);
        $expected = new Vector([4, 1, 3, 1]);
        $dia = $matrix->getDiagonal();

        $this->assertEquals($expected, $dia);
    }

    public function testGetInverse()
    {
        $matrix = new Matrix([
            [1, 3, 3],
            [1, 4, 3],
            [1, 3, 4]
        ]);
        $expected = new Matrix([
            [7, -3, -3],
            [-1, 1,  0],
            [-1, 0,  1]
        ]);
        $matrix->inverse();
        
        $this->assertEquals($expected, $matrix);
    }

    public function testGetMinors()
    {
        $matrix = new Matrix([
            [ 1,  4,  2,  3],
            [ 0,  1,  4,  4],
            [-1,  0,  1,  0],
            [ 2,  0,  4,  1]
        ]);
        $expected = [[
            new Matrix([
                [1, 4, 4],
                [0, 1, 0],
                [0, 4, 1]
            ]),
            new Matrix([
                [0, 4, 4],
                [-1, 1, 0],
                [2, 4, 1]
            ]),
            new Matrix([
                [0, 1, 4],
                [-1, 0, 0],
                [2, 0, 1]
            ]),
            new Matrix([
                [0, 1, 4],
                [-1, 0, 1],
                [2, 0, 4]
            ])
        ], [
            new Matrix([
                [4, 2, 3],
                [0, 1, 0],
                [0, 4, 1]
            ]),
            new Matrix([
                [1, 2, 3],
                [-1, 1, 0],
                [2, 4, 1]
            ]),
            new Matrix([
                [1, 4, 3],
                [-1, 0, 0],
                [2, 0, 1]
            ]),
            new Matrix([
                [1, 4, 2],
                [-1, 0, 1],
                [2, 0, 4]
            ])
        ], [
            new Matrix([
                [4, 2, 3],
                [1, 4, 4],
                [0, 4, 1]
            ]),
            new Matrix([
                [1, 2, 3],
                [0, 4, 4],
                [2, 4, 1]
            ]),
            new Matrix([
                [1, 4, 3],
                [0, 1, 4],
                [2, 0, 1]
            ]),
            new Matrix([
                [1, 4, 2],
                [0, 1, 4],
                [2, 0, 4]
            ])
        ], [
            new Matrix([
                [4, 2, 3],
                [1, 4, 4],
                [0, 1, 0]
            ]),
            new Matrix([
                [1, 2, 3],
                [0, 4, 4],
                [-1, 1, 0]
            ]),
            new Matrix([
                [1, 4, 3],
                [0, 1, 4],
                [-1, 0, 0]
            ]),
            new Matrix([
                [1, 4, 2],
                [0, 1, 4],
                [-1, 0, 1]
            ])
        ]];

        foreach ($expected as $y => $row) {
            foreach ($row as $x => $minor) {
                $actual = $matrix->getMinors($x, $y);
                $this->assertEquals($minor, $actual);
            }
        }
    }

    public function testGetNegative()
    {
        $matrix = new Matrix([
            [4,  3,  2,  2],
            [0,  1, -3,  3],
            [0, -1,  3,  3],
            [0,  3,  1,  1]
        ]);
        $expected = new Matrix([
            [-4,-3, -2,  -2],
            [0, -1,  3,  -3],
            [0,  1, -3,  -3],
            [0, -3, -1,  -1]
        ]);
        $neg = $matrix->getNegative();

        $this->assertEquals($expected, $neg);
    }

    public function testGetTranspose()
    {
        $matrix = new Matrix([
            [ 7,  8,  9],
            [ 2, -4,  0],
            [-1,  3, 16]
        ]);
        $expected = new Matrix([
            [7,  2, -1],
            [8, -4,  3],
            [9,  0, 16]
        ]);

        $this->assertEquals($expected, $matrix->transpose());
    }

    public function testInverseSquareException()
    {
        $this->expectError(\LogicException::class);
        $this->expectErrorMessage('inverse is only defined for a square matrix');

        $matrix = new Matrix([
            [1, 3, 2],
            [4, 1, 3]
        ]);
        $matrix->inverse();
    }

    public function testInverseInvertableException()
    {
        $this->expectError(\LogicException::class);
        $this->expectErrorMessage('inverse is only defined for a matrix with a non-zero determinant');

        $matrix = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [5, 7, 9]
        ]);
        $matrix->inverse();
    }

    public function testInverse()
    {
        $matrix = new Matrix([
            [1, 3, 3],
            [1, 4, 3],
            [1, 3, 4]
        ]);
        $expected = new Matrix([
            [7, -3, -3],
            [-1, 1,  0],
            [-1, 0,  1]
        ]);
        $matrix->inverse();
        
        $this->assertEquals($expected, $matrix);
    }

    public function testIsInvertable()
    {
        // isInvertable
        $matrix = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [5, 7, 9]
        ]);

        $this->assertFalse($matrix->isInvertable());

        $matrix = new Matrix([
            [2, 5, 1],
            [0, 3, 5],
            [0, 6, 7]
        ]);

        $this->assertTrue($matrix->isInvertable());
    }

    public function testIsSquare()
    {
        $matrix = new Matrix([
            [1, 2, 3],
            [0, 1, 2]
        ]);

        $this->assertFalse($matrix->isSquare());

        $matrix = new Matrix([
            [1, 3, 2],
            [4, 1, 3],
            [1, 4, 3]
        ]);

        $this->assertTrue($matrix->isSquare());
    }

    public function testIsSymmetric()
    {
        $matrix = new Matrix([
            [ 1,  2,  1],
            [-2,  4, -2],
            [ 1,  2,  1]
        ]);

        $this->assertFalse($matrix->isSymmetric());

        $matrix = new Matrix([
            [ 2,  7, -8],
            [ 7,  9,  0],
            [-8,  0,  2]
        ]);

        $this->assertTrue($matrix->isSymmetric());
    }

    public function testIsSkewSymmetric()
    {
        $matrix = new Matrix([
            [-8,  0,  6],
            [ 0, 10,  4],
            [ 6,  4, -8]
        ]);

        $this->assertFalse($matrix->isSkewSymmetric());

        $matrix = new Matrix([
             [  0,  2, -45],
             [ -2,  0,  -4],
             [ 45,  4,   0]
        ]);

        $this->assertTrue($matrix->isSkewSymmetric());
    }

    public function testTrace()
    {
        $matrix = new Matrix([
            [1, 0, 0],
            [0, 2, 3],
            [0, 0, 4]
        ]);
        
        $this->assertEquals(7, $matrix->trace());

        $matrix = new Matrix([
            [-4, 7, -8],
            [10, 4, 18],
            [-1, 0, -3]
        ]);

        $this->assertEquals(-3, $matrix->trace());
    }

    public function testTraceSquareMatrixException()
    {
        $this->expectError(\LogicException::class);
        $this->expectErrorMessage('trace is only defined for a square matrix');

        $matrix = new Matrix([
            [1, 2, 3],
            [4, 5, 6]
        ]);
        $matrix->trace();
    }

    public function testEquals()
    {
        $matrix1 = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 0]
        ]);

        $matrix2 = new Matrix([
            [1, 4, 7],
            [2, 5, 8],
            [3, 6, 0]
        ]);

        $this->assertFalse($matrix1->equals($matrix2));

        $matrix3 = new Matrix([
            [1, 4, 7],
            [2, 5, 8],
            [3, 6, 0]
        ]);

        $matrix4 = new Matrix([
            [1, 4, 7],
            [2, 5, 8],
            [3, 6, 0]
        ]);

        $this->assertTrue($matrix3->equals($matrix4));
    }

    public function testAddScalar()
    {
        $matrix = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 0]
        ]);
        $expected = new Matrix([
            [2, 3, 4],
            [5, 6, 7],
            [8, 9, 1]
        ]);

        $this->assertEquals($expected, $matrix->add(1));
    }

    public function testAddMatrix()
    {
        $matrix1 = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 0]
        ]);
        $matrix2 = new Matrix([
            [0, 8, 7],
            [6, 5, 4],
            [3, 2, 1]
        ]);
        $expected = new Matrix([
            [ 1, 10, 10],
            [10, 10, 10],
            [10, 10,  1]
        ]);

        $this->assertEquals($expected, $matrix1->add($matrix2));
    }

    public function testSubtractScalar()
    {
        $matrix = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 0]
        ]);
        $expected = new Matrix([
            [0, 1,  2],
            [3, 4,  5],
            [6, 7, -1]
        ]);

        $this->assertEquals($expected, $matrix->subtract(1));
    }

    public function testSubtractMatrix()
    {
        $matrix1 = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 0]
        ]);
        $matrix2 = new Matrix([
            [0, 8, 7],
            [6, 5, 4],
            [3, 2, 1]
        ]);
        $expected = new Matrix([
            [ 1, -6, -4],
            [-2,  0,  2],
            [ 4,  6, -1]
        ]);

        $this->assertEquals($expected, $matrix1->subtract($matrix2));
    }

    public function testMultiplyScalar()
    {
        $matrix = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 0]
        ]);
        $expected = new Matrix([
            [ 3,  6,  9],
            [12, 15, 18],
            [21, 24,  0]
        ]);

        $this->assertEquals($expected, $matrix->multiply(3));
    }

    public function testMultiplyMatrix()
    {
        $matrix1 = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 0]
        ]);
        $matrix2 = new Matrix([
            [0, 8, 7],
            [6, 5, 4],
            [3, 2, 1]
        ]);
        $expected = new Matrix([
            [ 0, 16, 21],
            [24, 25, 24],
            [21, 16,  0]
        ]);

        $this->assertEquals($expected, $matrix1->multiply($matrix2));
    }

    public function testDivideScalar()
    {
        $matrix = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 0]
        ]);
        $expected = new Matrix([
            [0.5,   1, 1.5],
            [  2, 2.5,   3],
            [3.5,   4,   0]
        ]);

        $this->assertEquals($expected, $matrix->divide(2));
    }

    public function testDivideMatrix()
    {
        $matrix1 = new Matrix([
            [1, 2, 7],
            [9, 5, 6],
            [6, 8, 0]
        ]);
        $matrix2 = new Matrix([
            [2, 8, 7],
            [3, 5, 4],
            [3, 2, 1]
        ]);
        $expected = new Matrix([
            [0.5, 0.25, 1  ],
            [3  , 1   , 1.5],
            [2  , 4   , 0  ]
        ]);

        $this->assertEquals($expected, $matrix1->divide($matrix2));
    }

    public function testTranspose()
    {
        $matrix = new Matrix([
            [ 7,  8,  9],
            [ 2, -4,  0],
            [-1,  3, 16]
        ]);
        $expected = new Matrix([
            [7,  2, -1],
            [8, -4,  3],
            [9,  0, 16]
        ]);

        $this->assertEquals($expected, $matrix->transpose());
    }

    public function testApply()
    {
        $matrix = new Matrix([
            [ 4, -2,  8],
            [ 3,  0, 14],
            [16,  7, 12]
        ]);
        $expected = new Matrix([
            [  0,  1, -10],
            [ -2,  0, -15],
            [-14, -6,   0]
        ]);
        $callback = function ($value, $x, $y) {
            if ($x === $y) {
                return 0;
            }
            return ($value + ($x - $y)) * -1;
        };
        $matrix->apply($callback);

        $this->assertEquals($expected, $matrix);
    }

    public function testMap()
    {
        $matrix = new Matrix([
            [ 4, -2,  8],
            [ 3,  0, 14],
            [16,  7, 12]
        ]);
        $expected = new Matrix([
            [  0,  1, -10],
            [ -2,  0, -15],
            [-14, -6,   0]
        ]);
        $callback = function ($value, $x, $y) {
            if ($x === $y) {
                return 0;
            }
            return ($value + ($x - $y)) * -1;
        };

        $this->assertEquals($expected, $matrix->map($callback));
    }

    public function testApplyMatrixDimensionMismatchException()
    {
        $this->expectError(\LogicException::class);
        $this->expectErrorMessage('matrices must have the same dimensions to apply a callback to another matrix');

        $matrix1 = new Matrix([
            [ 4, -2,  8],
            [ 3,  0, 14]
        ]);
        $matrix2 = new Matrix([
            [ 3,  0],
            [12,  7],
            [ 4, -2]
        ]);

        $matrix1->applyMatrix($matrix2, function () { return 0; });
    }

    public function testApplyMatrix()
    {
        $matrix1 = new Matrix([
            [ 4, -2,  8],
            [ 3,  0, 14],
            [16,  7, 12]
        ]);
        $matrix2 = new Matrix([
            [ 3,  0, 14],
            [12,  7, 16],
            [ 4, -2,  8]
        ]);
        $expected = new Matrix([
            [12, -2,  22],
            [15,  0,  30],
            [20,  5,  96]
        ]);
        $callback = function ($value1, $value2, $x, $y) {
            if ($x === $y) {
                return $value1 * $value2;
            }
            return $value1 + $value2;
        };
        $matrix1->applyMatrix($matrix2, $callback);

        $this->assertEquals($expected, $matrix1);
    }

    public function testMapMatrixDimensionMismatchException()
    {
        $this->expectError(\LogicException::class);
        $this->expectErrorMessage('matrices must have the same dimensions to apply a callback to another matrix');

        $matrix1 = new Matrix([
            [ 4, -2,  8],
            [ 3,  0, 14]
        ]);
        $matrix2 = new Matrix([
            [ 3,  0],
            [12,  7],
            [ 4, -2]
        ]);

        $matrix1->mapMatrix($matrix2, function () { return 0; });
    }

    public function testMapMatrix()
    {
        $matrix1 = new Matrix([
            [ 4, -2,  8],
            [ 3,  0, 14],
            [16,  7, 12]
        ]);
        $matrix2 = new Matrix([
            [ 3,  0, 14],
            [12,  7, 16],
            [ 4, -2,  8]
        ]);
        $expected = new Matrix([
            [12, -2,  22],
            [15,  0,  30],
            [20,  5,  96]
        ]);
        $callback = function ($value1, $value2, $x, $y) {
            if ($x === $y) {
                return $value1 * $value2;
            }
            return $value1 + $value2;
        };

        $this->assertEquals($expected, $matrix1->mapMatrix($matrix2, $callback));
    }

    public function testGet()
    {
        $matrix = new Matrix([
            [4, -2,  8],
            [1,  9, -2],
            [2,  5,  7],
        ]);

        $this->assertEquals(4, $matrix->get(0, 0));
        $this->assertEquals(-2, $matrix->get(1, 0));
        $this->assertEquals(8, $matrix->get(2, 0));
        $this->assertEquals(1, $matrix->get(0, 1));
        $this->assertEquals(9, $matrix->get(1, 1));
        $this->assertEquals(-2, $matrix->get(2, 1));
        $this->assertEquals(2, $matrix->get(0, 2));
        $this->assertEquals(5, $matrix->get(1, 2));
        $this->assertEquals(7, $matrix->get(2, 2));
    }

    public function testSet()
    {
        $matrix = Matrix::create(3);

        $this->assertEquals(0, $matrix->get(0, 0));
        $this->assertEquals(0, $matrix->get(1, 1));
        $this->assertEquals(0, $matrix->get(2, 2));

        $matrix->set(1, 1, -43);

        $this->assertEquals(0, $matrix->get(0, 0));
        $this->assertEquals(-43, $matrix->get(1, 1));
        $this->assertEquals(0, $matrix->get(2, 2));
    }

    public function testSetData()
    {
        $data = [
            [4, -2,  8],
            [1,  9, -2],
            [2,  5,  7],
        ];
        $expected = new Vector([
            new Vector([4, -2,  8]),
            new Vector([1,  9, -2]),
            new Vector([2,  5,  7])
        ]);
        $matrix = new Matrix();
        $matrix->setData($data);

        $this->assertEquals($expected, $matrix->getData());
    }

    public function testToArray()
    {
        $data = [
            [4, -2,  8],
            [1,  9, -2],
            [2,  5,  7],
        ];
        $matrix = new Matrix($data);

        $this->assertEquals($data, $matrix->toArray());
    }

    public function testIteration()
    {
        $matrix = new Matrix([
            [4, -2,  8],
            [1,  9, -2],
            [2,  5,  7],
        ]);

        foreach ($matrix as $y => $row) {
            foreach ($row as $x => $cell) {
                $this->assertEquals($matrix->get($x, $y), $cell);
            }
        }
    }

    public function testJsonSerialize()
    {
        $matrix = new Matrix([
            [4, -2,  8],
            [1,  9, -2],
            [2,  5,  7],
        ]);
        $expected = "[[4,-2,8],[1,9,-2],[2,5,7]]";

        $this->assertEquals($expected, \json_encode($matrix));
    }
}
