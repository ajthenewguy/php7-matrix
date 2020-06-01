<?php

use Matrix\Matrix;

/**
 * @BeforeMethods({"setUp"})
 */
class TestBench
{
    public function setUp()
    {
        //
    }

    /**
     * @Revs(2000)
     * @Iterations(5)
     * @Sleep(1000000)
     * @OutputTimeUnit("milliseconds", precision=3)
     */
    public function benchMark()
    {
        $this->matrixCalcs(Matrix::random(3));
        $this->matrixCalcs(Matrix::identity(4));
        $this->matrixCalcs(Matrix::random(5));
        Matrix::random(3)
            ->multiply(Matrix::random(3))
            ->equals(Matrix::random(3));
    }

    private function matrixCalcs($m)
    {
        $calc = [
            'diagonal' => $m->getDiagonal(),
            'antidiagonal' => $m->getAntidiagonal(),
            'is_diagonal' => $m->isDiagonal(),
            'is_skew_symmetric' => $m->isSkewSymmetric(),
            'column' => $m->getColumn(0),
            'invert_or_transpose' => ($m->isInvertible() ? $m->inverse() : $m->transpose()),
        ];
    }
}
