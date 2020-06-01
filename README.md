# php7-matrix
PHP Matrix leveraging PHP7 data structures.

## Usage
Instances can be instantiated with data, passing in an array of arrays. The first array should be the rows, the members of each being the column/cell values.

### Instantiation

    use Matrix\Matrix;

    $Matrix = new Matrix([
        [1, 2, 3],
        [4, 5, 6],
        [7, 8, 9]
    ]);

    // Create a 3x3 matrix filled with 1's.

    $Matrix = Matrix::create($width = 3, $height = 3, 1);

    // Create a 5x5 identity matrix.

    $Matrix = Matrix::identity(5);

### Non-Mutative Instance Methods

    public added($input): Matrix

Return a new matrix with a matrix or numeric value added to this instance.

    public determinant(): numeric

Scalar value that can be computed from the elements of a square matrix.

    public divided($input): Matrix

Return a new matrix divided by a matrix or numeric value.

    public equals(Matrix $m): bool

Check if the matrix equals the suppled matrix.

    public exponentiated($input): Matrix

Returns a new matrix exponentiated by a numeric value.

    public get(int $x_column, int $y_row)

Get the value at the given coordinates/offsets; 0-based index.

    public getAdjugate(): Matrix

Get the ajugate/adjoint of the matrix, which is the transpose of its cofactor matrix.

    public getAntidiagonal(): Vector

Get a Vector of the diagonal going from the lower left corner to the upper right corner.

    public getCofactors(): Matrix

Return new Matrix of the cofactors of all components.

    public getColumn(int $offset): Vector

Returns a Vector representing the column at the given offset.

    public getData(): Vector

Get the inner Vector that holds the matrix data.

    public getDiagonal(): Vector

Get a Vector of the diagonal components.

    public getInverse(): Matrix

Return new Matrix of this Matrix's inverse

    public getMinors(int $column_x, int $row_y = 0): Matrix

Get a Matrix of the minors of this Matrix for the given column/row.

    public getNegative(): Matrix

Get a Matrix of the negative of this Matrix.

    public getRow(int $y): Vector

Returns a Vector representing the row at the given offset.

    public getTranspose(): Matrix

Get a Matrix with the components of this Matrix flipped along the diagonal.

    public isDiagonal(): bool

Check if the matrix is a diagonal matrix, a matrix in which the entries outside the main diagonal are all zero.

    public isTriangular(): bool

Check if the matrix is triangular, a matrix that is either upper or lower triangular.

    public isLowerTriangular(): bool

Check if all the entries above the main diagonal are zero.

    public isUpperTriangular(): bool

Check if all the entries below the main diagonal are zero.

    public isInvertible(): bool

Check if this Matrix is invertible, a matrix where the determinant is non-zero.

    public isSquare(): bool

Check if the matrix has the same number of rows and columns.

    public isSymmetric(): bool

Check if the matrix equals its transpose.

    public isSkewSymmetric(): bool

Check if the matrix transpose equals its negative.

    public map(callable $callback): Matrix

Return a matrix with a callback applied to each component of this matrix.

    public mapMatrix(Matrix $matrix, callable $callback, $validation = self::SAME): Matrix

Return a matrix with a callback applied to each component of this matrix, passing in a cooresponding value from a supplied Matrix.

    public multiplied($input): Matrix

Return a new matrix with a matrix or value multiplied from this instance.

    public subtracted($input): Matrix

Return a new matrix with a matrix or value subtracted from this instance.

    public toArray(): array

Return an array representation of the Matrix data.

    public trace()

Get the sum of the diagonal.

### Mutative Instance Methods

    public add($input): Matrix

Add a matrix or numeric value to the matrix.

    public apply(callable $callback): Matrix

Apply a callback to each component of the Matrix.

    public applyMatrix(Matrix $matrix, callable $callback, $validation = self::SAME): Matrix

Apply a callback to each component of the Matrix, passing in a cooresponding value from a supplied Matrix.

    public divide($input): Matrix

Divide this matrix by a matrix or numeric value.

    public exponential($input): Matrix

Exponentiate this matrix by a numeric value.

    public inverse(): Matrix

Update this matrix to its inverse.

    public multiply($input): Matrix

Multiply a matrix or value to this instance.

    public set(int $x_column, int $y_row, $value): void

Set the value at the given coordinates/offsets; 0-based index.

    public setData(iterable $table = []): Matrix

Set the inner data Vector.

    public subtract($input): Matrix

Subtract a matrix or value to this instance.

    public transpose(): Matrix

Flip the Matrix along the diagonal.

## Matrix Mapping Algorithm
When mapping one matrix to another, their dimensions must match exactly or must reflect (transposed dimensions). Either the column counts and row counts must match or the row count of one must match the column count of the other.

### Example: Matrix->multiply(Matrix)

Matrix A:

    [1, 2, 3]
    [4, 5, 6]

Matrix B:

    [7, 8]
    [9, 10]
    [11, 12]


Expected:

    [ 58,  64]
    [139, 154]

STEP 1 - 1×7:

    A:             B:
    [1, -, -] x [7, -]
    [-, -, -]   [-, -]
                [-, -]

STEP 2 - 2×9:

    A:             B:
    [-, 2, -] x [-, -]
    [-, -, -]   [9, -]
                [-, -]

STEP 3 - 3×11:

    A:             B:
    [-, -, 3] x [ -, -]
    [-, -, -]   [ -, -]
                [11, -]

STEP 4 - Sum the products:

    C:
    [58, -]
    [ -, -]

STEP 5 - 1×8:

    A:             B:
    [1, -, -] x [-, 8]
    [-, -, -]   [-, -]
                [-, -]

STEP 6 - 2×10:

    A:             B:
    [-, 2, -] x [-,  -]
    [-, -, -]   [-, 10]
                [-,  -]

STEP 7 - 3×12:

    A:             B:
    [-, -, 3] x [-,  -]
    [-, -, -]   [-,  -]
                [-, 12]

STEP 8 - Sum the products:

    C:
    [58, 64]
    [ -, -]

STEP 9 - 4×7:

    A:             B:
    [-, -, -] x [7, -]
    [4, -, -]   [-, -]
                [-, -]

STEP 10 - 5×9:

    A:             B:
    [-, -, -] x [-, -]
    [-, 5, -]   [9, -]
                [-, -]

STEP 11 - 6×11:

    A:             B:
    [-, -, -] x [-,  -]
    [-, -, 6]   [-,  -]
                [11, -]

STEP 12 - Sum the products:

    C:
    [ 58, 64]
    [139,  -]

STEP 13 - 4×8:

    A:             B:
    [-, -, -] x [-, 8]
    [4, -, -]   [-, -]
                [-, -]

STEP 14 - 5×10:

    A:             B:
    [-, -, -] x [-,  -]
    [-, 5, -]   [-, 10]
                [-,  -]

STEP 15 - 6×12:

    A:             B:
    [-, -, -] x [-,  -]
    [-, -, 6]   [-,  -]
                [-, 12]

STEP 16 - Sum the products:

    C:
    [ 58,  64]
    [139, 154]