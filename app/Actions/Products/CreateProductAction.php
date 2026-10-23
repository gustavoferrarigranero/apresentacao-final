<?php

namespace App\Actions\Products;

use App\Domains\Products\Data\ProductData;
use App\Domains\Products\Exceptions\ProductException;
use App\Models\Produt;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Executa o caso de uso `CreateProductAction` no domínio de produtos, mantendo a operação de negócio fora da camada HTTP.
 */
final readonly class CreateProductAction
{
    public function __construct(private ProductRepository $productRepository) {}

    public function execute(int $restaurantId, ProductData $data): Produt
    {
        try {
            return DB::transaction(
                fn (): Produt => $this->productRepository->create($restaurantId, $data->attributes())
            );
        } catch (ModelNotFoundException|ProductException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ProductException::creationFailed($exception);
        }
    }
}
