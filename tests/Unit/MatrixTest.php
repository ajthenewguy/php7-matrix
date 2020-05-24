<?php

namespace Tests\Unit;

use Ds\{Map, Vector};
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
        $table = $matrix->getData();

        $this->assertCount(5, $table);
        $this->assertCount(5, $table->get(0));
        $this->assertEquals(0, $matrix->get(0, 0));
        $this->assertEquals(0, $matrix->get(4, 4));
        
        $matrix = Matrix::create(4, 3, 2);
        $table = $matrix->getData();

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
        $this->expectError();
        $this->expectErrorMessage('matrix must be square');

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

    public function testGetColumnOutOfBoundsException1()
    {
        $this->expectError();

        $matrix = new Matrix();

        $matrix->getColumn(0);
    }

    public function testGetColumnOutOfBoundsException2()
    {
        $this->expectError();

        $matrix = new Matrix([
            [4, -2,  8],
            [1,  9,  2],
            [2,  5,  7],
        ]);

        $matrix->getColumn(3);
    }

    public function testGetColumn()
    {
        $matrix = new Matrix([
            [4, -2,  8],
            [1,  9,  0],
            [2,  5,  7],
        ]);
        $expected = new Vector([-2, 9, 5]);

        $this->assertEquals($expected, $matrix->getColumn(1));

        $expected = new Vector([8, 0, 7]);

        $this->assertEquals($expected, $matrix->getColumn(2));
    }

    public function testGetRowOutOfBoundsException()
    {
        $this->expectError();

        $matrix = new Matrix([
            [4, -2,  8],
            [1,  9,  2],
            [2,  5,  7],
        ]);

        $matrix->getRow(3);
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
        $inversed = $matrix->getInverse();
        
        $this->assertEquals($expected, $inversed);
        $this->assertNotEquals($expected, $matrix);
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
        $this->expectError();
        $this->expectErrorMessage('matrix must be square');

        $matrix = new Matrix([
            [1, 3, 2],
            [4, 1, 3]
        ]);
        $matrix->inverse();
    }

    public function testInverseInvertibleException()
    {
        $this->expectError();
        $this->expectErrorMessage('matrix must have a non-zero determinant (invertible)');

        $matrix = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [5, 7, 9]
        ]);
        $matrix->inverse();
    }

    public function testInverse()
    {
        $matrix = new Matrix([[1]]);
        $expected = new Matrix([[1]]);
        $matrix->inverse();
        
        $this->assertEquals($expected, $matrix);

        $matrix = new Matrix([
            [-3, 1],
            [ 5, 0]
        ]);
        $expected = new Matrix([
            [0, 1/5],
            [1, 3/5]
        ]);
        $matrix->inverse();
        
        $this->assertEquals($expected, $matrix);

        $matrix = new Matrix([
            [ 1, -1, 3],
            [ 2,  1, 2],
            [-2, -2, 1]
        ]);
        $expected = new Matrix([
            [     1,  -1,  -1],
            [-(6/5), 7/5, 4/5],
            [-(2/5), 4/5, 3/5]
        ]);
        $matrix->inverse();
        
        $this->assertEquals($expected, $matrix);
        
        $matrix = new Matrix([
            [2, 1],
            [4, 4]
        ]);
        $expected = new Matrix([
            [ 1, -0.25],
            [-1,  0.5 ]
        ]);
        $matrix->inverse();
        
        $this->assertEquals($expected, $matrix);

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

    public function testIsDiagonal()
    {
        $matrix = new Matrix([
            [1, 2, 3],
            [0, 5, 6],
            [0, 0, 9]
        ]);

        $this->assertFalse($matrix->isDiagonal());

        $matrix = new Matrix([
            [1, 0, 0],
            [4, 5, 0],
            [5, 7, 9]
        ]);

        $this->assertFalse($matrix->isDiagonal());

        $matrix = new Matrix([
            [1, 0, 0],
            [0, 5, 0],
            [0, 0, 9]
        ]);

        $this->assertTrue($matrix->isDiagonal());
    }

    public function testIsTriangular()
    {
        $matrix = new Matrix([
            [1, 0, 0, 0],
            [0, 5, 0, 0],
            [0, 7, 9, 0]
        ]);

        $this->assertFalse($matrix->isTriangular());

        $matrix = new Matrix([
            [1, 2, 3],
            [0, 5, 6],
            [0, 0, 9]
        ]);

        $this->assertTrue($matrix->isTriangular());

        $matrix = new Matrix([
            [1, 0, 0],
            [4, 5, 0],
            [5, 7, 9]
        ]);

        $this->assertTrue($matrix->isTriangular());
    }

    public function testIsLowerTriangular()
    {
        $matrix = new Matrix([
            [1, 0, 0, 0],
            [0, 5, 0, 0],
            [0, 7, 9, 0]
        ]);

        $this->assertFalse($matrix->isLowerTriangular());

        $matrix = new Matrix([
            [1, 2, 3],
            [0, 5, 6],
            [0, 0, 9]
        ]);

        $this->assertFalse($matrix->isLowerTriangular());

        $matrix = new Matrix([
            [1, 0, 0],
            [4, 5, 0],
            [5, 7, 9]
        ]);

        $this->assertTrue($matrix->isLowerTriangular());
    }

    public function testIsUpperTriangular()
    {
        $matrix = new Matrix([
            [1, 0, 0, 0],
            [0, 5, 0, 0],
            [0, 7, 9, 0]
        ]);

        $this->assertFalse($matrix->isUpperTriangular());

        $matrix = new Matrix([
            [1, 0, 0],
            [4, 5, 0],
            [5, 7, 9]
        ]);

        $this->assertFalse($matrix->isUpperTriangular());

        $matrix = new Matrix([
            [1, 2, 3],
            [0, 5, 6],
            [0, 0, 9]
        ]);

        $this->assertTrue($matrix->isUpperTriangular());
    }

    public function testIsInvertible()
    {
        // isInvertible
        $matrix = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [5, 7, 9]
        ]);

        $this->assertFalse($matrix->isInvertible());

        $matrix = new Matrix([
            [2, 5, 1],
            [0, 3, 5],
            [0, 6, 7]
        ]);

        $this->assertTrue($matrix->isInvertible());
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
            [ 1,  2],
            [-2,  4],
            [ 1,  2]
        ]);

        $this->assertFalse($matrix->isSymmetric());

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
            [  0,  2],
            [ -2,  0],
            [ 45,  4]
       ]);

       $this->assertFalse($matrix->isSkewSymmetric());
        
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
        $this->expectError();
        $this->expectErrorMessage('matrix must be square');

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

        $matrix->add(1);

        $this->assertEquals($expected, $matrix);
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

        $matrix1->add($matrix2);

        $this->assertEquals($expected, $matrix1);
    }

    public function testAddedScalar()
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

        $this->assertEquals($expected, $matrix->added(1));
    }

    public function testAddedMatrix()
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

        $this->assertEquals($expected, $matrix1->added($matrix2));
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

        $matrix->subtract(1);

        $this->assertEquals($expected, $matrix);
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

        $matrix1->subtract($matrix2);

        $this->assertEquals($expected, $matrix1);
    }

    public function testSubtractedScalar()
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

        $this->assertEquals($expected, $matrix->subtracted(1));
    }

    public function testSubtractedMatrix()
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

        $this->assertEquals($expected, $matrix1->subtracted($matrix2));
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
            [0, 1, 2],
            [3, 4, 5]
        ]);
        $matrix2 = new Matrix([
            [ 6,  7],
            [ 8,  9],
            [10, 11]
        ]);
        $expected = new Matrix([
            [ 28,  31],
            [100, 112]
        ]);

        $this->assertEquals($expected, $matrix1->multiply($matrix2));

        $matrix1 = new Matrix([
            [1, 2],
            [3, 4]
        ]);
        $matrix2 = new Matrix([
            [1, 2],
            [3, 4]
        ]);
        $expected = new Matrix([
            [ 7, 10],
            [15, 22]
        ]);

        $this->assertEquals($expected, $matrix1->multiply($matrix2));
    }

    public function testMultipliedScalar()
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

        $this->assertEquals($expected, $matrix->multiplied(3));
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

        $matrix->divide(2);

        $this->assertEquals($expected, $matrix);
    }

    public function testDivideMatrix()
    {
        $matrix1 = new Matrix([
            [4, 4],
            [6, 4]
        ]);
        $matrix2 = new Matrix([
            [2, 2],
            [3, 2]
        ]);
        $expected = new Matrix([
            [2, 0],
            [0, 2]
        ]);

        $this->assertEquals($expected, $matrix1->divide($matrix2));
    }

    public function testDividedScalar()
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

        $this->assertEquals($expected, $matrix->divided(2));
    }


    public function testExponential()
    {
        $matrix = new Matrix([
            [1, 2],
            [3, 4]
        ]);
        $expected = new Matrix([
            [ 7, 10],
            [15, 22]
        ]);

        $this->assertEquals($expected, $matrix->exponential(2));

        $matrix = Matrix::create(3, 3, 2);
        $expected = Matrix::create(3, 3, 12);

        $this->assertEquals($expected, $matrix->exponential(2));

        $matrix = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9]
        ]);
        $expected = new Matrix([
            [ 30,  36,  42],
            [ 66,  81,  96],
            [102, 126, 150]
        ]);

        $this->assertEquals($expected, $matrix->exponential(2));

        $matrix = new Matrix([
            [1, 2],
            [3, 4]
        ]);
        $expected = new Matrix([
            [37,  54],
            [81, 118]
        ]);

        $this->assertEquals($expected, $matrix->exponential(3));
    }

    public function testPowScalar()
    {
        $matrix = new Matrix([
            [1, 2, 3],
            [3, 2, 1],
            [4, 4, 4]
        ]);
        $expected = new Matrix([
            [19, 18, 17],
            [13, 14, 15],
            [32, 32, 32]
        ]);

        $this->assertEquals($expected, $matrix->exponential(2));
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
        $this->expectError();
        $this->expectErrorMessage('matrices must have the same dimensions');

        $matrix1 = new Matrix([
            [ 4, -2,  8],
            [ 3,  0, 14]
        ]);
        $matrix2 = new Matrix([
            [ 3,  0],
            [12,  7],
            [ 4, -2]
        ]);

        $matrix1->applyMatrix($matrix2, function () { return 0; }, Matrix::SAME);
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
        $callback = function ($value1, $value2, $x, $y, $z) {
            if ($x === $y) {
                return $value1 * $value2;
            }
            return $value1 + $value2;
        };
        
        $expected = Matrix::create($matrix1->y, $matrix2->x);
        for ($y = 0; $y < $matrix1->y; $y++) {
            for ($x = 0; $x < $matrix2->x; $x++) {
                $column = $matrix2->getColumn($x);
                foreach ($matrix1->getRow($y) as $z => $value) {
                    $expected->set($x, $y, 
                        $expected->get($x, $y) + $callback($value, $column->get($z), $x, $y, $z)
                    );
                }
            }
        }

        $matrix1->applyMatrix($matrix2, $callback);

        $this->assertEquals($expected, $matrix1);
    }

    public function testMapMatrixDimensionMismatchException()
    {
        $this->expectError();
        $this->expectErrorMessage('matrices must have the same dimensions');

        $matrix1 = new Matrix([
            [ 4, -2,  8],
            [ 3,  0, 14]
        ]);
        $matrix2 = new Matrix([
            [ 3,  0],
            [12,  7],
            [ 4, -2]
        ]);

        $matrix1->mapMatrix($matrix2, function () { return 0; }, Matrix::SAME);
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
        $callback = function ($value1, $value2, $x, $y) {
            if ($x === $y) {
                return $value1 * $value2;
            }
            return $value1 + $value2;
        };
        
        $expected = Matrix::create($matrix1->y, $matrix2->x);
        for ($y = 0; $y < $matrix1->y; $y++) {
            for ($x = 0; $x < $matrix2->x; $x++) {
                $column = $matrix2->getColumn($x);
                foreach ($matrix1->getRow($y) as $z => $value) {
                    $expected->set($x, $y, 
                        $expected->get($x, $y) + $callback($value, $column->get($z), $x, $y)
                    );
                }
            }
        }

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

    public function testSetDataInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = [
            [4, -2,  8],
            [1,  9],
            [2,  5,  7],
        ];
        $matrix = new Matrix();
        $matrix->setData($data);
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

    public function testGetter()
    {
        $matrix = new Matrix([
            [1, 2, 3],
            [0, 4, 5],
            [1, 0, 6]
        ]);

        $this->assertEquals(3, $matrix->x);
        $this->assertNull($matrix->data);
    }

    public function testCallException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $matrix = new Matrix([
            [1, 2, 3],
            [0, 4, 5],
            [1, 0, 6]
        ]);

        $matrix->upsideDown();
    }

    public function testCall()
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

        // getAdjoint is alias for getAdjugate
        $adj = $matrix->getAdjoint();

        $this->assertEquals($expected, $adj);
    }

    public function testToString()
    {
        $matrix = new Matrix([
            [1, 2, 3],
            [0, 4, 5],
            [1, 0, 6]
        ]);

        $expected = '[1, 2, 3]
[0, 4, 5]
[1, 0, 6]
';

        $this->assertEquals($expected, $matrix.'');
    }
}
