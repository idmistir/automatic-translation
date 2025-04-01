<?php

namespace MageOS\AutomaticTranslation\Api;

use Magento\Catalog\Api\Data\CategoryInterface;

/**
 * Interface CategoryTranslatorInterface
 */
interface CategoryTranslatorInterface
{
    /**
     * @param CategoryInterface $category
     * @param string $targetLanguage
     * @param string $sourceLanguage
     * @param string $storeName
     * @param int $storeId
     * @return void
     */
    public function translateCategory(
        CategoryInterface $category,
        string $targetLanguage,
        string $sourceLanguage,
        string $storeName = 'Default Store View',
        int $storeId = 0
    ): void;
}
