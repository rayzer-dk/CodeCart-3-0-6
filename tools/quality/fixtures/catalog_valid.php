<?php

final class ControllerPhpstanCatalogValidFixture extends Controller
{
    public function verify(): void
    {
        $product = $this->model_catalog_product->getProduct(1);
        $this->response->setOutput('ok');
    }
}
