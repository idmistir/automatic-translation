<?php

namespace MageOS\AutomaticTranslation\Cron;

use MageOS\AutomaticTranslation\Api\TranslateCategoriesInterface;

/**
 * Class TranslateCategories
 */
class TranslateCategories
{
    /**
     * @var TranslateCategoriesInterface
     */
    protected TranslateCategoriesInterface $translateCategories;

    /**
     * TranslateCategories constructor.
     * @param TranslateCategoriesInterface $translateCategories
     */
    public function __construct(
        TranslateCategoriesInterface $translateCategories
    ) {
        $this->translateCategories = $translateCategories;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $this->translateCategories->translateCategories();
    }
}
