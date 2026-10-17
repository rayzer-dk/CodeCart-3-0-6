<?php

final class ControllerPhpstanCatalogInvalidFixture extends Controller
{
    public function verify(): void
    {
        $this->model_catalog_product->getProductsCustomFilterNonExistent();
        $this->response->setOuput('typo');
    }
}
