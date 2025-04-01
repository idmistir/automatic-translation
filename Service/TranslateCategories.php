<?php

namespace MageOS\AutomaticTranslation\Service;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use MageOS\AutomaticTranslation\Api\CategoryTranslatorInterface;
use MageOS\AutomaticTranslation\Api\TranslateCategoriesInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service as ServiceHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Api\Data\CategoryAttributeInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use MageOS\AutomaticTranslation\Api\AttributeProviderInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\DataObject;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\CategoryList;

/**
 * Class TranslateCategories
 */
class TranslateCategories implements TranslateCategoriesInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;
    /**
     * @var ServiceHelper
     */
    protected ServiceHelper $serviceHelper;
    /**
     * @var SearchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    /**
     * @var CategoryRepositoryInterface
     */
    protected CategoryRepositoryInterface $categoryRepository;
    /**
     * @var FilterBuilder
     */
    protected FilterBuilder $filterBuilder;
    /**
     * @var CategoryTranslatorInterface
     */
    protected CategoryTranslatorInterface $categoryTranslator;
    /**
     * @var CategoryList
     */
    protected CategoryList $categoryList;

    /**
     * TranslateCategories constructor.
     * @param StoreManagerInterface $storeManager
     * @param ModuleConfig $moduleConfig
     * @param ServiceHelper $serviceHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CategoryRepositoryInterface $categoryRepository
     * @param FilterBuilder $filterBuilder
     * @param CategoryTranslatorInterface $categoryTranslator
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ModuleConfig $moduleConfig,
        ServiceHelper $serviceHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CategoryRepositoryInterface $categoryRepository,
        FilterBuilder $filterBuilder,
        CategoryTranslatorInterface $categoryTranslator,
        CategoryList $categoryList
    ) {
        $this->storeManager = $storeManager;
        $this->moduleConfig = $moduleConfig;
        $this->serviceHelper = $serviceHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->categoryRepository = $categoryRepository;
        $this->filterBuilder = $filterBuilder;
        $this->categoryTranslator = $categoryTranslator;
        $this->categoryList = $categoryList;
    }

    /**
     * @return void
     */
    public function translateCategories(): void
    {
        $storeToTranslate = $this->serviceHelper->getStoresToTranslate();

        foreach ($storeToTranslate as $storeId => $storeName) {
            $categoriesToTranslate = $this->getCategoriesToTranslate($storeId);
            $targetLanguage = $this->moduleConfig->getDestinationLanguage($storeId);
            $sourceLanguage = $this->moduleConfig->getSourceLanguage();

            /** @var $category DataObject|CategoryInterface */
            foreach ($categoriesToTranslate as $category) {
                $this->categoryTranslator->translateCategory($category, $targetLanguage, $sourceLanguage, $storeName, $storeId);
            }
        }
    }

    /**
     * @param $storeId
     * @return array
     */
    protected function getCategoriesToTranslate($storeId): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilder;
        $this->filterByStatus($searchCriteriaBuilder, $storeId);

        if ($this->moduleConfig->isEnablePeriodicRetranslation()) {
            $searchCriteriaBuilder2 = clone $searchCriteriaBuilder;

            $this->filterByRetranslationDate($searchCriteriaBuilder, $storeId);
            $searchCriteria = $searchCriteriaBuilder->create();
            $expiredTranslationCategory = $this->categoryList->getList($searchCriteria)->getItems();

            $this->filterByRetranslationDate($searchCriteriaBuilder2, $storeId, false);
            $this->filterBySkipTranslation($searchCriteriaBuilder2);
            $searchCriteria2 = $searchCriteriaBuilder2->create();
            $unexpiredButUnskipTranslationCategory = $this->categoryList->getList($searchCriteria2)->getItems();

            $categories = array_merge($expiredTranslationCategory, $unexpiredButUnskipTranslationCategory);
        } else {
            $this->filterBySkipTranslation($searchCriteriaBuilder);
            $searchCriteria = $searchCriteriaBuilder->create();
            $categories = $this->categoryList->getList($searchCriteria)->getItems();
        }

        return $categories;
    }

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param int $storeId
     * @return void
     */
    protected function filterByStatus(SearchCriteriaBuilder $searchCriteriaBuilder, int $storeId = 0)
    {
        if (!$this->moduleConfig->translateDisabledCategories($storeId)) {
            $searchCriteriaBuilder->addFilter('is_active', Status::STATUS_ENABLED);
        }
    }

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param int $storeId
     * @param bool $getExpired
     * @return void
     */
    protected function filterByRetranslationDate(SearchCriteriaBuilder $searchCriteriaBuilder, int $storeId = 0, bool $getExpired = true): void
    {
        $translationExpirationDate = $this->moduleConfig->getTranslationExpirationDate($storeId);

        if ($getExpired) {
            $searchCriteriaBuilder->addFilters([
                $this->filterBuilder->setField(AttributeProviderInterface::LAST_TRANSLATION)
                    ->setValue('')
                    ->setConditionType('null')
                    ->create(),
                $this->filterBuilder->setField(AttributeProviderInterface::LAST_TRANSLATION)
                    ->setValue($translationExpirationDate)
                    ->setConditionType('lteq')
                    ->create()
            ]);
        } else {
            $searchCriteriaBuilder->addFilter(AttributeProviderInterface::LAST_TRANSLATION, $translationExpirationDate, 'gt');
        }
    }

    /**
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @return void
     */
    protected function filterBySkipTranslation(SearchCriteriaBuilder $searchCriteriaBuilder): void
    {
        $searchCriteriaBuilder->addFilters([
            $this->filterBuilder->setField(AttributeProviderInterface::SKIP_TRANSLATION)
                ->setValue(0)
                ->setConditionType('eq')
                ->create(),
            $this->filterBuilder->setField(AttributeProviderInterface::SKIP_TRANSLATION)
                ->setValue('')
                ->setConditionType('null')
                ->create(),
            $this->filterBuilder->setField(AttributeProviderInterface::SKIP_TRANSLATION)
                ->setValue(1)
                ->setConditionType('neq')
                ->create()
        ]);
    }
}
